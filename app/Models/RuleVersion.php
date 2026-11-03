<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class RuleVersion extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'rule_id',
        'version',
        'title',
        'priority',
        'body',
        'status',
        'source_type',
        'source_reference',
        'change_summary',
        'approved_at',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'priority' => 'integer',
            'approved_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(Rule::class);
    }
}
