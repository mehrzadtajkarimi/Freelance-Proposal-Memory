<?php

namespace Tests\Feature;

use App\Models\FreelancerProfile;
use App\Models\ProjectRequest;
use App\Models\ProposalVersion;
use App\Models\Rule;
use App\Models\WorkflowRun;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Support\CreatesMemoryRecords;
use Tests\TestCase;

final class WorkspacePagesTest extends TestCase
{
    use CreatesMemoryRecords;
    use RefreshDatabase;

    public function test_health_and_dashboard_use_real_saved_data(): void
    {
        FreelancerProfile::query()->create([
            'profile_key' => 'primary',
            'setup_completed_at' => now(),
        ]);

        $projectRequest = $this->createProjectRequest();
        $this->createProposalDraft($projectRequest);
        $this->createWorkflowEvent([
            'project_request_id' => $projectRequest->id,
            'event_type' => 'proposal.sent',
        ]);
        $this->createWorkflowRun(['project_request_id' => $projectRequest->id]);

        $pendingRule = Rule::query()->create([
            'scope' => 'global',
            'title' => 'Pending test rule',
            'status' => 'pending_approval',
            'current_version' => 1,
            'priority' => 20,
        ]);
        $pendingRule->versions()->create([
            'version' => 1,
            'body' => 'Pending test rule body.',
            'status' => 'pending_approval',
            'source_type' => 'automated_test',
            'created_at' => now(),
        ]);

        $this->get('/health')
            ->assertOk()
            ->assertJson(['status' => 'ready', 'database' => 'connected']);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertViewHas('requestCount', 1)
            ->assertViewHas('draftCount', 1)
            ->assertViewHas('submittedCount', 1)
            ->assertViewHas('pendingRuleCount', 1)
            ->assertViewHas('maxRunCount', 1);
    }

    public function test_request_list_applies_search_and_status_filters(): void
    {
        $this->createProjectRequest([
            'title' => 'Laravel website update',
            'status' => 'draft',
        ]);
        $this->createProjectRequest([
            'title' => 'WordPress maintenance',
            'status' => 'closed',
        ]);

        $this->get(route('requests.index', ['q' => 'Laravel', 'status' => 'draft']))
            ->assertOk()
            ->assertSee('Laravel website update')
            ->assertDontSee('WordPress maintenance')
            ->assertViewHas('filters', ['q' => 'Laravel', 'status' => 'draft']);
    }

    public function test_reports_apply_date_and_environment_filters_to_counts(): void
    {
        $this->travelTo(Carbon::parse('2026-06-15 12:00:00'));

        $selectedRequest = $this->createProjectRequest();
        $olderRequest = $this->createProjectRequest([
            'title' => 'Older request',
        ]);
        $olderRequest->forceFill([
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ])->saveQuietly();
        $this->createProposalDraft($selectedRequest);
        $olderProposal = $this->createProposalDraft($selectedRequest, ['version' => 2]);
        $olderProposal->forceFill(['created_at' => now()->subDay()])->saveQuietly();

        $this->createWorkflowRun([
            'project_request_id' => $selectedRequest->id,
            'run_type' => 'selected_run',
        ]);
        $this->createWorkflowRun([
            'run_type' => 'other_environment_run',
            'source_environment' => 'production',
        ]);
        $this->createWorkflowEvent([
            'project_request_id' => $selectedRequest->id,
            'event_type' => 'proposal.sent',
        ]);
        $this->createWorkflowEvent([
            'event_type' => 'proposal.sent',
            'source_environment' => 'production',
        ]);

        $this->get(route('reports.index', [
            'from' => '2026-06-15',
            'to' => '2026-06-15',
            'environment' => 'testing',
        ]))
            ->assertOk()
            ->assertViewHas('requestCount', 1)
            ->assertViewHas('draftCount', 1)
            ->assertViewHas('submittedCount', 1)
            ->assertViewHas('runCount', 1)
            ->assertViewHas('eventCount', 1);
    }

    public function test_locale_switch_keeps_safe_internal_return_paths_and_rejects_external_paths(): void
    {
        $this->post(route('locale.update', 'fa'), ['return_to' => '/settings/freelancer'])
            ->assertRedirect('/settings/freelancer')
            ->assertSessionHas('locale', 'fa');

        $this->post(route('locale.update', 'en'), ['return_to' => '//outside.example/path'])
            ->assertRedirect('/')
            ->assertSessionHas('locale', 'en');

        $this->post(route('locale.update', 'de'))
            ->assertNotFound();
    }

    public function test_rule_pages_render_and_index_filters_work(): void
    {
        $approved = Rule::query()->create([
            'scope' => 'global',
            'title' => 'Approved global rule',
            'status' => 'approved',
            'current_version' => 1,
            'priority' => 10,
            'approved_at' => now(),
        ]);
        $approved->versions()->create([
            'version' => 1,
            'body' => 'Approved rule content.',
            'status' => 'approved',
            'source_type' => 'automated_test',
            'approved_at' => now(),
            'created_at' => now(),
        ]);

        $pending = Rule::query()->create([
            'scope' => 'global',
            'title' => 'Pending global rule',
            'status' => 'pending_approval',
            'current_version' => 1,
            'priority' => 20,
        ]);
        $pending->versions()->create([
            'version' => 1,
            'body' => 'Pending rule content.',
            'status' => 'pending_approval',
            'source_type' => 'automated_test',
            'created_at' => now(),
        ]);

        $this->get(route('rules.index', ['status' => 'approved']))
            ->assertOk()
            ->assertSee('Approved global rule')
            ->assertDontSee('Pending global rule');
        $this->get(route('rules.create'))->assertOk();
        $this->get(route('rules.show', $approved))->assertOk();
        $this->get(route('rules.edit', $approved))->assertOk();
    }
}
