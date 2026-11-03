<?php

namespace App\Services;

use App\Models\FreelancerProfile;
use App\Models\Rule;
use App\Models\RuleVersion;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use JsonException;

final class PersonalDataPackage
{
    private const FORMAT = 'freelance-proposal-memory';

    private const PROFILE_FIELDS = [
        'display_name_fa',
        'display_name_en',
        'professional_title_fa',
        'professional_title_en',
        'bio_fa',
        'bio_en',
        'experience_years',
        'location_fa',
        'location_en',
        'languages_fa',
        'languages_en',
        'services_fa',
        'services_en',
        'technologies_fa',
        'technologies_en',
        'portfolio_url',
        'website_url',
    ];

    private const EXPERIENCE_FIELDS = [
        'domain_fa',
        'domain_en',
        'position_fa',
        'position_en',
        'organization_fa',
        'organization_en',
        'years',
        'description_fa',
        'description_en',
        'skills_fa',
        'skills_en',
    ];

    /** @return array<string, mixed> */
    public function export(): array
    {
        $profile = FreelancerProfile::query()
            ->where('profile_key', 'primary')
            ->with(['workExperiences' => fn ($query) => $query->orderBy('id')])
            ->first();

        $profileData = [];
        foreach (self::PROFILE_FIELDS as $field) {
            $profileData[$field] = $profile?->getAttribute($field);
        }

        $experiences = $profile?->workExperiences->map(function ($experience): array {
            $data = ['key' => $experience->import_key ?: $this->portableKey('experience', (string) $experience->id)];
            foreach (self::EXPERIENCE_FIELDS as $field) {
                $data[$field] = $experience->getAttribute($field);
            }

            return $data;
        })->all() ?? [];

        $rules = Rule::query()
            ->where('scope', 'global')
            ->where('status', 'approved')
            ->with(['versions' => fn ($query) => $query->where('status', 'approved')->orderByDesc('version')])
            ->orderBy('priority')
            ->orderBy('id')
            ->get()
            ->map(function (Rule $rule): ?array {
                $version = $rule->versions->firstWhere('version', $rule->current_version);
                if (! $version) {
                    return null;
                }

                return [
                    'key' => $this->validKey($rule->legacy_key) ? $rule->legacy_key : $this->portableKey('rule', $rule->title),
                    'title' => $rule->title,
                    'body' => $version->body,
                    'priority' => $rule->priority,
                ];
            })
            ->filter()
            ->values()
            ->all();

        return [
            'format' => self::FORMAT,
            'version' => 1,
            'exported_at' => now()->toIso8601String(),
            'profile' => $profileData,
            'experiences' => $experiences,
            'rules' => $rules,
        ];
    }

