<?php

namespace App\Console\Commands;

use App\Services\AgentRulePackage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

final class MemoryExportDataCommand extends Command
{
    protected $signature = 'memory:export-data';

    protected $description = 'Export the global agent rules and payment-stage rules to a private JSON file';

    public function handle(AgentRulePackage $package): int
    {
        $path = storage_path('app/private/agent-rules.json');
        File::ensureDirectoryExists(dirname($path));
        File::put($path, json_encode(
            $package->export(),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
        ));

        $this->info('Agent rule package exported to '.$path);

        return self::SUCCESS;
    }
}
