<x-layouts.app :title="__('ui.requests.title')">
    <x-page-heading :title="__('ui.requests.title')" :description="__('ui.requests.description')" />

    <form method="GET" action="{{ route('requests.index') }}" class="panel mb-5 flex flex-col gap-3 p-4 sm:flex-row sm:items-end">
        <div class="min-w-0 flex-1">
            <label class="label" for="q">{{ __('ui.common.search') }}</label>
            <input id="q" class="field" type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="{{ __('ui.requests.search_placeholder') }}">
        </div>
        <div class="sm:w-48">
            <label class="label" for="status">{{ __('ui.common.status') }}</label>
            <input id="status" class="field" type="text" name="status" value="{{ $filters['status'] ?? '' }}" placeholder="{{ __('ui.common.all_statuses') }}">
        </div>
        <button class="btn-primary" type="submit">{{ __('ui.common.search') }}</button>
        @if ($filters !== [])
            <a class="btn-secondary" href="{{ route('requests.index') }}">{{ __('ui.common.reset') }}</a>
        @endif
    </form>

    <section class="panel overflow-hidden">
        @if ($requests->isEmpty())
            <x-empty-state :title="__('ui.requests.no_results')" />
        @else
            <div class="divide-y divide-slate-100">
                @foreach ($requests as $item)
                    <article class="p-5 sm:p-6">
                        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-start">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h2 class="font-semibold text-slate-950">
                                        <a class="hover:text-indigo-700 hover:underline" href="{{ route('requests.show', $item) }}">{{ $item->title }}</a>
                                    </h2>
                                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-semibold text-slate-600">{{ $item->status }}</span>
                                </div>
                                <p class="mt-2 whitespace-pre-line text-sm leading-6 text-slate-600">{{ \Illuminate\Support\Str::limit($item->request_body, 240) }}</p>
                                <a class="mt-3 inline-flex text-sm font-semibold text-indigo-700 hover:underline" href="{{ route('requests.show', $item) }}">{{ __('ui.requests.view_details') }} <span aria-hidden="true">→</span></a>
                                <div class="mt-4 flex flex-wrap gap-x-5 gap-y-2 text-xs text-slate-500">
                                    <span>{{ __('ui.requests.platform') }}: <strong class="font-semibold text-slate-700">{{ $item->platform }}</strong></span>
                                    @if ($item->currency && ($item->budget_min_amount !== null || $item->budget_max_amount !== null))
                                        <span>{{ __('ui.requests.budget') }}:
                                            <strong class="font-semibold text-slate-700">
                                                {{ $item->budget_min_amount !== null ? number_format((float) $item->budget_min_amount, 2) : '—' }}
                                                – {{ $item->budget_max_amount !== null ? number_format((float) $item->budget_max_amount, 2) : '—' }}
                                                {{ $item->currency }}
                                            </strong>
                                        </span>
                                    @endif
                                    <span>{{ __('ui.common.date') }}: <strong class="font-semibold text-slate-700">{{ $item->created_at?->format('Y-m-d') ?? '—' }}</strong></span>
                                </div>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
            <div class="border-t border-slate-100 px-5 py-4 sm:px-6">{{ $requests->links() }}</div>
        @endif
    </section>
</x-layouts.app>
