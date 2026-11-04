<x-layouts.app :title="__('ui.about.title')">
    <x-page-heading :title="__('ui.about.title')" :description="__('ui.about.description')" />

    <section class="panel p-5 sm:p-7" aria-labelledby="about-workflow-title">
        <div class="max-w-3xl">
            <h2 id="about-workflow-title" class="text-lg font-semibold text-slate-950">{{ __('ui.about.workflow_title') }}</h2>
            <p class="mt-2 text-sm leading-6 text-slate-500">{{ __('ui.about.workflow_description') }}</p>
        </div>

        <ol class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach (__('ui.about.steps') as $step)
                <li class="rounded-2xl border border-slate-200 bg-slate-50/70 p-5">
                    <div class="flex items-start gap-3">
                        <span class="flex size-8 shrink-0 items-center justify-center rounded-xl bg-teal-100 text-sm font-bold text-teal-800">{{ $loop->iteration }}</span>
                        <div>
                            <h3 class="font-semibold leading-6 text-slate-900">{{ $step['title'] }}</h3>
                            <p class="mt-2 text-sm leading-6 text-slate-600">{{ $step['body'] }}</p>
                        </div>
                    </div>
                </li>
            @endforeach
        </ol>
    </section>

    <section class="mt-6 grid gap-4 lg:grid-cols-2">
        @foreach (['rules', 'payment', 'archive', 'example'] as $section)
            <article class="panel p-5 sm:p-6">
                <h2 class="font-semibold text-slate-950">{{ __('ui.about.'.$section.'_title') }}</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">{{ __('ui.about.'.$section.'_body') }}</p>
            </article>
        @endforeach
    </section>

    <aside class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 p-5 sm:p-6">
        <h2 class="font-semibold text-amber-950">{{ __('ui.about.limits_title') }}</h2>
        <p class="mt-2 text-sm leading-6 text-amber-900">{{ __('ui.about.limits_body') }}</p>
    </aside>
</x-layouts.app>
