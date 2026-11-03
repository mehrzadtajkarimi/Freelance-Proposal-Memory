<?php

namespace App\Console\Commands;

use App\Actions\ApproveRuleVersion;
use App\Models\RuleVersion;
use Illuminate\Console\Command;

final class MemoryApproveRuleCommand extends Command
{
    protected $signature = 'memory:approve-rule {versionId : Pending rule version ID} {approvedBy : User who explicitly approved the change}';

    protected $description = 'Activate a pending rule version after explicit user approval';

    public function handle(ApproveRuleVersion $action): int
    {
        $version = RuleVersion::query()->find($this->argument('versionId'));
        if (! $version) {
            $this->error('Rule version was not found.');

            return self::FAILURE;
        }

        $approved = $action->handle($version, (string) $this->argument('approvedBy'));
        $this->info(json_encode(['rule_id' => $approved->rule_id, 'version' => $approved->version, 'status' => $approved->status], JSON_UNESCAPED_UNICODE));

        return self::SUCCESS;
    }
}
