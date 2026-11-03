<x-layouts.app :title="__('ui.onboarding.title')">
    <x-page-heading :title="__('ui.onboarding.title')" :description="__('ui.onboarding.description')" />

    <section class="grid gap-6 xl:grid-cols-2">
        <div class="panel p-5 sm:p-7">
            <div class="mb-5 flex size-12 items-center justify-center rounded-2xl bg-teal-50 text-teal-700">
                <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 16V4m0 0L7.5 8.5M12 4l4.5 4.5M5 14.5v4A1.5 1.5 0 0 0 6.5 20h11a1.5 1.5 0 0 0 1.5-1.5v-4" /></svg>
            </div>
            <h2 class="text-lg font-semibold text-slate-950">{{ __('ui.onboarding.import_title') }}</h2>
            <p class="mt-2 text-sm leading-6 text-slate-600">{{ __('ui.onboarding.import_description') }}</p>
            <form method="POST" action="{{ route('onboarding.import') }}" enctype="multipart/form-data" class="mt-5 space-y-4">
                @csrf
                <div>
                    <label class="label" for="onboarding_file">{{ __('ui.data.file') }}</label>
                    <input class="field" id="onboarding_file" name="file" type="file" accept=".json,application/json" required>
                </div>
                <button class="btn-primary w-full sm:w-auto" type="submit">{{ __('ui.onboarding.import_continue') }}</button>
            </form>
        </div>

        <div class="panel p-5 sm:p-7">
            <div class="mb-5 flex size-12 items-center justify-center rounded-2xl bg-sky-50 text-sky-700">
                <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v16H6.5A2.5 2.5 0 0 0 4 21.5v-16ZM4 17a2.5 2.5 0 0 1 2.5-2.5H20M8 7h8m-8 3.5h6" /></svg>
            </div>
            <h2 class="text-lg font-semibold text-slate-950">{{ __('ui.onboarding.guided_title') }}</h2>
            <p class="mt-2 text-sm leading-6 text-slate-600">{{ __('ui.onboarding.guided_description') }}</p>
            <form method="POST" action="{{ route('onboarding.start') }}" class="mt-5 flex flex-col gap-3 sm:flex-row">
                @csrf
                <button class="btn-primary" type="submit" name="action" value="start">{{ __('ui.onboarding.start') }}</button>
                <button class="btn-secondary" type="submit" name="action" value="skip">{{ __('ui.onboarding.skip_all') }}</button>
            </form>
        </div>
    </section>

    <p class="mt-6 text-center text-xs leading-5 text-slate-500">
        {{ __('ui.onboarding.data_note') }}
        <a class="font-semibold text-teal-700 hover:text-teal-900" href="{{ route('settings.data') }}">{{ __('ui.onboarding.data_settings') }}</a>
    </p>
</x-layouts.app>
