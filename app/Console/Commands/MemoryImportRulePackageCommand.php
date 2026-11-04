<?php

namespace App\Console\Commands;

use App\Services\AgentRulePackage;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;

final class MemoryImportRulePackageCommand extends Command
{
    protected $signature = 'memory:import-rule-package {path : Path to a rules-only JSON package}';

    protected $description = 'Replace the reusable rule set with a JSON package and leave every imported item pending approval';

    public function handle(AgentRulePackage $package): int
    {
        $contents = @file_get_contents((string) $this->argument('path'));

        if ($contents === false) {
            $this->error('The JSON package could not be read.');

            return self::FAILURE;
        }

        try {
            $validated = $package->validateContents($contents);
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $messages) {
                foreach ($messages as $message) {
                    $this->error($message);
                }
            }

            return self::INVALID;
        }

        $summary = $package->replace($validated);
        $this->info('Imported '.$summary['rules_imported'].' rules and '.$summary['payment_stage_rules_imported'].' payment-stage rules as pending approval.');
        $this->info('Archived '.$summary['rules_archived'].' rules that were not in the package.');

        return self::SUCCESS;
    }
}
