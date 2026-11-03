<?php

namespace App\Console\Commands;

use App\Services\PersonalDataPackage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

final class MemoryExportDataCommand extends Command
{
    protected $signature = 'memory:export-data';

    protected $description = 'Export the private freelancer profile and approved global rules to storage';

    public function handle(PersonalDataPackage $package): int
    {
        $path = storage_path('app/private/personal-data.json');
        File::ensureDirectoryExists(dirname($path));
        File::put($path, json_encode(
            $package->export(),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
        ));

        $this->info('Private personal data package exported to '.$path);

        return self::SUCCESS;
    }
}
