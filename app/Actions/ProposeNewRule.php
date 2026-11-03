<?php

namespace App\Actions;

use App\Models\Rule;
use App\Services\WorkflowRecorder;
use Illuminate\Support\Facades\DB;

final class ProposeNewRule
{
    public function __construct(private readonly WorkflowRecorder $recorder) {}

    public function handle(string $title, string $body, string $scope = 'global', ?int $projectRequestId = null, string $proposedBy = 'agent'): Rule
    {
        if (! in_array($scope, ['global', 'project'], true)) {
            throw new \InvalidArgumentException('Rule scope must be global or project.');
        }
        if ($scope === 'project' && $projectRequestId === null) {
            throw new \InvalidArgumentException('Project-scoped rules require a project request ID.');
        }

        return DB::transaction(function () use ($title, $body, $scope, $projectRequestId, $proposedBy): Rule {
            $rule = Rule::query()->create([
                'project_request_id' => $projectRequestId,
                'scope' => $scope,
                'title' => $title,
                'status' => 'pending_approval',
                'current_version' => 1,
                'priority' => 100,
            ]);

            $version = $rule->versions()->create([
                'version' => 1,
                'body' => $body,
                'status' => 'pending_approval',
                'source_type' => 'user_change_request',
                'change_summary' => 'New rule proposed; awaiting explicit user approval.',
                'created_at' => now(),
            ]);

            $this->recorder->event(
                null,
                $rule->projectRequest,
                'rule.change_proposed',
                'pending_approval',
                'A new rule was recorded and is not active until the user approves it.',
                ['rule_id' => $rule->id, 'rule_version_id' => $version->id, 'version' => $version->version],
                $proposedBy,
            );

            return $rule->load('versions');
        });
    }
}