    /** @return array<string, mixed> */
    public function validateUpload(UploadedFile $file): array
    {
        try {
            $payload = json_decode($file->get(), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw ValidationException::withMessages([
                'file' => __('ui.data.invalid_json'),
            ]);
        }

        if (! is_array($payload)) {
            throw ValidationException::withMessages([
                'file' => __('ui.data.invalid_package'),
            ]);
        }

        $profileRules = [
            'display_name_fa' => ['nullable', 'string', 'max:120'],
            'display_name_en' => ['nullable', 'string', 'max:120'],
            'professional_title_fa' => ['nullable', 'string', 'max:160'],
            'professional_title_en' => ['nullable', 'string', 'max:160'],
            'bio_fa' => ['nullable', 'string', 'max:4000'],
            'bio_en' => ['nullable', 'string', 'max:4000'],
            'experience_years' => ['nullable', 'integer', 'between:0,60'],
            'location_fa' => ['nullable', 'string', 'max:120'],
            'location_en' => ['nullable', 'string', 'max:120'],
            'languages_fa' => ['nullable', 'array', 'max:40'],
            'languages_fa.*' => ['string', 'max:80'],
            'languages_en' => ['nullable', 'array', 'max:40'],
            'languages_en.*' => ['string', 'max:80'],
            'services_fa' => ['nullable', 'array', 'max:80'],
            'services_fa.*' => ['string', 'max:160'],
            'services_en' => ['nullable', 'array', 'max:80'],
            'services_en.*' => ['string', 'max:160'],
            'technologies_fa' => ['nullable', 'array', 'max:80'],
            'technologies_fa.*' => ['string', 'max:100'],
            'technologies_en' => ['nullable', 'array', 'max:80'],
            'technologies_en.*' => ['string', 'max:100'],
            'portfolio_url' => ['nullable', 'url:http,https', 'max:2048'],
            'website_url' => ['nullable', 'url:http,https', 'max:2048'],
        ];

        $experienceFieldRules = [
            'domain_fa' => ['nullable', 'string', 'max:120'],
            'domain_en' => ['nullable', 'string', 'max:120'],
            'position_fa' => ['nullable', 'string', 'max:160'],
            'position_en' => ['nullable', 'string', 'max:160'],
            'organization_fa' => ['nullable', 'string', 'max:180'],
            'organization_en' => ['nullable', 'string', 'max:180'],
            'years' => ['nullable', 'integer', 'between:0,60'],
            'description_fa' => ['nullable', 'string', 'max:2000'],
            'description_en' => ['nullable', 'string', 'max:2000'],
            'skills_fa' => ['nullable', 'array', 'max:80'],
            'skills_fa.*' => ['string', 'max:100'],
            'skills_en' => ['nullable', 'array', 'max:80'],
            'skills_en.*' => ['string', 'max:100'],
        ];

        $rules = [
            'format' => ['required', 'in:'.self::FORMAT],
            'version' => ['required', 'integer', 'in:1'],
            'profile' => ['required', 'array:'.implode(',', array_filter(array_keys($profileRules), fn (string $field): bool => ! str_contains($field, '.')))],
            'experiences' => ['present', 'array', 'max:100'],
            'experiences.*' => ['required', 'array:key,'.implode(',', array_filter(array_keys($experienceFieldRules), fn (string $field): bool => ! str_contains($field, '.')))],
            'experiences.*.key' => ['required', 'string', 'max:64', 'regex:/^[A-Za-z0-9_-]+$/'],
            'rules' => ['present', 'array', 'max:500'],
            'rules.*' => ['required', 'array:key,title,body,priority'],
            'rules.*.key' => ['required', 'string', 'max:64', 'regex:/^[A-Za-z0-9_-]+$/'],
            'rules.*.title' => ['required', 'string', 'max:255'],
            'rules.*.body' => ['required', 'string', 'max:20000'],
            'rules.*.priority' => ['required', 'integer', 'between:1,65535'],
        ];

        foreach ($profileRules as $field => $fieldRules) {
            $rules["profile.{$field}"] = $fieldRules;
        }
        foreach ($experienceFieldRules as $field => $fieldRules) {
            $rules["experiences.*.{$field}"] = $fieldRules;
        }

        return Validator::make($payload, $rules)->validate();
    }

    /** @param array<string, mixed> $package
     *  @return array{profile_fields: int, experiences: int, rules_created: int, rules_updated: int, rules_unchanged: int}
     */
    public function import(array $package): array
    {
        return DB::transaction(function () use ($package): array {
            $profile = FreelancerProfile::query()->firstOrNew(['profile_key' => 'primary']);
            $incomingProfile = $package['profile'];
            $profileFields = 0;
            foreach (self::PROFILE_FIELDS as $field) {
                if (array_key_exists($field, $incomingProfile) && $incomingProfile[$field] !== null) {
                    $profile->setAttribute($field, $incomingProfile[$field]);
                    $profileFields++;
                }
            }
            $profile->save();

            $experienceCount = 0;
            foreach ($package['experiences'] as $experienceData) {
                $key = $experienceData['key'];
                unset($experienceData['key']);
                $experience = $profile->workExperiences()->where('import_key', $key)->first();
                if (! $experience) {
                    $experience = $profile->workExperiences()
                        ->whereNull('import_key')
                        ->get()
                        ->first(fn ($candidate): bool => collect(self::EXPERIENCE_FIELDS)
                            ->every(fn (string $field): bool => $candidate->getAttribute($field) === ($experienceData[$field] ?? null)));
                }

                if ($experience) {
                    $experience->fill(['import_key' => $key, ...$experienceData])->save();
                } else {
                    $profile->workExperiences()->create(['import_key' => $key, ...$experienceData]);
                }
                $experienceCount++;
            }

            $ruleCounts = ['rules_created' => 0, 'rules_updated' => 0, 'rules_unchanged' => 0];
            foreach ($package['rules'] as $ruleData) {
                $priority = (int) $ruleData['priority'];
                $rule = Rule::query()
                    ->where('scope', 'global')
                    ->where('legacy_key', $ruleData['key'])
                    ->lockForUpdate()
                    ->first();

                if (! $rule) {
                    $unkeyedRules = Rule::query()
                        ->where('scope', 'global')
                        ->whereNull('legacy_key')
                        ->where('title', $ruleData['title'])
                        ->with('versions')
                        ->get();
                    $rule = $unkeyedRules->first(function (Rule $candidate) use ($ruleData): bool {
                        return $candidate->versions->firstWhere('version', $candidate->current_version)?->body === $ruleData['body'];
                    });

                    if (! $rule && $unkeyedRules->count() === 1) {
                        $rule = $unkeyedRules->first();
                    }
                }

                if (! $rule) {
                    $rule = Rule::query()->create([
                        'legacy_key' => $ruleData['key'],
                        'scope' => 'global',
                        'title' => $ruleData['title'],
                        'status' => 'pending_approval',
                        'current_version' => 1,
                        'priority' => $priority,
                    ]);
                    $rule->versions()->create([
                        'version' => 1,
                        'title' => $ruleData['title'],
                        'priority' => $priority,
                        'body' => $ruleData['body'],
                        'status' => 'pending_approval',
                        'source_type' => 'portable_import',
                        'change_summary' => 'Imported rule proposal; awaiting explicit user approval.',
                        'created_at' => now(),
                    ]);
                    $ruleCounts['rules_created']++;

                    continue;
                }

                $current = $rule->versions()
                    ->where('version', $rule->current_version)
                    ->first();
                $pending = $rule->versions()
                    ->where('status', 'pending_approval')
                    ->orderByDesc('version')
                    ->first();
                $comparison = $pending ?? $current;
                $comparisonTitle = $comparison?->title ?? $rule->title;
                $comparisonPriority = $comparison?->priority ?? $rule->priority;

                if ($comparison?->body === $ruleData['body']
                    && $comparisonTitle === $ruleData['title']
                    && (int) $comparisonPriority === $priority) {
                    if ($rule->legacy_key === null) {
                        $rule->update(['legacy_key' => $ruleData['key']]);
                    }
                    $ruleCounts['rules_unchanged']++;

                    continue;
                }

                $versionNumber = ((int) $rule->versions()->max('version')) + 1;
                $rule->versions()->create([
                    'version' => $versionNumber,
                    'title' => $ruleData['title'],
                    'priority' => $priority,
                    'body' => $ruleData['body'],
                    'status' => 'pending_approval',
                    'source_type' => 'portable_import',
                    'change_summary' => 'Imported rule change proposal; awaiting explicit user approval.',
                    'created_at' => now(),
                ]);
                if ($rule->legacy_key === null) {
                    $rule->update(['legacy_key' => $ruleData['key']]);
                }
                $ruleCounts['rules_updated']++;
            }

            return [
                'profile_fields' => $profileFields,
                'experiences' => $experienceCount,
                ...$ruleCounts,
            ];
        });
    }

    private function portableKey(string $type, string $value): string
    {
        $prefix = $type.'_';

        return $prefix.substr(hash('sha256', $type.':'.$value), 0, 64 - strlen($prefix));
    }

    private function validKey(?string $key): bool
    {
        return $key !== null && preg_match('/^[A-Za-z0-9_-]{1,64}$/', $key) === 1;
    }
}
