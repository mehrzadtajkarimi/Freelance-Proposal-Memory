<?php

namespace App\Services;

use App\Models\ProjectRequest;
use App\Models\ProposalVersion;
use App\Models\RuleVersion;
use App\Models\WorkflowEvent;
use App\Models\WorkflowRun;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class WorkflowRecorder
{
    public function __construct(private readonly ContentSanitizer $sanitizer) {}

    /** @param array<int, RuleVersion> $ruleVersions */
    public function start(
        string $runType,
        ?ProjectRequest $projectRequest = null,
        ?string $actor = null,
        array $context = [],
        array $ruleVersions = [],
        ?ProposalVersion $proposalVersion = null,
    ): WorkflowRun {
        return DB::transaction(function () use ($runType, $projectRequest, $actor, $context, $ruleVersions, $proposalVersion): WorkflowRun {
            $run = WorkflowRun::query()->create([
                'run_key' => (string) Str::uuid(),
                'project_request_id' => $projectRequest?->id,
                'proposal_version_id' => $proposalVersion?->id,
                'run_type' => $runType,
                'status' => 'running',
                'source_environment' => app()->environment(),
                'actor' => $actor !== null ? $this->sanitizer->redact($actor) : null,
                'started_at' => now(),
                'context' => $this->sanitizer->redactMetadata($context),
            ]);

            if ($ruleVersions !== []) {
                $run->ruleVersions()->sync(collect($ruleVersions)->map(fn (RuleVersion $version): int => $version->id)->all());
            }

            $this->event($run, $projectRequest, $runType.'.started', 'running', 'Workflow started.');

            return $run;
        });
    }

    public function event(
        ?WorkflowRun $run,
        ?ProjectRequest $projectRequest,
        string $eventType,
        ?string $status = null,
        ?string $message = null,
        array $context = [],
        ?string $actor = null,
    ): WorkflowEvent {
        return WorkflowEvent::query()->create([
            'workflow_run_id' => $run?->id,
            'project_request_id' => $projectRequest?->id,
            'event_type' => $eventType,
            'status' => $status,
            'source_environment' => app()->environment(),
            'actor' => $actor !== null ? $this->sanitizer->redact($actor) : null,
            'message' => $message !== null ? $this->sanitizer->redact($message) : null,
            'context' => $this->sanitizer->redactMetadata($context),
            'occurred_at' => now(),
        ]);
    }

    public function finish(WorkflowRun $run, string $status, ?string $summary = null, array $context = []): WorkflowRun
    {
        return DB::transaction(function () use ($run, $status, $summary, $context): WorkflowRun {
            $run->forceFill([
                'status' => $status,
                'finished_at' => now(),
                'result_summary' => $summary !== null ? $this->sanitizer->redact($summary) : null,
                'context' => $this->sanitizer->redactMetadata(array_merge($run->context ?? [], $context)),
            ])->save();

            $this->event(
                $run,
                $run->projectRequest,
                $run->run_type.'.finished',
                $status,
                $summary,
            );

            return $run->refresh();
        });
    }
}
