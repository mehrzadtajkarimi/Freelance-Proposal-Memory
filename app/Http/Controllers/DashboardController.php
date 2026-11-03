<?php

namespace App\Http\Controllers;

use App\Models\ProjectRequest;
use App\Models\ProposalVersion;
use App\Models\FreelancerProfile;
use App\Models\RuleVersion;
use App\Models\WorkflowEvent;
use App\Models\WorkflowRun;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

final class DashboardController
{
    public function __invoke(): View|RedirectResponse
    {
        $profile = FreelancerProfile::query()->where('profile_key', 'primary')->first();
        if (! $profile?->setup_completed_at) {
            return redirect()->route('onboarding.index');
        }

        $runGroups = WorkflowRun::query()
            ->selectRaw('source_environment, COUNT(*) AS total')
            ->groupBy('source_environment')
            ->orderBy('source_environment')
            ->get();

        $negotiationQuery = ProjectRequest::query()
            ->inNegotiation()
            ->orderByDesc('negotiation_activity.negotiation_started_at');

        return view('dashboard', [
            'requestCount' => ProjectRequest::query()->count(),
            'draftCount' => ProposalVersion::query()->where('status', 'draft')->count(),
            'submittedCount' => WorkflowEvent::query()
                ->where('event_type', 'proposal.sent')
                ->where('status', 'completed')
                ->count(),
            'negotiationCount' => (clone $negotiationQuery)->count('project_requests.id'),
            'negotiations' => (clone $negotiationQuery)->limit(5)->get(),
            'pendingRuleCount' => RuleVersion::query()->where('status', 'pending_approval')->count(),
            'recentEvents' => WorkflowEvent::query()
                ->with('projectRequest')
                ->orderByDesc('occurred_at')
                ->limit(8)
                ->get(),
            'runGroups' => $runGroups,
            'maxRunCount' => max(1, (int) $runGroups->max('total')),
        ]);
    }
}
