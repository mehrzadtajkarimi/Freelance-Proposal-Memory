<x-layouts.app :title="__('ui.payment_rules.create_title')">
    <x-page-heading :title="__('ui.payment_rules.create_title')" :description="__('ui.payment_rules.hint')">
        <x-slot:action>
            <a href="{{ route('payment-rules.index') }}" class="btn-secondary">{{ __('ui.common.back') }}</a>
        </x-slot:action>
    </x-page-heading>

    @include('payment-rules.form', ['action' => route('payment-rules.store'), 'method' => 'POST'])
</x-layouts.app>
