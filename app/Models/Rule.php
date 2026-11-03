<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Rule extends Model
{
    protected $fillable = [
        'project_request_id',
        'legacy_key',
        'scope',
        'title',
        'status',
        'current_version',
        'priority',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'current_version' => 'integer',
            'approved_at' => 'datetime',
        ];
    }

    public function projectRequest(): BelongsTo
    {
        return $this->belongsTo(ProjectRequest::class);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(RuleVersion::class);
    }
}
