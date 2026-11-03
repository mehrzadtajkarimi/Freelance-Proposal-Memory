<?php

namespace App\Console\Commands;

use App\Actions\ProposeNewRule;
use App\Services\ContentSanitizer;
use App\Services\JsonInputReader;
use Illuminate\Console\Command;

final class MemoryAddRuleCommand extends Command
{
    protected $signature = 'memory:add-rule {--stdin-json : Read title, body, scope, and optional project_request_id from standard input}';

    protected $description = 'Save a new rule as pending approval';

    public function handle(ProposeNewRule $action, ContentSanitizer $sanitizer, JsonInputReader $inputReader): int
    {
        if (! $this->option('stdin-json')) {
            $this->error('Pass --stdin-json and provide title and body fields.');

            return self::INVALID;
        }

        $input = $inputReader->decode();
        if (! is_array($input) || trim((string) ($input['title'] ?? '')) === '' || trim((string) ($input['body'] ?? '')) === '') {
            $this->error('JSON must include non-empty title and body fields.');

            return self::INVALID;
        }

        $rule = $action->handle(
            $sanitizer->redact(trim($input['title'])),
            $sanitizer->redact(trim($input['body'])),
            $input['scope'] ?? 'global',
            isset($input['project_request_id']) ? (int) $input['project_request_id'] : null,
            $input['proposed_by'] ?? 'agent',
        );

        $this->info(json_encode([
            'rule_id' => $rule->id,
            'rule_version_id' => $rule->versions->first()?->id,
            'status' => $rule->status,
        ], JSON_UNESCAPED_UNICODE));

        return self::SUCCESS;
    }
}
