<?php

namespace App\Console\Commands;

use App\Actions\ProposeRuleChange;
use App\Models\Rule;
use App\Services\ContentSanitizer;
use App\Services\JsonInputReader;
use Illuminate\Console\Command;

final class MemoryProposeRuleChangeCommand extends Command
{
    protected $signature = 'memory:propose-rule-change {ruleId : Existing rule ID} {--stdin-json : Read body and change_summary from standard input}';

    protected $description = 'Save a proposed rule version as pending approval';

    public function handle(ProposeRuleChange $action, ContentSanitizer $sanitizer, JsonInputReader $inputReader): int
    {
        if (! $this->option('stdin-json')) {
            $this->error('Pass --stdin-json and provide body and change_summary fields.');

            return self::INVALID;
        }

        $input = $inputReader->decode();
        if (! is_array($input) || trim((string) ($input['body'] ?? '')) === '' || trim((string) ($input['change_summary'] ?? '')) === '') {
            $this->error('JSON must include non-empty body and change_summary fields.');

            return self::INVALID;
        }

        $rule = Rule::query()->find($this->argument('ruleId'));
        if (! $rule) {
            $this->error('Rule was not found.');

            return self::FAILURE;
        }

        $version = $action->handle(
            $rule,
            $sanitizer->redact(trim($input['body'])),
            $sanitizer->redact(trim($input['change_summary'])),
            $sanitizer->redact((string) ($input['proposed_by'] ?? 'agent')),
        );
        $this->info(json_encode(['rule_id' => $rule->id, 'rule_version_id' => $version->id, 'version' => $version->version, 'status' => $version->status], JSON_UNESCAPED_UNICODE));

        return self::SUCCESS;
    }
}
