<x-layouts.app :title="__('ui.payment_rules.title')">
    <x-page-heading :title="__('ui.payment_rules.title')" :description="__('ui.payment_rules.description')">
        <x-slot:action>
            <a href="{{ route('payment-rules.create') }}" class="btn-primary">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14" /></svg>
                {{ __('ui.payment_rules.add') }}
            </a>
        </x-slot:action>
    </x-page-heading>

    <div class="space-y-4">
        @forelse ($rules as $rule)
            @php
                if ($rule->minimum_amount_toman !== null && $rule->maximum_amount_toman !== null) {
                    $range = __('ui.payment_rules.between', [
                        'minimum' => number_format($rule->minimum_amount_toman),
                        'maximum' => number_format($rule->maximum_amount_toman),
                    ]);
                } elseif ($rule->minimum_amount_toman !== null) {
                    $range = __('ui.payment_rules.from', ['amount' => number_format($rule->minimum_amount_toman)]);
                } else {
                    $range = __('ui.payment_rules.up_to', ['amount' => number_format($rule->maximum_amount_toman)]);
                }
            @endphp
            <article class="panel p-5 sm:p-6">
                <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-start">
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="text-base font-semibold text-slate-950">{{ $rule->{'name_'.app()->getLocale()} ?? $rule->name_fa }}</h2>
                            <span class="rounded-full bg-teal-50 px-2.5 py-1 text-[11px] font-semibold text-teal-800">{{ $range }}</span>
                        </div>
                        <ol class="mt-4 space-y-3">
                            @foreach ($rule->stages as $stage)
                                @php($language = app()->getLocale())
                                <li class="rounded-xl border border-slate-100 bg-slate-50 px-4 py-3">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <h3 class="text-sm font-semibold text-slate-800">{{ $stage['title_'.$language] ?? $stage['title_fa'] }}</h3>
                                        <span class="text-sm font-bold text-teal-800">{{ $stage['percentage'] }}{{ __('ui.payment_rules.percent_sign') }}</span>
                                    </div>
                                    <p class="mt-1 whitespace-pre-line text-sm leading-6 text-slate-600">{{ $stage['description_'.$language] ?? $stage['description_fa'] }}</p>
                                </li>
                            @endforeach
                        </ol>
                    </div>
                    <div class="flex shrink-0 gap-2">
                        <a href="{{ route('payment-rules.edit', $rule) }}" class="btn-secondary">{{ __('ui.payment_rules.edit') }}</a>
                        <form method="POST" action="{{ route('payment-rules.destroy', $rule) }}" onsubmit="return confirm('{{ __('ui.payment_rules.confirm_delete') }}')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-secondary text-rose-700 hover:text-rose-800">{{ __('ui.payment_rules.delete') }}</button>
                        </form>
                    </div>
                </div>
            </article>
        @empty
            <section class="panel"><x-empty-state :title="__('ui.payment_rules.no_rules')" :description="__('ui.payment_rules.no_rules_hint')" /></section>
        @endforelse
    </div>
</x-layouts.app>
