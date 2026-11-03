<?php

namespace App\Console\Commands;

use App\Models\Rule;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

final class MemoryExportRulesCommand extends Command
{
    protected $signature = 'memory:export-rules {--path= : Output path; defaults to storage/app/private/approved-rules.md}';

    protected $description = 'Export currently approved global rules to the human-readable Markdown mirror';

    public function handle(): int
    {
        $rules = Rule::query()
            ->where('scope', 'global')
            ->where('status', 'approved')
            ->with(['versions' => fn ($query) => $query->where('status', 'approved')->orderByDesc('version')])
            ->orderBy('priority')
            ->orderBy('id')
            ->get();

        $lines = [
            '# Approved Proposal Rules',
            '',
            'This file is a readable export of approved global rules. The database remains the source of truth.',
            '',
            '## Approved Rules',
            '',
        ];

        foreach ($rules as $rule) {
            $version = $rule->versions->firstWhere('version', $rule->current_version);
            if ($version) {
                $lines[] = '- '.$version->body;
            }
        }

        $path = $this->option('path') ?: storage_path('app/private/approved-rules.md');
        File::ensureDirectoryExists(dirname($path));
        File::put($path, implode(PHP_EOL, $lines).PHP_EOL);

        $this->info('Exported '.$rules->count().' approved global rules to '.$path);

        return self::SUCCESS;
    }
}
