@props(['title' => null, 'description' => null])

<div class="px-6 py-14 text-center">
    <div class="mx-auto flex size-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-500">
        <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8 7.5h8m-8 4.5h8m-8 4.5h5M5.25 3.75h13.5v16.5H5.25z" /></svg>
    </div>
    <h3 class="mt-4 text-sm font-semibold text-slate-900">{{ $title ?? __('ui.common.no_data') }}</h3>
    <p class="mx-auto mt-1 max-w-sm text-sm leading-6 text-slate-500">{{ $description ?? __('ui.common.no_data_hint') }}</p>
</div>
