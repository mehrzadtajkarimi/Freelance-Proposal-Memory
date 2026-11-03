<?php

namespace App\Http\Controllers;

use App\Models\FreelancerProfile;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class FreelancerProfileController
{
    public function edit(): View
    {
        $profile = FreelancerProfile::query()->where('profile_key', 'primary')->first()
            ?? new FreelancerProfile(['profile_key' => 'primary']);
        $locale = app()->getLocale();
        $profileValues = $profile->localizedValues($locale);

        return view('settings.freelancer-profile', compact('profile', 'profileValues'));
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
        ]);

        $locale = app()->getLocale();
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

        FreelancerProfile::query()->updateOrCreate(
            ['profile_key' => 'primary'],
            $profileData,
        );

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
