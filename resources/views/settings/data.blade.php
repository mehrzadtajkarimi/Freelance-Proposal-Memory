<x-layouts.app :title="__('ui.data.title')">
    <x-page-heading :title="__('ui.data.title')" :description="__('ui.data.description')" />

    <section class="grid gap-6 xl:grid-cols-2">
        <div class="panel p-5 sm:p-7">
            <h2 class="text-lg font-semibold text-slate-950">{{ __('ui.data.export_title') }}</h2>
            <p class="mt-2 text-sm leading-6 text-slate-600">{{ __('ui.data.export_description') }}</p>
            <a class="btn-primary mt-5 inline-flex" href="{{ route('settings.data.export') }}">{{ __('ui.data.export') }}</a>
        </div>

        <div class="panel p-5 sm:p-7">
            <h2 class="text-lg font-semibold text-slate-950">{{ __('ui.data.import_title') }}</h2>
            <p class="mt-2 text-sm leading-6 text-slate-600">{{ __('ui.data.import_description') }}</p>
            <form method="POST" action="{{ route('settings.data.import') }}" enctype="multipart/form-data" class="mt-5 space-y-4">
                @csrf
                <div>
                    <label class="label" for="data_file">{{ __('ui.data.file') }}</label>
                    <input class="field" id="data_file" name="file" type="file" accept=".json,application/json" required>
                </div>
                <button class="btn-primary" type="submit">{{ __('ui.data.import') }}</button>
            </form>
        </div>
    </section>
</x-layouts.app>
