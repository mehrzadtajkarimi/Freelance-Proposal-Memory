<?php

namespace Tests\Feature;

use App\Models\PaymentStageRule;
use App\Models\Rule;
use App\Services\AgentRulePackage;
use App\Services\PaymentStageRuleMatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\Support\CreatesMemoryRecords;
use Tests\TestCase;

final class AgentRulePackageTest extends TestCase
{
    use CreatesMemoryRecords;
    use RefreshDatabase;

    public function test_export_contains_only_global_rules_and_payment_release_conditions(): void
    {
        $globalVersion = $this->createApprovedRuleVersion();
        $globalVersion->rule->update(['legacy_key' => 'global_rule']);
        $projectRequest = $this->createProjectRequest();
        $this->createApprovedRuleVersion($projectRequest);
        PaymentStageRule::query()->create([
            'name_fa' => '۲۰ میلیون تومان و بیشتر',
            'name_en' => '20 million tomans and above',
            'minimum_amount_toman' => 20000000,
            'maximum_amount_toman' => null,
            'stages' => $this->releaseStages(),
            'status' => 'approved',
            'approved_at' => now(),
        ]);

        $package = app(AgentRulePackage::class)->export();

        $this->assertSame('freelance-proposal-memory-rules', $package['format']);
        $this->assertSame(['format', 'version', 'exported_at', 'rules', 'payment_stage_rules'], array_keys($package));
        $this->assertCount(1, $package['rules']);
        $this->assertSame('global_rule', $package['rules'][0]['key']);
        $this->assertCount(1, $package['payment_stage_rules']);
        $this->assertSame(20000000, $package['payment_stage_rules'][0]['minimum_amount_toman']);
    }

    public function test_replace_archives_absent_rules_and_keeps_imported_rules_inactive_until_approval(): void
    {
        $oldGlobal = $this->createApprovedRuleVersion();
        $oldGlobal->rule->update(['legacy_key' => 'existing_global']);
        $projectRequest = $this->createProjectRequest();
        $oldProject = $this->createApprovedRuleVersion($projectRequest);
        PaymentStageRule::query()->create([
            'name_fa' => 'قدیمی',
            'name_en' => 'Old rule',
            'maximum_amount_toman' => 19999999,
            'stages' => $this->releaseStages(50, 50),
            'status' => 'approved',
            'approved_at' => now(),
        ]);

        $summary = app(AgentRulePackage::class)->replace($this->package());

        $this->assertSame(2, $summary['rules_imported']);
        $this->assertSame(1, $summary['rules_reused']);
        $this->assertSame(1, $summary['rules_archived']);
        $this->assertSame(1, $summary['payment_stage_rules_imported']);
        $this->assertSame('pending_approval', $oldGlobal->rule->fresh()->status);
        $this->assertSame(2, $oldGlobal->rule->fresh()->current_version);
        $this->assertSame('pending_approval', $oldGlobal->rule->versions()->where('version', 2)->sole()->status);
        $this->assertSame('archived', $oldProject->rule->fresh()->status);
        $this->assertDatabaseCount('payment_stage_rules', 1);
        $this->assertDatabaseHas('payment_stage_rules', [
            'minimum_amount_toman' => 20000000,
            'status' => 'pending_approval',
        ]);
        $this->assertNull(app(PaymentStageRuleMatcher::class)->termsFor('20000000', 'IRT', 'fa'));

        $exported = app(AgentRulePackage::class)->export();
        $this->assertSame('Updated reusable rule body.', $exported['rules'][0]['body']);
        $this->assertSame(2, count($exported['rules']));
        $this->assertSame('pending_approval', PaymentStageRule::query()->sole()->status);
    }

    public function test_validation_rejects_stage_percentages_that_do_not_total_one_hundred(): void
    {
        $package = $this->package();
        $package['payment_stage_rules'][0]['stages'][2]['percentage'] = 25;

        $this->expectException(ValidationException::class);
        app(AgentRulePackage::class)->validateContents(json_encode($package, JSON_THROW_ON_ERROR));
    }

    /** @return array<string, mixed> */
    private function package(): array
    {
        return [
            'format' => 'freelance-proposal-memory-rules',
            'version' => 1,
            'exported_at' => '2026-11-03T12:00:00+00:00',
            'rules' => [
                [
                    'key' => 'existing_global',
                    'title' => 'Updated test rule',
                    'body' => 'Updated reusable rule body.',
                    'priority' => 10,
                ],
                [
                    'key' => 'new_rule',
                    'title' => 'New test rule',
                    'body' => 'New reusable rule body.',
                    'priority' => 20,
                ],
            ],
            'payment_stage_rules' => [[
                'key' => 'release_20m_plus',
                'name_fa' => '۲۰ میلیون تومان و بیشتر',
                'name_en' => '20 million tomans and above',
                'minimum_amount_toman' => 20000000,
                'maximum_amount_toman' => null,
                'stages' => $this->releaseStages(),
            ]],
        ];
    }

    /** @return array<int, array<string, int|string>> */
    private function releaseStages(int $first = 30, int $second = 40, int $third = 30): array
    {
        return [
            [
                'position' => 1,
                'percentage' => $first,
                'title_fa' => 'آزادسازی پس از شروع پروژه',
                'title_en' => 'Release after project start',
                'description_fa' => "{$first}٪ پس از شروع پروژه برای مهرزاد آزاد شود.",
                'description_en' => "{$first}% is released to the freelancer after work starts.",
            ],
            [
                'position' => 2,
                'percentage' => $second,
                'title_fa' => 'آزادسازی پس از نسخهٔ قابل بررسی',
                'title_en' => 'Release after reviewable version',
                'description_fa' => "{$second}٪ پس از تکمیل بخش‌های اصلی و ارائهٔ نسخهٔ قابل بررسی آزاد شود.",
                'description_en' => "{$second}% is released after the main work is complete and a reviewable version is provided.",
            ],
            [
                'position' => 3,
                'percentage' => $third,
                'title_fa' => 'آزادسازی پس از تحویل نهایی',
                'title_en' => 'Release after final handoff',
                'description_fa' => "{$third}٪ پس از بررسی نهایی، رفع ایرادهای توافق‌شده و تحویل آزاد شود.",
                'description_en' => "{$third}% is released after final review, agreed fixes, and delivery.",
            ],
        ];
    }
}
