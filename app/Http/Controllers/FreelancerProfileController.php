<?php

namespace App\Http\Controllers;

use App\Models\FreelancerProfile;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class FreelancerProfileController
{
    public function edit(): View
    {
        $profile = FreelancerProfile::query()->where('profile_key', 'primary')->first()
            ?? new FreelancerProfile(['profile_key' => 'primary']);
        $locale = app()->getLocale();
        $profileValues = $profile->localizedValues($locale);
        $experiences = $profile->exists
            ? $profile->workExperiences()->orderBy('id')->get()->map(fn ($experience): array => [
                'id' => $experience->id,
                'domain' => $experience->getAttribute("domain_{$locale}"),
                'position' => $experience->getAttribute("position_{$locale}"),
                'organization' => $experience->getAttribute("organization_{$locale}"),
                'years' => $experience->years,
                'description' => $experience->getAttribute("description_{$locale}"),
                'skills' => implode("\n", $experience->getAttribute("skills_{$locale}") ?? []),
            ])->all()
            : [];

        return view('settings.freelancer-profile', compact('profile', 'profileValues', 'experiences'));
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'display_name' => ['nullable', 'string', 'max:120'],
            'professional_title' => ['nullable', 'string', 'max:160'],
            'bio' => ['nullable', 'string', 'max:4000'],
            'experience_years' => ['nullable', 'integer', 'between:0,60'],
            'location' => ['nullable', 'string', 'max:120'],
            'languages' => ['nullable', 'string', 'max:1200'],
            'services' => ['nullable', 'string', 'max:2000'],
            'technologies' => ['nullable', 'string', 'max:2400'],
            'portfolio_url' => ['nullable', 'url:http,https', 'max:2048'],
            'website_url' => ['nullable', 'url:http,https', 'max:2048'],
            'experiences' => ['sometimes', 'array', 'max:20'],
            'experiences.*' => ['array:id,domain,position,organization,years,description,skills'],
            'experiences.*.id' => ['nullable', 'integer'],
            'experiences.*.domain' => ['nullable', 'string', 'max:120'],
            'experiences.*.position' => ['nullable', 'string', 'max:160'],
            'experiences.*.organization' => ['nullable', 'string', 'max:180'],
            'experiences.*.years' => ['nullable', 'integer', 'between:0,60'],
            'experiences.*.description' => ['nullable', 'string', 'max:2000'],
            'experiences.*.skills' => ['nullable', 'string', 'max:1200'],
            'deleted_experiences' => ['sometimes', 'array', 'max:20'],
            'deleted_experiences.*' => ['integer', 'distinct'],
        ]);

        $locale = app()->getLocale();
        $experienceRows = $data['experiences'] ?? [];
        $deletedIds = collect($data['deleted_experiences'] ?? [])->map(fn ($id): int => (int) $id)->unique()->values();
        $rowIds = collect($experienceRows)->pluck('id')->filter()->map(fn ($id): int => (int) $id);
        if ($rowIds->count() !== $rowIds->unique()->count()) {
            throw ValidationException::withMessages([
                'experiences' => __('ui.profile.experience_invalid'),
            ]);
        }

        foreach ($experienceRows as $index => $row) {
            $experienceId = filled($row['id'] ?? null) ? (int) $row['id'] : null;
            if ($experienceId !== null && $deletedIds->contains($experienceId)) {
                continue;
            }

            $hasLocalizedContent = collect(['domain', 'position', 'organization', 'description', 'skills'])
                ->contains(fn (string $field): bool => filled($row[$field] ?? null));
            $hasNewDurationOnly = $experienceId === null && filled($row['years'] ?? null);

            if (($hasLocalizedContent || $hasNewDurationOnly) && blank($row['domain'] ?? null)) {
                throw ValidationException::withMessages([
                    "experiences.{$index}.domain" => __('ui.profile.experience_domain_required'),
                ]);
            }
        }

        $profile = FreelancerProfile::query()->where('profile_key', 'primary')->first();
        $submittedIds = $rowIds->merge($deletedIds)->unique()->values();
        $ownedCount = $profile?->workExperiences()->whereKey($submittedIds)->count() ?? 0;
        if ($ownedCount !== $submittedIds->count()) {
            throw ValidationException::withMessages([
                'experiences' => __('ui.profile.experience_invalid'),
            ]);
        }

        $localizedFields = [
            'display_name',
            'professional_title',
            'bio',
            'location',
            'languages',
            'services',
            'technologies',
        ];
        $listFields = ['languages', 'services', 'technologies'];
        $profileData = [];

        foreach ($localizedFields as $field) {
            if (! array_key_exists($field, $data)) {
                continue;
            }

            $value = $data[$field];
            $profileData["{$field}_{$locale}"] = in_array($field, $listFields, true)
                ? $this->parseList($value)
                : $value;
        }

        foreach (['experience_years', 'portfolio_url', 'website_url'] as $field) {
            if (array_key_exists($field, $data)) {
                $profileData[$field] = $data[$field];
            }
        }

        DB::transaction(function () use ($profileData, $experienceRows, $deletedIds, $locale): void {
            $profile = FreelancerProfile::query()->updateOrCreate(
                ['profile_key' => 'primary'],
                $profileData,
            );

            if ($deletedIds->isNotEmpty()) {
                $profile->workExperiences()->whereKey($deletedIds)->delete();
            }

            foreach ($experienceRows as $row) {
                $experienceId = filled($row['id'] ?? null) ? (int) $row['id'] : null;
                if ($experienceId !== null && $deletedIds->contains($experienceId)) {
                    continue;
                }

                $hasContent = collect(['domain', 'position', 'organization', 'years', 'description', 'skills'])
                    ->contains(fn (string $field): bool => filled($row[$field] ?? null));
                if (! $hasContent && $experienceId === null) {
                    continue;
                }

                $experienceData = [
                    "domain_{$locale}" => $row['domain'] ?? null,
                    "position_{$locale}" => $row['position'] ?? null,
                    "organization_{$locale}" => $row['organization'] ?? null,
                    'years' => $row['years'] ?? null,
                    "description_{$locale}" => $row['description'] ?? null,
                    "skills_{$locale}" => $this->parseList($row['skills'] ?? null),
                ];

                if ($experienceId !== null) {
                    $experience = $profile->workExperiences()->whereKey($experienceId)->firstOrFail();
                    $experience->fill($experienceData)->save();
                } else {
                    $profile->workExperiences()->create($experienceData);
                }
            }
        });

        return redirect()->route('settings.freelancer.edit')
            ->with('success', __('ui.profile.saved'));
    }

    /** @return list<string> */
    private function parseList(?string $value): array
    {
        if ($value === null || trim($value) === '') {
            return [];
        }

        $items = preg_split('/[\r\n,;]+/u', $value) ?: [];
        $items = array_map('trim', $items);
        $items = array_filter($items, static fn (string $item): bool => $item !== '');

        return array_values(array_unique($items));
    }
}
