<?php

namespace App\Http\Controllers;

use App\Models\ProjectRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

final class ProjectRequestController
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'string', 'max:32'],
        ]);

        $requests = ProjectRequest::query()
            ->when($filters['q'] ?? null, fn ($query, $term) => $query->where('title', 'like', '%'.$term.'%'))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->orderByDesc('updated_at')
            ->paginate(12)
            ->withQueryString();

        return view('requests.index', compact('requests', 'filters'));
    }

    public function show(ProjectRequest $projectRequest): View
    {
        $projectRequest->load([
            'emphases',
            'proposals' => fn ($query) => $query->orderByDesc('version'),
        ]);

        return view('requests.show', compact('projectRequest'));
    }

    public function updateFullTime(Request $request, ProjectRequest $projectRequest): RedirectResponse
    {
        $validated = $request->validate([
            'is_full_time' => ['required', 'boolean'],
        ]);

        $projectRequest->update([
            'is_full_time' => (bool) $validated['is_full_time'],
            'is_full_time_known' => true,
        ]);

        return redirect()
            ->route('reports.index')
            ->with('success', __('ui.reports.full_time_saved'));
    }
}
