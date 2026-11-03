<x-layouts.app :title="__('ui.rules.title')">
    <x-page-heading :title="__('ui.rules.title')" :description="__('ui.rules.description')">
        <x-slot:action>
            <a href="{{ route('rules.create') }}" class="btn-primary">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14" /></svg>
                {{ __('ui.rules.new') }}
            </a>
        </x-slot:action>
    </x-page-heading>

    <form method="GET" action="{{ route('rules.index') }}" class="panel mb-5 flex flex-col gap-3 p-4 sm:flex-row sm:items-end">
        <div class="sm:w-52">
            <label class="label" for="scope">{{ __('ui.rules.scope') }}</label>
            <select id="scope" class="field" name="scope">
                <option value="">{{ __('ui.common.all_scopes') }}</option>
                <option value="global" @selected(($filters['scope'] ?? '') === 'global')>{{ __('ui.common.global') }}</option>
                <option value="project" @selected(($filters['scope'] ?? '') === 'project')>{{ __('ui.common.project') }}</option>
            </select>
        </div>
        <div class="sm:w-56">
            <label class="label" for="status">{{ __('ui.common.status') }}</label>
            <select id="status" class="field" name="status">
                <option value="">{{ __('ui.common.all_statuses') }}</option>
                <option value="approved" @selected(($filters['status'] ?? '') === 'approved')>{{ __('ui.common.approved') }}</option>
                <option value="pending_approval" @selected(($filters['status'] ?? '') === 'pending_approval')>{{ __('ui.common.pending') }}</option>
            </select>
        </div>
        <button type="submit" class="btn-primary">{{ __('ui.common.search') }}</button>
        @if ($filters !== [])
            <a href="{{ route('rules.index') }}" class="btn-secondary">{{ __('ui.common.reset') }}</a>
        @endif
    </form>

    <div class="space-y-4">
        @forelse ($rules as $rule)
            @php
                $currentVersion = $rule->versions->first(fn ($version) => $version->version === $rule->current_version && $version->status === 'approved');
                $pendingVersions = $rule->versions->where('status', 'pending_approval');
                $displayVersion = $currentVersion ?? $pendingVersions->first();
            @endphp
            <article class="panel p-5 sm:p-6">
                <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-start">
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="text-base font-semibold text-slate-950">{{ $rule->title }}</h2>
                            <span class="rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $rule->status === 'approved' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-800' }}">
                                {{ $rule->status === 'approved' ? __('ui.common.approved') : __('ui.common.pending') }}
                            </span>
                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-semibold text-slate-600">{{ $rule->scope === 'global' ? __('ui.common.global') : __('ui.common.project') }}</span>
                        </div>
                        @if ($rule->projectRequest)
                            <p class="mt-1 text-xs text-slate-500">{{ $rule->projectRequest->title }}</p>
                        @endif
                        @if ($displayVersion)
                            <p class="mt-4 whitespace-pre-line text-sm leading-6 text-slate-600">{{ \Illuminate\Support\Str::limit($displayVersion->body, 420) }}</p>
                            <p class="mt-3 text-xs text-slate-400">{{ __('ui.common.version', ['number' => $displayVersion->version]) }}</p>
                        @else
                            <p class="mt-4 text-sm text-slate-500">{{ __('ui.rules.no_current') }}</p>
                        @endif
                        @if ($pendingVersions->isNotEmpty())
                            <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-3.5 py-3 text-xs leading-5 text-amber-900">
                                {{ __('ui.rules.pending_note') }}
                                <div class="mt-2 flex flex-wrap gap-2">
                                    @foreach ($pendingVersions as $pendingVersion)
                                        <form method="POST" action="{{ route('rule-versions.approve', $pendingVersion) }}">
                                            @csrf
                                            <button type="submit" class="rounded-lg border border-amber-300 bg-white px-3 py-1.5 font-semibold text-amber-900 transition hover:bg-amber-100">{{ __('ui.rules.approve') }} · {{ __('ui.common.version', ['number' => $pendingVersion->version]) }}</button>
                                        </form>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                    <a href="{{ route('rules.show', $rule) }}" class="btn-secondary shrink-0">{{ __('ui.rules.review') }}</a>
                </div>
            </article>
        @empty
            <section class="panel"><x-empty-state :title="__('ui.rules.no_rules')" /></section>
        @endforelse
    </div>

    @if ($rules->hasPages())
        <div class="mt-5">{{ $rules->links() }}</div>
    @endif
</x-layouts.app>
