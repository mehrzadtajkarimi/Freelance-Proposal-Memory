<?php

namespace App\Http\Controllers;

use App\Models\ProjectRequest;
use App\Models\ProposalVersion;
use App\Models\WorkflowEvent;
use App\Models\WorkflowRun;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final class ReportController
{
    public function __invoke(Request $request): View
    {
        $filters = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'environment' => ['nullable', 'in:local,staging,production,testing'],
        ]);

        $runQuery = WorkflowRun::query()
            ->when($filters['from'] ?? null, fn ($query, $from) => $query->where('started_at', '>=', $from.' 00:00:00'))
            ->when($filters['to'] ?? null, fn ($query, $to) => $query->where('started_at', '<=', $to.' 23:59:59'))
            ->when($filters['environment'] ?? null, fn ($query, $environment) => $query->where('source_environment', $environment));

        $eventQuery = WorkflowEvent::query()
            ->when($filters['from'] ?? null, fn ($query, $from) => $query->where('occurred_at', '>=', $from.' 00:00:00'))
            ->when($filters['to'] ?? null, fn ($query, $to) => $query->where('occurred_at', '<=', $to.' 23:59:59'))
            ->when($filters['environment'] ?? null, fn ($query, $environment) => $query->where('source_environment', $environment));

        $requestCount = ProjectRequest::query()
            ->when($filters['from'] ?? null, fn ($query, $from) => $query->where('created_at', '>=', $from.' 00:00:00'))
            ->when($filters['to'] ?? null, fn ($query, $to) => $query->where('created_at', '<=', $to.' 23:59:59'))
            ->count();

        $draftCount = ProposalVersion::query()
            ->where('status', 'draft')
            ->when($filters['from'] ?? null, fn ($query, $from) => $query->where('created_at', '>=', $from.' 00:00:00'))
            ->when($filters['to'] ?? null, fn ($query, $to) => $query->where('created_at', '<=', $to.' 23:59:59'))
            ->count();

        $submittedCount = (clone $eventQuery)
            ->where('event_type', 'proposal.sent')
            ->where('status', 'completed')
            ->count();

        $negotiationQuery = ProjectRequest::query()
            ->inNegotiation()
            ->when($filters['from'] ?? null, fn ($query, $from) => $query->where('negotiation_activity.negotiation_started_at', '>=', $from.' 00:00:00'))
            ->when($filters['to'] ?? null, fn ($query, $to) => $query->where('negotiation_activity.negotiation_started_at', '<=', $to.' 23:59:59'))
            ->orderByDesc('negotiation_activity.negotiation_started_at');

        return view('reports.index', [
            'filters' => $filters,
            'projectRequests' => ProjectRequest::query()
                ->orderByDesc('updated_at')
                ->paginate(12, ['*'], 'projects_page')
                ->withQueryString(),
            'requestCount' => $requestCount,
            'draftCount' => $draftCount,
            'submittedCount' => $submittedCount,
            'negotiationCount' => (clone $negotiationQuery)->count('project_requests.id'),
            'negotiations' => (clone $negotiationQuery)->paginate(12)->withQueryString(),
            'runCount' => (clone $runQuery)->count(),
            'eventCount' => (clone $eventQuery)->count(),
            'runGroups' => (clone $runQuery)
                ->selectRaw('source_environment, run_type, status, COUNT(*) AS total')
                ->groupBy('source_environment', 'run_type', 'status')
                ->orderBy('source_environment')
                ->orderBy('run_type')
                ->orderBy('status')
                ->get(),
            'eventGroups' => (clone $eventQuery)
                ->selectRaw('source_environment, event_type, status, COUNT(*) AS total')
                ->groupBy('source_environment', 'event_type', 'status')
                ->orderBy('source_environment')
                ->orderBy('event_type')
                ->orderBy('status')
                ->get(),
            'recentEvents' => (clone $eventQuery)
                ->with('projectRequest')
                ->orderByDesc('occurred_at')
                ->limit(20)
                ->get(),
        ]);
    }
}
