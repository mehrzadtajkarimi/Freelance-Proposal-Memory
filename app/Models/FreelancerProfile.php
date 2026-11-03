<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class FreelancerProfile extends Model
{
    protected $fillable = [
        'profile_key',
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
        'setup_completed_at',
    ];

    protected function casts(): array
    {
        return [
            'experience_years' => 'integer',
            'setup_completed_at' => 'datetime',
            'languages_fa' => 'array',
            'languages_en' => 'array',
            'services_fa' => 'array',
            'services_en' => 'array',
            'technologies_fa' => 'array',
            'technologies_en' => 'array',
        ];
    }

    /** @return array<string, mixed> */
    public function localizedValues(string $locale): array
    {
        if (! in_array($locale, ['fa', 'en'], true)) {
            throw new \InvalidArgumentException('Profile locale must be fa or en.');
        }

        $values = [];
        foreach ([
            'display_name',
            'professional_title',
            'bio',
            'location',
            'languages',
            'services',
            'technologies',
        ] as $field) {
            $values[$field] = $this->getAttribute("{$field}_{$locale}");
        }

        return $values + [
            'experience_years' => $this->experience_years,
            'portfolio_url' => $this->portfolio_url,
            'website_url' => $this->website_url,
        ];
    }

    public function workExperiences(): HasMany
    {
        return $this->hasMany(FreelancerExperience::class);
    }

    /** @return list<array<string, mixed>> */
    public function localizedExperiences(string $locale): array
    {
        if (! in_array($locale, ['fa', 'en'], true)) {
            throw new \InvalidArgumentException('Profile locale must be fa or en.');
        }

        return $this->workExperiences()
            ->orderBy('id')
            ->get()
            ->map(fn (FreelancerExperience $experience): array => [
                'domain' => $this->localizedExperienceValue($experience, 'domain', $locale),
                'position' => $this->localizedExperienceValue($experience, 'position', $locale),
                'organization' => $this->localizedExperienceValue($experience, 'organization', $locale),
                'years' => $experience->years,
                'description' => $this->localizedExperienceValue($experience, 'description', $locale),
                'skills' => $this->localizedExperienceValue($experience, 'skills', $locale),
            ])
            ->all();
    }

    /** @return list<array<string, mixed>> */
    public function experienceContextValues(): array
    {
        return $this->workExperiences()
            ->orderBy('id')
            ->get()
            ->map(fn (FreelancerExperience $experience): array => [
                'id' => $experience->id,
                'domain_fa' => $experience->domain_fa,
                'domain_en' => $experience->domain_en,
                'position_fa' => $experience->position_fa,
                'position_en' => $experience->position_en,
                'organization_fa' => $experience->organization_fa,
                'organization_en' => $experience->organization_en,
                'years' => $experience->years,
                'description_fa' => $experience->description_fa,
                'description_en' => $experience->description_en,
                'skills_fa' => $experience->skills_fa ?? [],
                'skills_en' => $experience->skills_en ?? [],
            ])
            ->all();
    }

    private function localizedExperienceValue(FreelancerExperience $experience, string $field, string $locale): mixed
    {
        $value = $experience->getAttribute("{$field}_{$locale}");
        if ($value !== null && $value !== '' && $value !== []) {
            return $value;
        }

        return $experience->getAttribute("{$field}_".($locale === 'fa' ? 'en' : 'fa'));
    }
}
