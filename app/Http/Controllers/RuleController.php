<?php

namespace App\Http\Controllers;

use App\Actions\ApproveRuleVersion;
use App\Actions\ProposeNewRule;
use App\Actions\ProposeRuleChange;
use App\Models\ProjectRequest;
use App\Models\Rule;
use App\Models\RuleVersion;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class RuleController
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'scope' => ['nullable', 'in:global,project'],
            'status' => ['nullable', 'in:approved,pending_approval'],
        ]);

        $rules = Rule::query()
            ->with(['projectRequest', 'versions' => fn ($query) => $query->orderByDesc('version')])
            ->when($filters['scope'] ?? null, fn ($query, $scope) => $query->where('scope', $scope))
            ->when($filters['status'] ?? null, function ($query, $status): void {
                if ($status === 'pending_approval') {
                    $query->where(fn ($pending) => $pending
                        ->where('status', $status)
                        ->orWhereHas('versions', fn ($versions) => $versions->where('status', $status)));

                    return;
                }

                $query->where('status', $status);
            })
            ->orderByDesc('updated_at')
            ->paginate(10)
            ->withQueryString();

        return view('rules.index', compact('rules', 'filters'));
    }

    public function create(): View
    {
        return view('rules.create', [
            'projectRequests' => ProjectRequest::query()->orderBy('title')->get(['id', 'title']),
        ]);
    }

    public function store(Request $request, ProposeNewRule $proposeNewRule): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:20000'],
            'scope' => ['required', 'in:global,project'],
            'project_request_id' => ['required_if:scope,project', 'nullable', 'integer', 'exists:project_requests,id'],
        ]);

        $rule = $proposeNewRule->handle(
            $data['title'],
            $data['body'],
            $data['scope'],
            $data['scope'] === 'project' ? (int) $data['project_request_id'] : null,
            'user',
        );

        return redirect()->route('rules.show', $rule)->with('success', __('ui.rules.created'));
    }

    public function show(Rule $rule): View
    {
        $rule->load(['projectRequest', 'versions' => fn ($query) => $query->orderByDesc('version')]);

        return view('rules.show', compact('rule'));
    }

    public function edit(Rule $rule): View
    {
        $rule->load(['projectRequest', 'versions' => fn ($query) => $query->orderByDesc('version')]);

        return view('rules.edit', compact('rule'));
    }

    public function update(Request $request, Rule $rule, ProposeRuleChange $proposeRuleChange): RedirectResponse
    {
        $data = $request->validate([
            'body' => ['required', 'string', 'max:20000'],
            'change_summary' => ['required', 'string', 'max:2000'],
        ]);

        $version = $proposeRuleChange->handle($rule, $data['body'], $data['change_summary'], 'user');

        return redirect()->route('rules.show', $rule)
            ->with('success', __('ui.rules.change_proposed', ['version' => $version->version]));
    }

    public function approve(RuleVersion $version, ApproveRuleVersion $approveRuleVersion): RedirectResponse
    {
        try {
            $approveRuleVersion->handle($version, 'user');
        } catch (\InvalidArgumentException) {
            return redirect()->route('rules.show', $version->rule_id)
                ->withErrors(['version' => __('ui.rules.version_not_pending')]);
        }

        return redirect()->route('rules.show', $version->rule_id)
            ->with('success', __('ui.rules.approved'));
    }
}
