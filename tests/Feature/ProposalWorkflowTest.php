<?php

namespace Tests\Feature;

use App\Actions\CreateProposalDraft;
use App\Actions\MarkProposalAsSubmitted;
use App\Models\ProposalVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesMemoryRecords;
use Tests\TestCase;

final class ProposalWorkflowTest extends TestCase
{
    use CreatesMemoryRecords;
    use RefreshDatabase;

    public function test_creating_a_draft_records_workflow_and_keeps_request_unsubmitted(): void
    {
        $projectRequest = $this->createProjectRequest();
        $ruleVersion = $this->createApprovedRuleVersion();

        $proposal = app(CreateProposalDraft::class)->handle(
            $projectRequest,
            'A sample client-facing proposal.',
            '125.00',
            'USD',
            5,
            ['milestone' => 'On approval'],
            ['design' => 2, 'development' => 3],
            'Content and access will be provided.',
            [$ruleVersion],
            'A short onboarding checklist will surface missing content before implementation.',
            'automated-test',
            'fa',
        );

        $this->assertSame('draft', $proposal->status);
        $this->assertSame('fa', $proposal->content_locale);
        $this->assertNull($proposal->submitted_at);
        $this->assertSame('draft', $projectRequest->fresh()->status);
        $this->assertDatabaseHas('workflow_runs', [
            'proposal_version_id' => $proposal->id,
            'status' => 'succeeded',
        ]);
        $this->assertDatabaseHas('workflow_events', [
            'project_request_id' => $projectRequest->id,
            'event_type' => 'proposal.drafted',
            'status' => 'completed',
        ]);
        $this->assertTrue(
            $proposal->workflowRuns()->first()->ruleVersions()->whereKey($ruleVersion->id)->exists(),
        );
    }

    public function test_submission_requires_a_draft_and_records_user_confirmation(): void
    {
        $projectRequest = $this->createProjectRequest();
        $proposal = $this->createProposalDraft($projectRequest);

        $submitted = app(MarkProposalAsSubmitted::class)->handle($proposal, 'Test User');

        $this->assertSame('submitted', $submitted->status);
        $this->assertNotNull($submitted->submitted_at);
        $this->assertSame('submitted', $projectRequest->fresh()->status);
        $this->assertDatabaseHas('workflow_events', [
            'project_request_id' => $projectRequest->id,
            'event_type' => 'proposal.sent',
            'status' => 'completed',
            'actor' => 'Test User',
        ]);
    }

    public function test_submitted_proposal_cannot_be_submitted_again(): void
    {
        $projectRequest = $this->createProjectRequest(['status' => 'submitted']);
        $proposal = $this->createProposalDraft($projectRequest, [
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        try {
            app(MarkProposalAsSubmitted::class)->handle($proposal, 'Test User');
            $this->fail('A submitted proposal should not be submitted a second time.');
        } catch (\InvalidArgumentException $exception) {
            $this->assertSame('Only an unsent draft proposal can be marked as submitted.', $exception->getMessage());
        }

        $this->assertSame('submitted', $proposal->fresh()->status);
        $this->assertDatabaseCount('workflow_events', 0);
    }

    public function test_invalid_draft_pricing_is_rejected_without_creating_a_proposal(): void
    {
        $projectRequest = $this->createProjectRequest();

        try {
            app(CreateProposalDraft::class)->handle(
                $projectRequest,
                'A proposal draft.',
                '0',
                'USD',
                5,
                [],
                [],
                'Test assumption.',
                [],
                'A clear implementation checkpoint plan for the request.',
            );
            $this->fail('A zero-price proposal must be rejected.');
        } catch (\InvalidArgumentException $exception) {
            $this->assertSame('A proposal draft requires a positive price and delivery duration.', $exception->getMessage());
        }

        $this->assertDatabaseCount('proposal_versions', 0);
        $this->assertDatabaseCount('workflow_runs', 0);
    }

    public function test_draft_rejects_an_approved_rule_version_that_is_no_longer_active(): void
    {
        $projectRequest = $this->createProjectRequest();
        $staleVersion = $this->createApprovedRuleVersion();
        $staleVersion->rule->forceFill(['current_version' => 2])->save();

        try {
            app(CreateProposalDraft::class)->handle(
                $projectRequest,
                'A proposal draft using stale guidance.',
                '125.00',
                'USD',
                5,
                [],
                [],
                'Test assumption.',
                [$staleVersion],
                'A review checklist matched to the client workflow.',
            );
            $this->fail('A stale rule version must not be attached to a proposal run.');
        } catch (\InvalidArgumentException $exception) {
            $this->assertSame(
                'Only the currently active, approved rule version can be attached to a proposal run.',
                $exception->getMessage(),
            );
        }

        $this->assertDatabaseCount('proposal_versions', 0);
        $this->assertDatabaseCount('workflow_runs', 0);
    }
}
