@php
    $stageRows = old('stages', $rule->stages ?? []);
    if ($stageRows === []) {
        $stageRows = [[
            'percentage' => '',
            'title_fa' => '',
            'title_en' => '',
            'description_fa' => '',
            'description_en' => '',
        ]];
    }
@endphp

<form method="POST" action="{{ $action }}" class="panel p-5 sm:p-7">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <section>
        <h2 class="text-base font-semibold text-slate-950">{{ __('ui.payment_rules.range') }}</h2>
        <div class="mt-4 grid gap-4 sm:grid-cols-2">
            <div>
                <label class="label" for="name_fa">{{ __('ui.payment_rules.name_fa') }}</label>
                <input class="field" id="name_fa" name="name_fa" value="{{ old('name_fa', $rule->name_fa) }}" maxlength="160" required>
            </div>
            <div>
                <label class="label" for="name_en">{{ __('ui.payment_rules.name_en') }}</label>
                <input class="field" id="name_en" name="name_en" value="{{ old('name_en', $rule->name_en) }}" maxlength="160" required>
            </div>
            <div class="grid gap-4 sm:col-span-2 sm:grid-cols-2">
                <div>
                    <label class="label" for="minimum_amount_toman">{{ __('ui.payment_rules.minimum') }}</label>
                    <input class="field" id="minimum_amount_toman" name="minimum_amount_toman" type="number" min="0" max="9000000000000000000" step="1" value="{{ old('minimum_amount_toman', $rule->minimum_amount_toman) }}" inputmode="numeric">
                </div>
                <div>
                    <label class="label" for="maximum_amount_toman">{{ __('ui.payment_rules.maximum') }}</label>
                    <input class="field" id="maximum_amount_toman" name="maximum_amount_toman" type="number" min="0" max="9000000000000000000" step="1" value="{{ old('maximum_amount_toman', $rule->maximum_amount_toman) }}" inputmode="numeric">
                </div>
            </div>
        </div>
        <p class="mt-3 text-sm leading-6 text-slate-500">{{ __('ui.payment_rules.hint') }}</p>
    </section>

    <section class="mt-8 border-t border-slate-100 pt-6">
        <h2 class="text-base font-semibold text-slate-950">{{ __('ui.payment_rules.stages') }}</h2>
        <div id="payment-stage-list" class="mt-5 space-y-4">
            @foreach ($stageRows as $index => $stage)
                <fieldset class="rounded-xl border border-slate-200 p-4 sm:p-5" data-payment-stage data-stage-index="{{ $index }}">
                    <legend class="mb-4 flex w-full items-center justify-between gap-3 text-sm font-semibold text-slate-800">
                        <span data-stage-title data-template="{{ __('ui.payment_rules.stage', ['number' => '__NUMBER__']) }}">{{ __('ui.payment_rules.stage', ['number' => $loop->iteration]) }}</span>
                        <button type="button" class="btn-secondary px-3 py-2 text-sm" data-remove-stage>{{ __('ui.payment_rules.remove_stage') }}</button>
                    </legend>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="label" for="stage-{{ $index }}-percentage">{{ __('ui.payment_rules.percentage') }}</label>
                            <div class="flex items-center gap-2">
                                <input class="field" id="stage-{{ $index }}-percentage" name="stages[{{ $index }}][percentage]" type="number" min="1" max="100" step="1" value="{{ $stage['percentage'] ?? '' }}" required>
                                <span class="text-sm text-slate-500">{{ __('ui.payment_rules.percent_sign') }}</span>
                            </div>
                        </div>
                        <div>
                            <label class="label" for="stage-{{ $index }}-title-fa">{{ __('ui.payment_rules.title_fa') }}</label>
                            <input class="field" id="stage-{{ $index }}-title-fa" name="stages[{{ $index }}][title_fa]" value="{{ $stage['title_fa'] ?? '' }}" maxlength="160" required>
                        </div>
                        <div>
                            <label class="label" for="stage-{{ $index }}-title-en">{{ __('ui.payment_rules.title_en') }}</label>
                            <input class="field" id="stage-{{ $index }}-title-en" name="stages[{{ $index }}][title_en]" value="{{ $stage['title_en'] ?? '' }}" maxlength="160" required>
                        </div>
                        <div>
                            <label class="label" for="stage-{{ $index }}-description-fa">{{ __('ui.payment_rules.description_fa') }}</label>
                            <textarea class="field min-h-24 resize-y leading-6" id="stage-{{ $index }}-description-fa" name="stages[{{ $index }}][description_fa]" rows="3" maxlength="2000" required>{{ $stage['description_fa'] ?? '' }}</textarea>
                        </div>
                        <div>
                            <label class="label" for="stage-{{ $index }}-description-en">{{ __('ui.payment_rules.description_en') }}</label>
                            <textarea class="field min-h-24 resize-y leading-6" id="stage-{{ $index }}-description-en" name="stages[{{ $index }}][description_en]" rows="3" maxlength="2000" required>{{ $stage['description_en'] ?? '' }}</textarea>
                        </div>
                    </div>
                </fieldset>
            @endforeach
        </div>

        <button type="button" id="add-payment-stage" class="btn-secondary mt-4">{{ __('ui.payment_rules.add_stage') }}</button>
    </section>

    <div class="mt-7 flex justify-end border-t border-slate-100 pt-5">
        <button type="submit" class="btn-primary">{{ __('ui.common.save') }}</button>
    </div>
