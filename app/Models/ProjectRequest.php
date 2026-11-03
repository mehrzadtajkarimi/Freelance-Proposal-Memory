<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

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

    public function scopeInNegotiation(Builder $query): Builder
    {
        $negotiationResponses = DB::table('conversation_messages as negotiation_messages')
            ->select('negotiation_messages.project_request_id')
            ->selectRaw('MIN(COALESCE(negotiation_messages.occurred_at, negotiation_messages.created_at)) AS negotiation_started_at')
            ->where('negotiation_messages.direction', 'incoming')
            ->whereExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('proposal_versions as submitted_proposals')
                    ->whereColumn('submitted_proposals.project_request_id', 'negotiation_messages.project_request_id')
                    ->where('submitted_proposals.status', 'submitted')
                    ->whereNotNull('submitted_proposals.submitted_at')
                    ->whereRaw('submitted_proposals.submitted_at < COALESCE(negotiation_messages.occurred_at, negotiation_messages.created_at)');
            })
            ->groupBy('negotiation_messages.project_request_id');

        return $query
            ->joinSub($negotiationResponses, 'negotiation_activity', function ($join): void {
                $join->on('project_requests.id', '=', 'negotiation_activity.project_request_id');
            })
            ->select(['project_requests.*', 'negotiation_activity.negotiation_started_at']);
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
