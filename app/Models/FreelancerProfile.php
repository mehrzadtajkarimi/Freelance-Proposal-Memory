<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
    ];

    protected function casts(): array
    {
        return [
            'experience_years' => 'integer',
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
}
