<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class ProposalVersion extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'project_request_id',
        'version',
        'status',
        'body',
        'price_amount',
        'currency',
        'delivery_days',
        'delivery_unit',
        'payment_terms',
        'estimate',
        'assumptions',
        'submitted_at',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'price_amount' => 'decimal:4',
            'delivery_days' => 'integer',
            'payment_terms' => 'array',
            'estimate' => 'array',
            'submitted_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function projectRequest(): BelongsTo
    {
        return $this->belongsTo(ProjectRequest::class);
    }

    public function workflowRuns(): HasMany
    {
        return $this->hasMany(WorkflowRun::class);
    }
}
