<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class FreelancerExperience extends Model
{
    protected $fillable = [
        'import_key',
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

    protected function casts(): array
    {
        return [
            'years' => 'integer',
            'skills_fa' => 'array',
            'skills_en' => 'array',
        ];
    }

    public function freelancerProfile(): BelongsTo
    {
        return $this->belongsTo(FreelancerProfile::class);
    }
}
