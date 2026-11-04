<x-layouts.app :title="$projectRequest->title">
    <div class="mb-5">
        <a class="text-sm font-semibold text-indigo-700 hover:underline" href="{{ route('requests.index') }}">← {{ __('ui.requests.back_to_list') }}</a>
        <x-page-heading :title="$projectRequest->title" :description="$projectRequest->platform . ' · ' . $projectRequest->status" />
    </div>

    <section class="panel mb-6 p-4 sm:p-6" aria-labelledby="request-uml-title">
        <div class="mb-5">
            <h2 id="request-uml-title" class="text-lg font-semibold text-slate-950">{{ __('ui.requests.uml_title') }}</h2>
            <p class="mt-1 text-sm text-slate-600">{{ __('ui.requests.uml_description') }}</p>
        </div>

        <div class="overflow-x-auto pb-2">
            <div class="min-w-[760px]">
                <article class="mx-auto max-w-4xl overflow-hidden rounded-xl border-2 border-indigo-300 bg-white shadow-sm">
                    <header class="border-b border-indigo-200 bg-indigo-50 px-4 py-3 text-center">
                        <p class="text-xs font-semibold uppercase tracking-wide text-indigo-700">&laquo;{{ __('ui.requests.database_record') }}&raquo;</p>
                        <h3 class="mt-1 font-mono text-base font-bold text-slate-950">ProjectRequest #{{ $projectRequest->id }}</h3>
                    </header>
                    <dl class="grid gap-x-8 px-4 py-3 text-sm sm:grid-cols-2">
                        @foreach ([
                            'platform' => $projectRequest->platform,
                            'source_url' => $projectRequest->source_url,
                            'external_id' => $projectRequest->external_id,
                            'budget' => $projectRequest->currency && ($projectRequest->budget_min_amount !== null || $projectRequest->budget_max_amount !== null)
                                ? (($projectRequest->budget_min_amount !== null ? number_format((float) $projectRequest->budget_min_amount, 2) : '—') . ' – ' . ($projectRequest->budget_max_amount !== null ? number_format((float) $projectRequest->budget_max_amount, 2) : '—') . ' ' . $projectRequest->currency)
                                : null,
                            'client_location' => $projectRequest->client_location_text ?: collect([$projectRequest->client_city, $projectRequest->client_region, $projectRequest->client_country])->filter()->implode(', '),
                            'posted_at' => $projectRequest->posted_at
                                ? $projectRequest->posted_at->setTimezone($projectRequest->source_timezone ?: config('app.timezone'))->format('Y-m-d H:i:s T')
                                : null,
                            'posted_time_text' => $projectRequest->posted_time_text,
                            'posted_at_basis' => match ($projectRequest->posted_at_basis) {
                                'source_absolute' => __('ui.requests.source_absolute'),
                                'derived_relative' => __('ui.requests.derived_relative'),
                                default => __('ui.requests.unknown'),
                            },
                            'captured_at' => $projectRequest->captured_at
                                ? $projectRequest->captured_at->setTimezone($projectRequest->source_timezone ?: config('app.timezone'))->format('Y-m-d H:i:s T')
                                : null,
                            'source_timezone' => $projectRequest->source_timezone,
                            'client_reviews_count' => $projectRequest->client_reviews_count,
                            'client_rating' => $projectRequest->client_rating !== null
                                ? $projectRequest->client_rating . ($projectRequest->client_rating_scale ? ' / ' . $projectRequest->client_rating_scale : '')
                                : null,
                            'client_is_new' => $projectRequest->client_is_new === null ? null : ($projectRequest->client_is_new ? __('ui.common.yes') : __('ui.common.no')),
                            'client_payment_verified' => $projectRequest->client_payment_verified === null ? null : ($projectRequest->client_payment_verified ? __('ui.common.yes') : __('ui.common.no')),
                            'applicants_count' => $projectRequest->applicants_count,
                            'client_deadline' => $projectRequest->client_deadline_text ?: ($projectRequest->client_deadline_days ? __('ui.requests.days_value', ['count' => $projectRequest->client_deadline_days]) : null),
                            'is_full_time' => $projectRequest->is_full_time_known ? ($projectRequest->is_full_time ? __('ui.common.yes') : __('ui.common.no')) : null,
                            'is_featured' => $projectRequest->is_featured === null ? null : ($projectRequest->is_featured ? __('ui.common.yes') : __('ui.common.no')),
                        ] as $label => $value)
                            <div class="flex min-w-0 justify-between gap-3 border-b border-dashed border-slate-200 py-2">
                                <dt class="shrink-0 font-mono text-xs text-slate-500">{{ __('ui.requests.' . $label) }}</dt>
                                <dd class="break-words text-right text-slate-800">{{ filled($value) ? $value : __('ui.requests.not_recorded') }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </article>

                <svg class="h-20 w-full text-slate-400" viewBox="0 0 1000 100" role="img" aria-label="{{ __('ui.requests.uml_relationships') }}" xmlns="http://www.w3.org/2000/svg">
                    <path d="M500 0 V38 H250 V92 M500 38 H750 V92" fill="none" stroke="currentColor" stroke-width="2" />
                    <path d="M244 84 L250 94 L256 84 M744 84 L750 94 L756 84" fill="none" stroke="currentColor" stroke-width="2" />
                    <text x="500" y="29" text-anchor="middle" class="fill-slate-500" font-size="14">1</text>
                    <text x="236" y="77" text-anchor="end" class="fill-slate-500" font-size="14">0..*</text>
                    <text x="764" y="77" text-anchor="start" class="fill-slate-500" font-size="14">0..*</text>
                </svg>

                <div class="grid grid-cols-2 gap-5">
                    <article class="overflow-hidden rounded-xl border-2 border-emerald-300 bg-white shadow-sm">
                        <header class="border-b border-emerald-200 bg-emerald-50 px-4 py-3">
                            <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">&laquo;{{ __('ui.requests.database_record') }}&raquo;</p>
                            <h3 class="mt-1 font-mono font-bold text-slate-950">RequestEmphasis</h3>
                            <p class="mt-1 text-xs text-slate-600">{{ $projectRequest->emphases->count() }} {{ __('ui.requests.emphases') }}</p>
                        </header>
                        <div class="divide-y divide-slate-100">
                            @forelse ($projectRequest->emphases as $emphasis)
                                <div class="p-4">
                                    <p class="font-mono text-xs text-slate-500">#{{ $emphasis->position }} · {{ __('ui.requests.heading') }}</p>
                                    <h4 class="mt-1 font-semibold text-slate-900">{{ $emphasis->title }}</h4>
                                    @if ($emphasis->source_excerpt)
                                        <p class="mt-2 border-s-2 border-emerald-300 ps-3 text-sm text-slate-600">{{ $emphasis->source_excerpt }}</p>
                                    @endif
                                    <p class="mt-2 text-sm leading-6 text-slate-700"><span class="font-semibold">{{ __('ui.requests.interpretation') }}:</span> {{ $emphasis->interpretation }}</p>
                                </div>
                            @empty
                                <p class="p-4 text-sm text-slate-500">{{ __('ui.requests.no_emphases') }}</p>
                            @endforelse
                        </div>
                    </article>

                    <article class="overflow-hidden rounded-xl border-2 border-amber-300 bg-white shadow-sm">
                        <header class="border-b border-amber-200 bg-amber-50 px-4 py-3">
                            <p class="text-xs font-semibold uppercase tracking-wide text-amber-700">&laquo;{{ __('ui.requests.database_record') }}&raquo;</p>
                            <h3 class="mt-1 font-mono font-bold text-slate-950">ProposalVersion</h3>
                            <p class="mt-1 text-xs text-slate-600">{{ $projectRequest->proposals->count() }} {{ __('ui.requests.proposals') }}</p>
                        </header>
                        <div class="divide-y divide-slate-100">
                            @forelse ($projectRequest->proposals as $proposal)
                                <div class="p-4">
                                    <p class="font-mono text-xs text-slate-500">#{{ $proposal->version }} · {{ $proposal->status }}</p>
                                    <p class="mt-1 text-sm text-slate-800">
                                        {{ $proposal->price_amount !== null ? number_format((float) $proposal->price_amount, 2) . ' ' . $proposal->currency : __('ui.requests.not_recorded') }}
                                        <span class="px-1 text-slate-400">·</span>
                                        {{ $proposal->delivery_days ? __('ui.requests.days_value', ['count' => $proposal->delivery_days]) : __('ui.requests.not_recorded') }}
                                    </p>
                                    <p class="mt-2 text-xs text-slate-500">{{ __('ui.requests.proposal_locale') }}: {{ $proposal->content_locale ?: __('ui.requests.not_recorded') }}</p>
                                    <div class="mt-3 rounded-lg bg-slate-50 p-3">
                                        <p class="text-xs font-semibold text-slate-500">{{ __('ui.requests.proposal_idea') }}</p>
                                        <p class="mt-2 whitespace-pre-line text-sm leading-6 text-slate-700">{{ $proposal->key_idea ?: __('ui.requests.not_recorded') }}</p>
                                    </div>
                                </div>
                            @empty
                                <p class="p-4 text-sm text-slate-500">{{ __('ui.requests.no_proposals') }}</p>
                            @endforelse
                        </div>
                    </article>
                </div>
            </div>
        </div>
        <p class="mt-3 text-xs text-slate-500">{{ __('ui.requests.uml_legend') }}</p>
    </section>

    <div class="grid gap-6 xl:grid-cols-2">
        <section class="panel p-5 sm:p-6">
            <h2 class="text-base font-semibold text-slate-950">{{ __('ui.requests.request') }}</h2>
            <p class="mt-3 whitespace-pre-line text-sm leading-7 text-slate-700">{{ $projectRequest->request_body }}</p>
        </section>

        <section class="panel p-5 sm:p-6">
            <h2 class="text-base font-semibold text-slate-950">{{ __('ui.requests.source_and_capture') }}</h2>
            <dl class="mt-3 divide-y divide-slate-100 text-sm">
                @foreach ([
                    'source_url' => $projectRequest->source_url,
                    'external_id' => $projectRequest->external_id,
                    'posted_at' => $projectRequest->posted_at
                        ? $projectRequest->posted_at->setTimezone($projectRequest->source_timezone ?: config('app.timezone'))->format('Y-m-d H:i:s T')
                        : null,
                    'posted_at_basis' => match ($projectRequest->posted_at_basis) {
                        'source_absolute' => __('ui.requests.source_absolute'),
                        'derived_relative' => __('ui.requests.derived_relative'),
                        default => __('ui.requests.unknown'),
                    },
                    'captured_at' => $projectRequest->captured_at
                        ? $projectRequest->captured_at->setTimezone($projectRequest->source_timezone ?: config('app.timezone'))->format('Y-m-d H:i:s T')
                        : null,
                    'source_timezone' => $projectRequest->source_timezone,
                    'posted_time_text' => $projectRequest->posted_time_text,
                ] as $label => $value)
                    <div class="flex flex-col justify-between gap-1 py-2 sm:flex-row">
                        <dt class="font-medium text-slate-500">{{ __('ui.requests.' . $label) }}</dt>
                        <dd class="break-all text-slate-800">
                            @if ($label === 'source_url' && filled($value))
                                <a class="text-indigo-700 underline" href="{{ $value }}" target="_blank" rel="noopener noreferrer">{{ $value }}</a>
                            @else
                                {{ filled($value) ? $value : __('ui.requests.not_recorded') }}
                            @endif
                        </dd>
                    </div>
                @endforeach
            </dl>
        </section>

        <section class="panel p-5 sm:p-6">
            <h2 class="text-base font-semibold text-slate-950">{{ __('ui.requests.client_details') }}</h2>
            <dl class="mt-3 divide-y divide-slate-100 text-sm">
                @foreach ([
                    'client_location' => $projectRequest->client_location_text ?: collect([$projectRequest->client_city, $projectRequest->client_region, $projectRequest->client_country])->filter()->implode(', '),
                    'client_is_new' => $projectRequest->client_is_new === null ? null : ($projectRequest->client_is_new ? __('ui.common.yes') : __('ui.common.no')),
                    'client_reviews_count' => $projectRequest->client_reviews_count,
                    'client_rating' => $projectRequest->client_rating !== null
                        ? $projectRequest->client_rating . ($projectRequest->client_rating_scale ? ' / ' . $projectRequest->client_rating_scale : '')
                        : null,
                    'client_payment_verified' => $projectRequest->client_payment_verified === null ? null : ($projectRequest->client_payment_verified ? __('ui.common.yes') : __('ui.common.no')),
                    'applicants_count' => $projectRequest->applicants_count,
                    'client_deadline' => $projectRequest->client_deadline_text ?: ($projectRequest->client_deadline_days ? __('ui.requests.days_value', ['count' => $projectRequest->client_deadline_days]) : null),
                    'is_full_time' => $projectRequest->is_full_time_known ? ($projectRequest->is_full_time ? __('ui.common.yes') : __('ui.common.no')) : null,
                    'is_featured' => $projectRequest->is_featured === null ? null : ($projectRequest->is_featured ? __('ui.common.yes') : __('ui.common.no')),
                ] as $label => $value)
                    <div class="flex flex-col justify-between gap-1 py-2 sm:flex-row">
                        <dt class="font-medium text-slate-500">{{ __('ui.requests.' . $label) }}</dt>
                        <dd class="break-words text-slate-800">{{ filled($value) ? $value : __('ui.requests.not_recorded') }}</dd>
                    </div>
                @endforeach
            </dl>
        </section>
    </div>

    @if ($projectRequest->metadata)
        <section class="panel mt-6 p-5 sm:p-6">
            <h2 class="text-base font-semibold text-slate-950">{{ __('ui.requests.additional_metadata') }}</h2>
            <pre class="mt-3 overflow-x-auto rounded-lg bg-slate-50 p-4 text-xs leading-6 text-slate-700">{{ json_encode($projectRequest->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
        </section>
    @endif
</x-layouts.app>
