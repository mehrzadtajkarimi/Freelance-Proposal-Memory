<x-layouts.app :title="__('ui.payment_rules.edit_title')">
    <x-page-heading :title="__('ui.payment_rules.edit_title')" :description="$rule->{'name_'.app()->getLocale()}">
        <x-slot:action>
            <a href="{{ route('payment-rules.index') }}" class="btn-secondary">{{ __('ui.common.back') }}</a>
        </x-slot:action>
    </x-page-heading>

    @include('payment-rules.form', ['action' => route('payment-rules.update', $rule), 'method' => 'PUT'])
</x-layouts.app>
