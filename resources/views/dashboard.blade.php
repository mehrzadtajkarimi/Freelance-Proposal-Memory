<x-layouts.app :title="__('ui.dashboard.title')">
    <x-page-heading :title="__('ui.dashboard.title')" :description="__('ui.dashboard.description')">
        <x-slot:action>
            <a href="{{ route('reports.index') }}" class="btn-primary">
                <span>{{ __('ui.dashboard.open_reports') }}</span>
                <svg class="size-4 rtl:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14m-6-6 6 6-6 6" /></svg>
            </a>
        </x-slot:action>
    </x-page-heading>

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="{{ __('ui.dashboard.title') }}">
        @php
            $stats = [
                ['label' => __('ui.dashboard.requests'), 'value' => $requestCount, 'hint' => __('ui.nav.requests'), 'href' => route('requests.index'), 'tone' => 'bg-sky-50 text-sky-700', 'icon' => 'M7.5 3.75h9A2.25 2.25 0 0 1 18.75 6v14.25H5.25V6A2.25 2.25 0 0 1 7.5 3.75ZM8.25 8.25h7.5'],
                ['label' => __('ui.dashboard.drafts'), 'value' => $draftCount, 'hint' => __('ui.nav.reports'), 'href' => route('reports.index'), 'tone' => 'bg-violet-50 text-violet-700', 'icon' => 'M7 3.75h7l4.5 4.5v12H7zM14 3.75v4.5h4.5M9.5 13h6m-6 3.5h6'],
                ['label' => __('ui.dashboard.submitted'), 'value' => $submittedCount, 'hint' => __('ui.nav.reports'), 'href' => route('reports.index'), 'tone' => 'bg-emerald-50 text-emerald-700', 'icon' => 'm5 12.5 4 4L19 6.5'],
                ['label' => __('ui.dashboard.negotiations'), 'value' => $negotiationCount, 'hint' => __('ui.nav.reports'), 'href' => route('reports.index').'#negotiations', 'tone' => 'bg-fuchsia-50 text-fuchsia-700', 'icon' => 'M7.5 8.25h9m-9 3.75h6m-9.75 8.25 2.1-4.2A8.2 8.2 0 1 1 9 19.5H3.75Z'],
                ['label' => __('ui.dashboard.pending_rules'), 'value' => $pendingRuleCount, 'hint' => __('ui.nav.rules'), 'href' => route('rules.index', ['status' => 'pending_approval']), 'tone' => 'bg-amber-50 text-amber-700', 'icon' => 'M12 8v4l2.5 1.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'],
            ];
        @endphp
        @foreach ($stats as $stat)
            <a href="{{ $stat['href'] }}" class="panel group p-5 transition hover:-translate-y-0.5 hover:shadow-md">
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-slate-500">{{ $stat['label'] }}</p>
                        <p class="mt-4 text-3xl font-bold tracking-tight text-slate-950 tabular-nums">{{ number_format($stat['value']) }}</p>
                    </div>
                    <div class="flex size-11 shrink-0 items-center justify-center rounded-2xl {{ $stat['tone'] }}">
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{{ $stat['icon'] }}" /></svg>
                    </div>
                </div>
                <div class="mt-5 flex items-center justify-between border-t border-slate-100 pt-3 text-xs font-semibold text-slate-500 group-hover:text-teal-700">
                    <span>{{ $stat['hint'] }}</span>
                    <span aria-hidden="true">{{ app()->getLocale() === 'fa' ? '←' : '→' }}</span>
                </div>
            </a>
        @endforeach
    </section>

    <section class="panel mt-6 overflow-hidden">
        <div class="flex items-center justify-between gap-4 border-b border-slate-100 px-5 py-4 sm:px-6">
            <div>
                <h2 class="font-semibold text-slate-950">{{ __('ui.dashboard.negotiations') }}</h2>
                <p class="mt-1 text-xs text-slate-500">{{ __('ui.dashboard.negotiations_hint') }}</p>
            </div>
            <a href="{{ route('reports.index') }}#negotiations" class="text-xs font-semibold text-teal-700 hover:text-teal-900">{{ __('ui.common.view_all') }}</a>
        </div>
        @if ($negotiations->isEmpty())
            <x-empty-state :title="__('ui.dashboard.no_negotiations')" />
        @else
            <div class="divide-y divide-slate-100">
                @foreach ($negotiations as $project)
                    <div class="flex flex-col gap-2 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-slate-800">{{ $project->title }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ $project->platform }}</p>
                        </div>
                        <time class="shrink-0 text-xs text-slate-400" datetime="{{ \Illuminate\Support\Carbon::parse($project->negotiation_started_at)->toIso8601String() }}">{{ \Illuminate\Support\Carbon::parse($project->negotiation_started_at)->format('Y-m-d H:i') }}</time>
                    </div>
                @endforeach
            </div>
        @endif
    </section>

    <div class="mt-6 grid gap-6 xl:grid-cols-[minmax(0,1.65fr)_minmax(260px,0.8fr)]">
        <section class="panel overflow-hidden">
            <div class="flex items-center justify-between gap-4 border-b border-slate-100 px-5 py-4 sm:px-6">
                <div>
                    <h2 class="font-semibold text-slate-950">{{ __('ui.dashboard.recent_activity') }}</h2>
                    <p class="mt-1 text-xs text-slate-500">{{ __('ui.dashboard.recent_activity_hint') }}</p>
                </div>
                <a href="{{ route('reports.index') }}" class="text-xs font-semibold text-teal-700 hover:text-teal-900">{{ __('ui.common.view_all') }}</a>
            </div>
            @if ($recentEvents->isEmpty())
                <x-empty-state />
            @else
                <div class="divide-y divide-slate-100">
                    @foreach ($recentEvents as $event)
                        @php($eventKey = 'ui.events.'.str_replace('.', '_', $event->event_type))
                        <div class="flex items-start gap-3 px-5 py-4 sm:px-6">
                            <div class="mt-0.5 flex size-9 shrink-0 items-center justify-center rounded-xl bg-teal-50 text-teal-700">
                                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3v3m0 12v3m9-9h-3M6 12H3m15.36-6.36-2.12 2.12M7.76 16.24l-2.12 2.12m12.72 0-2.12-2.12M7.76 7.76 5.64 5.64M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="break-words text-sm font-semibold text-slate-800">{{ __($eventKey) === $eventKey ? str($event->event_type)->replace('.', ' · ') : __($eventKey) }}</p>
                                @if ($event->message)
                                    <p class="mt-1 line-clamp-2 text-xs leading-5 text-slate-500">{{ $event->message }}</p>
                                @endif
                                @if ($event->projectRequest)
                                    <p class="mt-1 text-xs text-slate-400">{{ $event->projectRequest->title }}</p>
                                @endif
                            </div>
                            <time class="shrink-0 text-[11px] text-slate-400" datetime="{{ $event->occurred_at?->toIso8601String() }}">{{ $event->occurred_at?->format('Y-m-d H:i') ?? '—' }}</time>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>

        <section class="panel p-5 sm:p-6">
            <div class="mb-5">
                <h2 class="font-semibold text-slate-950">{{ __('ui.dashboard.workflow_runs') }}</h2>
                <p class="mt-1 text-xs text-slate-500">{{ __('ui.dashboard.workflow_runs_hint') }}</p>
            </div>
            @if ($runGroups->isEmpty())
                <x-empty-state />
            @else
                <div class="space-y-5">
                    @foreach ($runGroups as $group)
                        <div>
                            <div class="mb-2 flex items-center justify-between gap-3 text-sm">
                                <span class="font-medium text-slate-700">{{ $group->source_environment }}</span>
                                <span class="font-semibold tabular-nums text-slate-900">{{ number_format($group->total) }}</span>
                            </div>
                            <div class="h-2 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full bg-teal-600" style="width: {{ ((int) $group->total / $maxRunCount) * 100 }}%"></div></div>
                        </div>
                    @endforeach
                </div>
            @endif
            <a href="{{ route('reports.index') }}" class="btn-secondary mt-7 w-full">{{ __('ui.dashboard.open_reports') }}</a>
        </section>
    </div>
</x-layouts.app>
