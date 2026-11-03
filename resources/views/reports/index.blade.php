<x-layouts.app :title="__('ui.reports.title')">
    <x-page-heading :title="__('ui.reports.title')" :description="__('ui.reports.description')" />

    <form method="GET" action="{{ route('reports.index') }}" class="panel mb-6 grid gap-3 p-4 sm:grid-cols-2 xl:grid-cols-[1fr_1fr_1fr_auto_auto] xl:items-end">
        <div>
            <label class="label" for="from">{{ __('ui.reports.from') }}</label>
            <input class="field" id="from" name="from" type="date" value="{{ $filters['from'] ?? '' }}">
        </div>
        <div>
            <label class="label" for="to">{{ __('ui.reports.to') }}</label>
            <input class="field" id="to" name="to" type="date" value="{{ $filters['to'] ?? '' }}">
        </div>
        <div>
            <label class="label" for="environment">{{ __('ui.common.environment') }}</label>
            <select class="field" id="environment" name="environment">
                <option value="">{{ __('ui.common.all') }}</option>
                @foreach (['local', 'staging', 'production', 'testing'] as $environment)
                    <option value="{{ $environment }}" @selected(($filters['environment'] ?? '') === $environment)>{{ $environment }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn-primary">{{ __('ui.reports.apply') }}</button>
        @if ($filters !== [])
            <a href="{{ route('reports.index') }}" class="btn-secondary">{{ __('ui.common.reset') }}</a>
        @endif
    </form>

    @php
        $reportStats = [
            ['label' => __('ui.reports.requests'), 'value' => $requestCount, 'tone' => 'bg-sky-50 text-sky-700'],
            ['label' => __('ui.reports.drafts'), 'value' => $draftCount, 'tone' => 'bg-violet-50 text-violet-700'],
            ['label' => __('ui.reports.submitted'), 'value' => $submittedCount, 'tone' => 'bg-emerald-50 text-emerald-700'],
            ['label' => __('ui.reports.negotiations'), 'value' => $negotiationCount, 'tone' => 'bg-fuchsia-50 text-fuchsia-700'],
            ['label' => __('ui.reports.runs'), 'value' => $runCount, 'tone' => 'bg-cyan-50 text-cyan-700'],
            ['label' => __('ui.reports.events'), 'value' => $eventCount, 'tone' => 'bg-amber-50 text-amber-700'],
        ];
    @endphp
    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-6">
        @foreach ($reportStats as $stat)
            <div class="panel p-5">
                <div class="flex items-start justify-between gap-3">
                    <p class="max-w-[12rem] text-sm font-medium leading-5 text-slate-500">{{ $stat['label'] }}</p>
                    <div class="size-2.5 rounded-full {{ $stat['tone'] }}"></div>
                </div>
                <p class="mt-5 text-3xl font-bold tracking-tight text-slate-950 tabular-nums">{{ number_format($stat['value']) }}</p>
            </div>
        @endforeach
    </section>
    <div class="mt-3 space-y-1 text-xs text-slate-500">
        <p>{{ __('ui.reports.draft_note') }}</p>
        <p>{{ __('ui.reports.negotiation_note') }}</p>
        <p>{{ __('ui.reports.filter_note') }}</p>
    </div>

    <section id="negotiations" class="panel mt-6 overflow-hidden">
        <div class="border-b border-slate-100 px-5 py-4 sm:px-6">
            <h2 class="font-semibold text-slate-950">{{ __('ui.reports.negotiations') }}</h2>
        </div>
        @if ($negotiations->isEmpty())
            <x-empty-state :title="__('ui.reports.no_negotiations')" />
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[560px] text-start text-sm">
                    <thead class="bg-slate-50 text-xs font-semibold text-slate-500">
                        <tr>
                            <th class="px-5 py-3 text-start">{{ __('ui.common.title') }}</th>
                            <th class="px-5 py-3 text-start">{{ __('ui.requests.platform') }}</th>
                            <th class="px-5 py-3 text-start">{{ __('ui.reports.negotiation_started_at') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($negotiations as $project)
                            <tr>
                                <td class="max-w-lg px-5 py-3 font-medium text-slate-800">{{ $project->title }}</td>
                                <td class="px-5 py-3 text-slate-600">{{ $project->platform }}</td>
                                <td class="px-5 py-3 text-slate-600">{{ \Illuminate\Support\Carbon::parse($project->negotiation_started_at)->format('Y-m-d H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 px-5 py-4 sm:px-6">{{ $negotiations->links() }}</div>
        @endif
    </section>

    <div class="mt-7 grid gap-6 xl:grid-cols-2">
        <section class="panel overflow-hidden">
            <div class="border-b border-slate-100 px-5 py-4 sm:px-6"><h2 class="font-semibold text-slate-950">{{ __('ui.reports.run_breakdown') }}</h2></div>
            @if ($runGroups->isEmpty())
                <x-empty-state :title="__('ui.reports.no_rows')" />
            @else
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[520px] text-start text-sm">
                        <thead class="bg-slate-50 text-xs font-semibold text-slate-500"><tr><th class="px-5 py-3 text-start">{{ __('ui.common.environment') }}</th><th class="px-5 py-3 text-start">{{ __('ui.reports.type') }}</th><th class="px-5 py-3 text-start">{{ __('ui.common.status') }}</th><th class="px-5 py-3 text-end">{{ __('ui.reports.count') }}</th></tr></thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($runGroups as $row)
                                <tr><td class="px-5 py-3 font-medium text-slate-700">{{ $row->source_environment }}</td><td class="px-5 py-3 text-slate-600">{{ $row->run_type }}</td><td class="px-5 py-3 text-slate-600">{{ __("ui.statuses.{$row->status}") !== "ui.statuses.{$row->status}" ? __("ui.statuses.{$row->status}") : $row->status }}</td><td class="px-5 py-3 text-end font-semibold tabular-nums text-slate-900">{{ number_format($row->total) }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        <section class="panel overflow-hidden">
            <div class="border-b border-slate-100 px-5 py-4 sm:px-6"><h2 class="font-semibold text-slate-950">{{ __('ui.reports.event_breakdown') }}</h2></div>
            @if ($eventGroups->isEmpty())
                <x-empty-state :title="__('ui.reports.no_rows')" />
            @else
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[560px] text-start text-sm">
                        <thead class="bg-slate-50 text-xs font-semibold text-slate-500"><tr><th class="px-5 py-3 text-start">{{ __('ui.common.environment') }}</th><th class="px-5 py-3 text-start">{{ __('ui.reports.type') }}</th><th class="px-5 py-3 text-start">{{ __('ui.common.status') }}</th><th class="px-5 py-3 text-end">{{ __('ui.reports.count') }}</th></tr></thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($eventGroups as $row)
                                @php($eventKey = 'ui.events.'.str_replace('.', '_', $row->event_type))
                                <tr><td class="px-5 py-3 font-medium text-slate-700">{{ $row->source_environment }}</td><td class="max-w-56 truncate px-5 py-3 text-slate-600" title="{{ $row->event_type }}">{{ __($eventKey) !== $eventKey ? __($eventKey) : $row->event_type }}</td><td class="px-5 py-3 text-slate-600">{{ $row->status ? (__("ui.statuses.{$row->status}") !== "ui.statuses.{$row->status}" ? __("ui.statuses.{$row->status}") : $row->status) : '—' }}</td><td class="px-5 py-3 text-end font-semibold tabular-nums text-slate-900">{{ number_format($row->total) }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>

    <section class="panel mt-6 overflow-hidden">
        <div class="border-b border-slate-100 px-5 py-4 sm:px-6"><h2 class="font-semibold text-slate-950">{{ __('ui.reports.recent_events') }}</h2></div>
        @if ($recentEvents->isEmpty())
            <x-empty-state :title="__('ui.reports.no_rows')" />
        @else
            <div class="divide-y divide-slate-100">
                @foreach ($recentEvents as $event)
                    @php($eventKey = 'ui.events.'.str_replace('.', '_', $event->event_type))
                    <div class="flex flex-col gap-2 px-5 py-4 sm:flex-row sm:items-center sm:gap-5 sm:px-6">
                        <time class="shrink-0 text-xs text-slate-400" datetime="{{ $event->occurred_at?->toIso8601String() }}">{{ $event->occurred_at?->format('Y-m-d H:i') ?? '—' }}</time>
                        <div class="min-w-0 flex-1">
                            <p class="break-words text-sm font-semibold text-slate-800">{{ __($eventKey) !== $eventKey ? __($eventKey) : $event->event_type }}</p>
                            @if ($event->message)<p class="mt-1 text-xs leading-5 text-slate-500">{{ $event->message }}</p>@endif
                        </div>
                        <div class="flex shrink-0 items-center gap-2 text-xs text-slate-500">
                            <span class="rounded-full bg-slate-100 px-2.5 py-1 font-medium">{{ $event->source_environment }}</span>
                            @if ($event->status)<span class="rounded-full bg-teal-50 px-2.5 py-1 font-medium text-teal-800">{{ $event->status }}</span>@endif
                            @if ($event->projectRequest)<span class="max-w-40 truncate">{{ $event->projectRequest->title }}</span>@endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </section>
</x-layouts.app>
