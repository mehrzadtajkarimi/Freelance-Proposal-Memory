<?php

namespace Tests\Feature;

use App\Models\Rule;
use App\Models\RuleVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesMemoryRecords;
use Tests\TestCase;

final class RuleWorkflowTest extends TestCase
{
    use CreatesMemoryRecords;
    use RefreshDatabase;

    public function test_new_rule_stays_pending_until_explicit_approval(): void
    {
        $response = $this->post(route('rules.store'), [
            'title' => 'Proposal tone',
            'body' => 'Use clear and concise language.',
            'scope' => 'global',
        ]);

        $rule = Rule::query()->sole();
        $version = $rule->versions()->sole();

        $response->assertRedirect(route('rules.show', $rule));
        $this->assertSame('pending_approval', $rule->status);
        $this->assertSame('pending_approval', $version->status);

        $this->post(route('rule-versions.approve', $version))
            ->assertRedirect(route('rules.show', $rule))
            ->assertSessionHas('success');

        $this->assertSame('approved', $rule->fresh()->status);
        $this->assertSame('approved', $version->fresh()->status);
        $this->assertDatabaseHas('workflow_events', [
            'event_type' => 'rule.change_approved',
            'status' => 'completed',
        ]);
    }

    public function test_rule_change_does_not_replace_active_version_until_approval(): void
    {
        $activeVersion = $this->createApprovedRuleVersion();
        $rule = $activeVersion->rule;

        $this->put(route('rules.update', $rule), [
            'body' => 'Use a concise, client-specific approach.',
            'change_summary' => 'Clarify the writing guidance.',
        ])->assertRedirect(route('rules.show', $rule));

        $pendingVersion = $rule->versions()->where('version', 2)->sole();
        $this->assertSame('approved', $rule->fresh()->status);
        $this->assertSame(1, $rule->fresh()->current_version);
        $this->assertSame('pending_approval', $pendingVersion->status);
        $this->assertSame('approved', $activeVersion->fresh()->status);

        $this->post(route('rule-versions.approve', $pendingVersion))
            ->assertRedirect(route('rules.show', $rule));

        $this->assertSame(2, $rule->fresh()->current_version);
        $this->assertSame('approved', $pendingVersion->fresh()->status);
    }

    public function test_project_rule_requires_a_saved_project_request(): void
    {
        $this->from(route('rules.create'))
            ->post(route('rules.store'), [
                'title' => 'Project-specific rule',
                'body' => 'Keep this guidance scoped.',
                'scope' => 'project',
            ])
            ->assertRedirect(route('rules.create'))
            ->assertSessionHasErrors('project_request_id');

        $this->assertDatabaseCount('rules', 0);
    }

    public function test_approved_rule_version_cannot_be_approved_again(): void
    {
        $version = $this->createApprovedRuleVersion();

        $this->post(route('rule-versions.approve', $version))
            ->assertRedirect(route('rules.show', $version->rule_id))
            ->assertSessionHasErrors('version');
    }
}
