<?php

namespace Tests\Feature;

use App\Models\ProjectRequest;
use App\Models\ProposalVersion;
use App\Models\Rule;
use App\Services\JsonInputReader;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Tests\Support\CreatesMemoryRecords;
use Tests\TestCase;

final class MemoryCommandsTest extends TestCase
{
    use CreatesMemoryRecords;
    use RefreshDatabase;

    public function test_record_request_command_saves_sanitized_request_and_activity(): void
    {
        $this->provideJsonInput([
            'title' => 'Laravel implementation request',
            'body' => 'Please contact test@example.test about this work.',
            'platform' => 'test-platform',
            'metadata' => ['email' => 'test@example.test'],
        ]);

        $exitCode = Artisan::call('memory:record-request', ['--stdin-json' => true]);

        $this->assertSame(0, $exitCode);
        $projectRequest = ProjectRequest::query()->sole();
        $this->assertSame('Laravel implementation request', $projectRequest->title);
        $this->assertStringNotContainsString('test@example.test', $projectRequest->request_body);
        $this->assertStringNotContainsString('test@example.test', $projectRequest->metadata['email']);
        $this->assertDatabaseHas('workflow_events', [
            'project_request_id' => $projectRequest->id,
            'event_type' => 'request.recorded',
            'status' => 'completed',
        ]);
    }

    public function test_record_request_command_rejects_invalid_json_without_writing(): void
    {
        $this->provideJsonInput(null);

        $this->assertSame(2, Artisan::call('memory:record-request', ['--stdin-json' => true]));
        $this->assertDatabaseCount('project_requests', 0);
        $this->assertDatabaseCount('workflow_events', 0);
    }

    public function test_record_proposal_command_saves_a_draft_with_current_approved_rules(): void
    {
        $projectRequest = $this->createProjectRequest();
        $ruleVersion = $this->createApprovedRuleVersion();
        $this->provideJsonInput([
            'body' => 'A proposal draft for the saved request.',
            'key_idea' => 'Add a focused onboarding checklist tailored to the client workflow.',
            'price_amount' => '250.00',
            'currency' => 'usd',
            'content_locale' => 'fa',
            'delivery_days' => 7,
            'assumptions' => 'The client supplies content.',
            'rule_version_ids' => [$ruleVersion->id],
        ]);

        $exitCode = Artisan::call('memory:record-proposal', [
            'projectId' => $projectRequest->id,
            '--stdin-json' => true,
        ]);

        $this->assertSame(0, $exitCode);
        $proposal = ProposalVersion::query()->sole();
        $this->assertSame('draft', $proposal->status);
        $this->assertSame('fa', $proposal->content_locale);
        $this->assertSame('USD', $proposal->currency);
        $this->assertSame($projectRequest->id, $proposal->project_request_id);
        $this->assertTrue(
            $proposal->workflowRuns()->first()->ruleVersions()->whereKey($ruleVersion->id)->exists(),
        );
        $this->assertSame('draft', $projectRequest->fresh()->status);
    }

    public function test_record_proposal_command_rejects_an_unsupported_content_locale(): void
    {
        $projectRequest = $this->createProjectRequest();
        $this->provideJsonInput([
            'body' => 'A proposal draft.',
            'key_idea' => 'Use a compact implementation roadmap tailored to the request.',
            'price_amount' => '250.00',
            'currency' => 'USD',
            'content_locale' => 'de',
            'delivery_days' => 7,
            'assumptions' => 'A generic test assumption.',
        ]);

        $this->assertSame(2, Artisan::call('memory:record-proposal', [
            'projectId' => $projectRequest->id,
            '--stdin-json' => true,
        ]));
        $this->assertDatabaseCount('proposal_versions', 0);
    }

    public function test_record_message_command_saves_direction_and_sanitized_body(): void
    {
        $projectRequest = $this->createProjectRequest();
        $this->provideJsonInput([
            'body' => 'Please reply to test@example.test.',
            'metadata' => [
                'platform_message_id' => 'message-123',
                'private_note' => 'must not be persisted',
            ],
        ]);

        $exitCode = Artisan::call('memory:record-message', [
            'projectId' => $projectRequest->id,
            'direction' => 'incoming',
            '--stdin-json' => true,
        ]);

        $this->assertSame(0, $exitCode);
        $message = $projectRequest->messages()->sole();
        $this->assertSame('incoming', $message->direction);
        $this->assertStringNotContainsString('test@example.test', $message->body);
        $this->assertSame(['platform_message_id' => 'message-123'], $message->metadata);
        $this->assertDatabaseHas('workflow_events', [
            'project_request_id' => $projectRequest->id,
            'event_type' => 'message.recorded',
        ]);
    }