</form>

<template id="payment-stage-template">
    <fieldset class="rounded-xl border border-slate-200 p-4 sm:p-5" data-payment-stage data-stage-index="__INDEX__">
        <legend class="mb-4 flex w-full items-center justify-between gap-3 text-sm font-semibold text-slate-800">
            <span data-stage-title data-template="{{ __('ui.payment_rules.stage', ['number' => '__NUMBER__']) }}">{{ __('ui.payment_rules.stage', ['number' => '__NUMBER__']) }}</span>
            <button type="button" class="btn-secondary px-3 py-2 text-sm" data-remove-stage>{{ __('ui.payment_rules.remove_stage') }}</button>
        </legend>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="label" for="stage-__INDEX__-percentage">{{ __('ui.payment_rules.percentage') }}</label>
                <div class="flex items-center gap-2">
                    <input class="field" id="stage-__INDEX__-percentage" name="stages[__INDEX__][percentage]" type="number" min="1" max="100" step="1" required>
                    <span class="text-sm text-slate-500">{{ __('ui.payment_rules.percent_sign') }}</span>
                </div>
            </div>
            <div>
                <label class="label" for="stage-__INDEX__-title-fa">{{ __('ui.payment_rules.title_fa') }}</label>
                <input class="field" id="stage-__INDEX__-title-fa" name="stages[__INDEX__][title_fa]" maxlength="160" required>
            </div>
            <div>
                <label class="label" for="stage-__INDEX__-title-en">{{ __('ui.payment_rules.title_en') }}</label>
                <input class="field" id="stage-__INDEX__-title-en" name="stages[__INDEX__][title_en]" maxlength="160" required>
            </div>
            <div>
                <label class="label" for="stage-__INDEX__-description-fa">{{ __('ui.payment_rules.description_fa') }}</label>
                <textarea class="field min-h-24 resize-y leading-6" id="stage-__INDEX__-description-fa" name="stages[__INDEX__][description_fa]" rows="3" maxlength="2000" required></textarea>
            </div>
            <div>
                <label class="label" for="stage-__INDEX__-description-en">{{ __('ui.payment_rules.description_en') }}</label>
                <textarea class="field min-h-24 resize-y leading-6" id="stage-__INDEX__-description-en" name="stages[__INDEX__][description_en]" rows="3" maxlength="2000" required></textarea>
            </div>
        </div>
    </fieldset>
</template>

<script>
    const paymentStageList = document.getElementById('payment-stage-list');
    const paymentStageTemplate = document.getElementById('payment-stage-template');
    const existingStageIndices = [...paymentStageList.querySelectorAll('[data-stage-index]')]
        .map((stage) => Number.parseInt(stage.dataset.stageIndex, 10))
        .filter(Number.isFinite);
    let nextPaymentStageIndex = existingStageIndices.length ? Math.max(...existingStageIndices) + 1 : 0;

    document.getElementById('add-payment-stage').addEventListener('click', () => {
        const number = paymentStageList.children.length + 1;
        const row = paymentStageTemplate.innerHTML
            .replaceAll('__INDEX__', String(nextPaymentStageIndex++))
            .replaceAll('__NUMBER__', String(number));
        paymentStageList.insertAdjacentHTML('beforeend', row);
        updatePaymentStageLabels();
    });

    paymentStageList.addEventListener('click', (event) => {
        const button = event.target.closest('[data-remove-stage]');
        if (!button) return;

        button.closest('[data-payment-stage]').remove();
        updatePaymentStageLabels();
    });

    function updatePaymentStageLabels() {
        paymentStageList.querySelectorAll('[data-stage-title]').forEach((label, index) => {
            label.textContent = label.dataset.template.replace('__NUMBER__', String(index + 1));
        });
    }
</script>
