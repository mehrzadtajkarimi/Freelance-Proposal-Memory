<?php

namespace Tests\Support;

use App\Models\ProjectRequest;
use App\Models\ProposalVersion;
use App\Models\Rule;
use App\Models\RuleVersion;
use App\Models\WorkflowEvent;
use App\Models\WorkflowRun;
use Illuminate\Support\Str;

trait CreatesMemoryRecords
{
    protected function createProjectRequest(array $attributes = []): ProjectRequest
    {
        return ProjectRequest::query()->create(array_replace([
            'platform' => 'test-platform',
            'title' => 'Sample project request',
            'request_body' => 'A generic request used by automated tests.',
            'status' => 'draft',
        ], $attributes));
    }

    protected function createApprovedRuleVersion(?ProjectRequest $projectRequest = null): RuleVersion
    {
        $rule = Rule::query()->create([
            'project_request_id' => $projectRequest?->id,
            'scope' => $projectRequest ? 'project' : 'global',
            'title' => 'Approved test rule',
            'status' => 'approved',
            'current_version' => 1,
            'priority' => 10,
            'approved_at' => now(),
        ]);

        return $rule->versions()->create([
            'version' => 1,
            'body' => 'Approved rule body for automated tests.',
            'status' => 'approved',
            'source_type' => 'automated_test',
            'change_summary' => 'Test fixture.',
            'approved_at' => now(),
            'created_at' => now(),
        ]);
    }

    protected function createProposalDraft(ProjectRequest $projectRequest, array $attributes = []): ProposalVersion
    {
        return $projectRequest->proposals()->create(array_replace([
            'version' => 1,
            'status' => 'draft',
            'content_locale' => 'en',
            'body' => 'Sample proposal draft.',
            'price_amount' => '100.00',
            'currency' => 'USD',
            'delivery_days' => 5,
            'delivery_unit' => 'calendar_day',
            'payment_terms' => [],
            'estimate' => [],
            'assumptions' => 'Generic test assumption.',
            'created_at' => now(),
        ], $attributes));
    }

    protected function createWorkflowRun(array $attributes = []): WorkflowRun
    {
        return WorkflowRun::query()->create(array_replace([
            'run_key' => (string) Str::uuid(),
            'run_type' => 'test_run',
            'status' => 'succeeded',
            'source_environment' => 'testing',
            'started_at' => now(),
            'finished_at' => now(),
        ], $attributes));
    }

    protected function createWorkflowEvent(array $attributes = []): WorkflowEvent
    {
        return WorkflowEvent::query()->create(array_replace([
            'event_type' => 'test.event',
            'status' => 'completed',
            'source_environment' => 'testing',
            'occurred_at' => now(),
        ], $attributes));
    }
}