    public function test_add_rule_and_propose_change_commands_keep_versions_pending(): void
    {
        $this->provideJsonInput([
            'title' => 'Proposal wording',
            'body' => 'Use direct language.',
            'scope' => 'global',
        ]);

        $this->assertSame(0, Artisan::call('memory:add-rule', ['--stdin-json' => true]));
        $rule = Rule::query()->sole();
        $this->assertSame('pending_approval', $rule->status);
        $this->assertSame('pending_approval', $rule->versions()->sole()->status);

        $this->provideJsonInput([
            'body' => 'Use concise and direct language.',
            'change_summary' => 'Clarify the original guidance.',
        ]);

        $this->assertSame(0, Artisan::call('memory:propose-rule-change', [
            'ruleId' => $rule->id,
            '--stdin-json' => true,
        ]));

        $this->assertSame(2, $rule->versions()->where('version', 2)->sole()->version);
        $this->assertSame('pending_approval', $rule->versions()->where('version', 2)->sole()->status);
        $this->assertSame(1, $rule->fresh()->current_version);
    }

    public function test_search_command_accepts_json_and_returns_exact_title_matches(): void
    {
        $this->createProjectRequest([
            'title' => 'Laravel proposal workflow',
            'request_body' => 'Build a proposal memory tool.',
        ]);
        $this->provideJsonInput([
            'title' => 'Laravel proposal workflow',
            'text' => 'Laravel proposal',
        ]);

        $this->assertSame(0, Artisan::call('memory:search', ['--stdin-json' => true]));
        $output = Artisan::output();

        $this->assertStringContainsString('"result":"exact_title"', $output);
        $this->assertStringContainsString('Laravel proposal workflow', $output);
    }

    public function test_search_command_returns_text_candidates_and_scopes_project_rules(): void
    {
        $projectRequest = $this->createProjectRequest([
            'title' => 'Project for performance review',
            'request_body' => 'Review performance in this project.',
        ]);

        $globalVersion = $this->createApprovedRuleVersion();
        $globalVersion->rule->update(['title' => 'Global performance guidance']);
        $globalVersion->update(['body' => 'Apply global performance guidance.']);

        $projectVersion = $this->createApprovedRuleVersion($projectRequest);
        $projectVersion->rule->update(['title' => 'Project performance guidance']);
        $projectVersion->update(['body' => 'Apply project performance guidance.']);

        $this->provideJsonInput(['text' => 'performance']);
        $this->assertSame(0, Artisan::call('memory:search', [
            '--stdin-json' => true,
            '--project-id' => $projectRequest->id,
        ]));
        $projectOutput = Artisan::output();

        $this->assertStringContainsString('"result":"text_candidates"', $projectOutput);
        $this->assertStringContainsString('Project for performance review', $projectOutput);
        $this->assertStringContainsString('Apply global performance guidance.', $projectOutput);
        $this->assertStringContainsString('Apply project performance guidance.', $projectOutput);

        $this->provideJsonInput(['text' => 'performance']);
        $this->assertSame(0, Artisan::call('memory:search', ['--stdin-json' => true]));
        $globalOutput = Artisan::output();

        $this->assertStringContainsString('Apply global performance guidance.', $globalOutput);
        $this->assertStringNotContainsString('Apply project performance guidance.', $globalOutput);
    }

    public function test_context_command_includes_only_the_selected_project_history_and_rules(): void
    {
        $projectRequest = $this->createProjectRequest([
            'title' => 'Selected project context fixture',
            'request_body' => 'The selected project request body.',
        ]);
        $proposal = $this->createProposalDraft($projectRequest, [
            'body' => 'The selected project proposal draft.',
        ]);

        $globalVersion = $this->createApprovedRuleVersion();
        $globalVersion->rule->update(['title' => 'Global context rule']);
        $globalVersion->update(['body' => 'Global context rule body.']);

        $projectVersion = $this->createApprovedRuleVersion($projectRequest);
        $projectVersion->rule->update(['title' => 'Selected project rule']);
        $projectVersion->update(['body' => 'Selected project rule body.']);

        Artisan::call('memory:context', ['--locale' => 'en']);
        $globalContext = Artisan::output();

        $this->assertStringContainsString('Global context rule', $globalContext);
        $this->assertStringNotContainsString('Selected project rule', $globalContext);
        $this->assertStringNotContainsString('Selected project context fixture', $globalContext);

        Artisan::call('memory:context', [
            '--project-id' => $projectRequest->id,
            '--locale' => 'en',
        ]);
        $projectContext = Artisan::output();

        $this->assertStringContainsString('PROJECT HISTORY', $projectContext);
        $this->assertStringContainsString('Selected project context fixture', $projectContext);
        $this->assertStringContainsString('The selected project request body.', $projectContext);
        $this->assertStringContainsString('The selected project proposal draft.', $projectContext);
        $this->assertStringContainsString('Selected project rule body.', $projectContext);
        $this->assertStringContainsString('PROPOSAL TASK', $projectContext);
        $this->assertStringContainsString('Write all client-facing text in English.', $projectContext);
        $this->assertSame($projectRequest->id, $proposal->project_request_id);

        Artisan::call('memory:context', [
            '--project-id' => $projectRequest->id,
            '--locale' => 'fa',
        ]);
        $persianProjectContext = Artisan::output();
        $this->assertStringContainsString('Write all client-facing text in Persian.', $persianProjectContext);
    }

