<?php

namespace App\Console\Commands;

use App\Models\ProjectRequest;
use App\Models\ProposalVersion;
use App\Models\WorkflowEvent;
use App\Models\WorkflowRun;
use Illuminate\Console\Command;

final class MemoryReportCommand extends Command
{
    protected $signature = 'memory:report {--from= : Start date, inclusive} {--to= : End date, inclusive} {--environment= : local, staging, or production}';

    protected $description = 'Report proposal workflow activity by date range and environment';

    public function handle(): int
    {
        $runs = WorkflowRun::query()
            ->when($this->option('from'), fn ($query, $from) => $query->where('started_at', '>=', $from.' 00:00:00'))
            ->when($this->option('to'), fn ($query, $to) => $query->where('started_at', '<=', $to.' 23:59:59'))
            ->when($this->option('environment'), fn ($query, $environment) => $query->where('source_environment', $environment));

        $this->info('Environment / run type / status / count');
        $groups = (clone $runs)
            ->selectRaw('source_environment, run_type, status, COUNT(*) AS total')
            ->groupBy('source_environment', 'run_type', 'status')
            ->orderBy('source_environment')
            ->orderBy('run_type')
            ->get();

        foreach ($groups as $group) {
            $this->line("{$group->source_environment} / {$group->run_type} / {$group->status} / {$group->total}");
        }

        $this->newLine();
        $this->info('Environment / event / status / count');
        $eventGroups = WorkflowEvent::query()
            ->when($this->option('from'), fn ($query, $from) => $query->where('occurred_at', '>=', $from.' 00:00:00'))
            ->when($this->option('to'), fn ($query, $to) => $query->where('occurred_at', '<=', $to.' 23:59:59'))
            ->when($this->option('environment'), fn ($query, $environment) => $query->where('source_environment', $environment))
            ->selectRaw('source_environment, event_type, status, COUNT(*) AS total')
            ->groupBy('source_environment', 'event_type', 'status')
            ->orderBy('source_environment')
            ->orderBy('event_type')
            ->get();

        foreach ($eventGroups as $group) {
            $this->line("{$group->source_environment} / {$group->event_type} / {$group->status} / {$group->total}");
        }

        $requestCount = ProjectRequest::query()
            ->when($this->option('from'), fn ($query, $from) => $query->where('created_at', '>=', $from.' 00:00:00'))
            ->when($this->option('to'), fn ($query, $to) => $query->where('created_at', '<=', $to.' 23:59:59'))
            ->count();
        $draftCount = ProposalVersion::query()
            ->where('status', 'draft')
            ->when($this->option('from'), fn ($query, $from) => $query->where('created_at', '>=', $from.' 00:00:00'))
            ->when($this->option('to'), fn ($query, $to) => $query->where('created_at', '<=', $to.' 23:59:59'))
            ->count();
        $sentCount = WorkflowEvent::query()
            ->where('event_type', 'proposal.sent')
            ->where('status', 'completed')
            ->when($this->option('environment'), fn ($query, $environment) => $query->where('source_environment', $environment))
            ->when($this->option('from'), fn ($query, $from) => $query->where('occurred_at', '>=', $from.' 00:00:00'))
            ->when($this->option('to'), fn ($query, $to) => $query->where('occurred_at', '<=', $to.' 23:59:59'))
            ->count();

        $this->newLine();
        $this->line("Saved requests in selected dates (all environments): {$requestCount}");
        $this->line("Draft proposal versions in selected dates (all environments): {$draftCount}");
        $this->line("Confirmed submissions in range: {$sentCount}");
        $this->comment('A draft is not counted as sent. Only a confirmed proposal.sent event is included.');

        return self::SUCCESS;
    }
}
