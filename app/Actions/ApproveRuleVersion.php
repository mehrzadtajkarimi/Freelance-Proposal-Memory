<?php

namespace App\Actions;

use App\Models\Rule;
use App\Models\RuleVersion;
use App\Services\WorkflowRecorder;
use Illuminate\Support\Facades\DB;

final class ApproveRuleVersion
{
    public function __construct(private readonly WorkflowRecorder $recorder) {}

    /** Call only after explicit user approval. */
    public function handle(RuleVersion $version, string $approvedBy): RuleVersion
    {
        return DB::transaction(function () use ($version, $approvedBy): RuleVersion {
            $version = RuleVersion::query()->lockForUpdate()->findOrFail($version->id);
            if ($version->status !== 'pending_approval') {
                throw new \InvalidArgumentException('Only a pending rule version can be approved.');
            }

            $rule = Rule::query()->lockForUpdate()->findOrFail($version->rule_id);
            $version->forceFill([
                'status' => 'approved',
                'approved_at' => now(),
            ])->save();

            $rule->forceFill([
                'status' => 'approved',
                'current_version' => $version->version,
                'approved_at' => now(),
            ])->save();

            $this->recorder->event(
                null,
                $rule->projectRequest,
                'rule.change_approved',
                'completed',
                'The user explicitly approved this rule version.',
                ['rule_id' => $version->rule_id, 'rule_version_id' => $version->id, 'version' => $version->version],
                $approvedBy,
            );

            return $version->refresh();
        });
    }
}