    public function test_report_command_counts_only_confirmed_submissions(): void
    {
        $projectRequest = $this->createProjectRequest();
        $this->createProposalDraft($projectRequest);
        $this->createWorkflowRun(['project_request_id' => $projectRequest->id]);
        $this->createWorkflowEvent([
            'project_request_id' => $projectRequest->id,
            'event_type' => 'proposal.drafted',
        ]);
        $this->createWorkflowEvent([
            'project_request_id' => $projectRequest->id,
            'event_type' => 'proposal.sent',
            'status' => 'completed',
        ]);

        $this->assertSame(0, Artisan::call('memory:report', ['--environment' => 'testing']));
        $output = Artisan::output();

        $this->assertStringContainsString('Draft proposal versions in selected dates (all environments): 1', $output);
        $this->assertStringContainsString('Confirmed submissions in range: 1', $output);
    }

    public function test_export_rules_command_exports_approved_global_versions_only(): void
    {
        $approved = $this->createApprovedRuleVersion();
        $approved->rule->forceFill(['title' => 'Approved global fixture'])->save();

        $pendingRule = Rule::query()->create([
            'scope' => 'global',
            'title' => 'Pending global fixture',
            'status' => 'pending_approval',
            'current_version' => 1,
            'priority' => 20,
        ]);
        $pendingRule->versions()->create([
            'version' => 1,
            'body' => 'Pending private test content.',
            'status' => 'pending_approval',
            'source_type' => 'automated_test',
            'created_at' => now(),
        ]);

        $projectRequest = $this->createProjectRequest();
        $projectVersion = $this->createApprovedRuleVersion($projectRequest);
        $projectVersion->update(['body' => 'Project-scoped test content.']);

        $path = sys_get_temp_dir().'/approved-rules-'.Str::uuid().'.md';

        try {
            $this->assertSame(0, Artisan::call('memory:export-rules', ['--path' => $path]));
            $contents = file_get_contents($path);

            $this->assertIsString($contents);
            $this->assertStringContainsString('Approved rule body for automated tests.', $contents);
            $this->assertStringNotContainsString('Pending private test content.', $contents);
            $this->assertStringNotContainsString('Project-scoped test content.', $contents);
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    public function test_confirmation_commands_update_submission_and_rule_statuses(): void
    {
        $projectRequest = $this->createProjectRequest();
        $proposal = $this->createProposalDraft($projectRequest);

        $this->assertSame(0, Artisan::call('memory:mark-submitted', [
            'proposalId' => $proposal->id,
            'confirmedBy' => 'Test User',
        ]));
        $this->assertSame('submitted', $proposal->fresh()->status);
        $this->assertDatabaseHas('workflow_events', [
            'event_type' => 'proposal.sent',
            'actor' => 'Test User',
            'status' => 'completed',
        ]);

        $rule = Rule::query()->create([
            'scope' => 'global',
            'title' => 'Pending approval command fixture',
            'status' => 'pending_approval',
            'current_version' => 1,
            'priority' => 30,
        ]);
        $version = $rule->versions()->create([
            'version' => 1,
            'body' => 'Pending rule body.',
            'status' => 'pending_approval',
            'source_type' => 'automated_test',
            'created_at' => now(),
        ]);

        $this->assertSame(0, Artisan::call('memory:approve-rule', [
            'versionId' => $version->id,
            'approvedBy' => 'Test User',
        ]));
        $this->assertSame('approved', $rule->fresh()->status);
        $this->assertSame('approved', $version->fresh()->status);
    }

    private function provideJsonInput(mixed $input): void
    {
        $reader = \Mockery::mock(JsonInputReader::class);
        $reader->shouldReceive('decode')->once()->andReturn($input);
        $this->app->instance(JsonInputReader::class, $reader);
    }
}
