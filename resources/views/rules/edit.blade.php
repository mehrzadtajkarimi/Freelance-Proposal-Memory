<x-layouts.app :title="__('ui.rules.edit_title')">
    <x-page-heading :title="__('ui.rules.edit_title')" :description="$rule->title">
        <x-slot:action><a href="{{ route('rules.show', $rule) }}" class="btn-secondary">{{ __('ui.common.back') }}</a></x-slot:action>
    </x-page-heading>

    @php($currentVersion = $rule->versions->first(fn ($version) => $version->version === $rule->current_version && $version->status === 'approved'))
    <form method="POST" action="{{ route('rules.update', $rule) }}" class="panel max-w-3xl p-5 sm:p-7">
        @csrf
        @method('PUT')
        <div>
            <label class="label" for="body">{{ __('ui.rules.body') }}</label>
            <textarea class="field min-h-48 resize-y leading-6" id="body" name="body" rows="9" required maxlength="20000">{{ old('body', $currentVersion?->body ?? $rule->versions->first()?->body) }}</textarea>
        </div>
        <div class="mt-5">
            <label class="label" for="change_summary">{{ __('ui.rules.change_summary') }}</label>
            <textarea class="field min-h-24 resize-y leading-6" id="change_summary" name="change_summary" rows="3" required maxlength="2000">{{ old('change_summary') }}</textarea>
        </div>
        <div class="mt-6 flex flex-col-reverse gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:justify-end">
            <a href="{{ route('rules.show', $rule) }}" class="btn-secondary">{{ __('ui.common.cancel') }}</a>
            <button type="submit" class="btn-primary">{{ __('ui.rules.propose') }}</button>
        </div>
    </form>
</x-layouts.app>
