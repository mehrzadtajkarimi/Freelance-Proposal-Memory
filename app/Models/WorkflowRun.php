<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class WorkflowRun extends Model
{
    protected $fillable = [
        'run_key',
        'project_request_id',
        'proposal_version_id',
        'run_type',
        'status',
        'source_environment',
        'actor',
        'started_at',
        'finished_at',
        'result_summary',
        'context',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'context' => 'array',
        ];
    }

    public function projectRequest(): BelongsTo
    {
        return $this->belongsTo(ProjectRequest::class);
    }

    public function proposalVersion(): BelongsTo
    {
        return $this->belongsTo(ProposalVersion::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(WorkflowEvent::class);
    }

    public function ruleVersions(): BelongsToMany
    {
        return $this->belongsToMany(RuleVersion::class, 'workflow_run_rule_versions');
    }
}
