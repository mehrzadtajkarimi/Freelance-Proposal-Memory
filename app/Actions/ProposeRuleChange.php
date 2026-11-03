<?php

namespace App\Actions;

use App\Models\Rule;
use App\Models\RuleVersion;
use App\Services\WorkflowRecorder;
use Illuminate\Support\Facades\DB;

final class ProposeRuleChange
{
    public function __construct(private readonly WorkflowRecorder $recorder) {}

    public function handle(Rule $rule, string $body, string $changeSummary, string $proposedBy): RuleVersion
    {
        return DB::transaction(function () use ($rule, $body, $changeSummary, $proposedBy): RuleVersion {
            $rule = Rule::query()->lockForUpdate()->findOrFail($rule->id);
            $versionNumber = ((int) $rule->versions()->max('version')) + 1;
            $version = $rule->versions()->create([
                'version' => $versionNumber,
                'title' => $rule->title,
                'priority' => $rule->priority,
                'body' => $body,
                'status' => 'pending_approval',
                'source_type' => 'user_change_request',
                'change_summary' => $changeSummary,
                'created_at' => now(),
            ]);

            $this->recorder->event(
                null,
                $rule->projectRequest,
                'rule.change_proposed',
                'pending_approval',
                'A rule change was recorded and is not active until the user approves it.',
                ['rule_id' => $rule->id, 'rule_version_id' => $version->id, 'version' => $versionNumber],
                $proposedBy,
            );

            return $version;
        });
    }
}
