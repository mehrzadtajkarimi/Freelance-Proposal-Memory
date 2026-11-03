<x-layouts.app :title="$rule->title">
    <x-page-heading :title="$rule->title" :description="__('ui.rules.description')">
        <x-slot:action>
            <div class="flex gap-2">
                <a href="{{ route('rules.index') }}" class="btn-secondary">{{ __('ui.common.back') }}</a>
                <a href="{{ route('rules.edit', $rule) }}" class="btn-primary">{{ __('ui.rules.review') }}</a>
            </div>
        </x-slot:action>
    </x-page-heading>

    @php($currentVersion = $rule->versions->first(fn ($version) => $version->version === $rule->current_version && $version->status === 'approved'))
    <div class="mb-6 flex flex-wrap gap-2">
        <span class="rounded-full bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-700">{{ $rule->scope === 'global' ? __('ui.common.global') : __('ui.common.project') }}</span>
        <span class="rounded-full px-3 py-1.5 text-xs font-semibold {{ $rule->status === 'approved' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-800' }}">{{ $rule->status === 'approved' ? __('ui.common.approved') : __('ui.common.pending') }}</span>
        @if ($rule->projectRequest)
            <span class="rounded-full bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-700">{{ $rule->projectRequest->title }}</span>
        @endif
    </div>

    <section class="panel p-5 sm:p-7">
        <h2 class="text-sm font-semibold text-slate-500">{{ __('ui.rules.current') }}</h2>
        @if ($currentVersion)
            <p class="mt-3 whitespace-pre-line text-sm leading-7 text-slate-800">{{ $currentVersion->body }}</p>
        @else
            <p class="mt-3 text-sm text-slate-500">{{ __('ui.rules.no_current') }}</p>
        @endif
    </section>

    <section class="mt-6">
        <div class="mb-4 flex items-end justify-between gap-4">
            <div>
                <h2 class="text-lg font-bold text-slate-950">{{ __('ui.rules.versions') }}</h2>
            </div>
        </div>
        <div class="space-y-3">
            @foreach ($rule->versions as $version)
                <article class="panel p-5 sm:p-6">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="font-semibold text-slate-900">{{ __('ui.common.version', ['number' => $version->version]) }}</h3>
                            <span class="rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $version->status === 'approved' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-800' }}">{{ $version->status === 'approved' ? __('ui.common.approved') : __('ui.common.pending') }}</span>
                        </div>
                        <time class="text-xs text-slate-400" datetime="{{ $version->created_at?->toIso8601String() }}">{{ $version->created_at?->format('Y-m-d H:i') ?? '—' }}</time>
                    </div>
                    @if ($version->change_summary)
                        <p class="mt-3 text-sm font-medium text-slate-700">{{ $version->change_summary }}</p>
                    @endif
                    <p class="mt-3 whitespace-pre-line text-sm leading-6 text-slate-600">{{ $version->body }}</p>
                    @if ($version->status === 'pending_approval')
                        <div class="mt-4 flex flex-col justify-between gap-3 rounded-xl border border-amber-200 bg-amber-50 p-3.5 sm:flex-row sm:items-center">
                            <p class="text-xs leading-5 text-amber-900">{{ __('ui.rules.pending_note') }}</p>
                            <form method="POST" action="{{ route('rule-versions.approve', $version) }}">
                                @csrf
                                <button type="submit" class="btn-primary bg-amber-700! hover:bg-amber-800!">{{ __('ui.rules.approve') }}</button>
                            </form>
                        </div>
                    @endif
                </article>
            @endforeach
        </div>
    </section>
</x-layouts.app>
