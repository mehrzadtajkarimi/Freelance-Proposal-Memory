<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class ProjectRequest extends Model
{
    protected $fillable = [
        'legacy_document_id',
        'platform',
        'external_id',
        'title',
        'request_body',
        'status',
        'budget_min_amount',
        'budget_max_amount',
        'currency',
        'client_deadline_days',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'budget_min_amount' => 'decimal:4',
            'budget_max_amount' => 'decimal:4',
            'metadata' => 'array',
        ];
    }

    public function legacyDocument(): BelongsTo
    {
        return $this->belongsTo(LegacyDocument::class);
    }

    public function proposals(): HasMany
    {
        return $this->hasMany(ProposalVersion::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ConversationMessage::class);
    }

    public function workflowRuns(): HasMany
    {
        return $this->hasMany(WorkflowRun::class);
    }

    public function workflowEvents(): HasMany
    {
        return $this->hasMany(WorkflowEvent::class);
    }
}
