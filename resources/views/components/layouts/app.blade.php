@props(['title' => null])

<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'fa' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? __('ui.app.name') }} · {{ __('ui.app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen">
    <div class="min-h-screen lg:flex">
        <aside class="flex shrink-0 flex-col bg-slate-950 text-white lg:min-h-screen lg:w-64">
            <div class="flex items-center gap-3 px-6 py-6">
                <div class="flex size-11 items-center justify-center rounded-2xl bg-teal-500 text-lg font-bold text-slate-950">PM</div>
                <div>
                    <p class="font-semibold tracking-tight">{{ __('ui.app.name') }}</p>
                    <p class="mt-0.5 text-xs text-slate-400">{{ __('ui.app.eyebrow') }}</p>
                </div>
            </div>

            <div class="px-4 pb-2 pt-3 text-[11px] font-semibold tracking-[0.18em] text-slate-500">{{ __('ui.nav.workspace') }}</div>
            <nav class="flex gap-1 overflow-x-auto px-3 pb-4 lg:flex-col lg:overflow-visible" aria-label="{{ __('ui.app.menu') }}">
                @php
                    $navigation = [
                        ['route' => 'dashboard', 'label' => 'ui.nav.overview', 'icon' => 'M3 10.5 12 3l9 7.5M5.25 9v11.25h13.5V9M9 20.25v-6h6v6'],
                        ['route' => 'requests.index', 'label' => 'ui.nav.requests', 'icon' => 'M7.5 3.75h9A2.25 2.25 0 0 1 18.75 6v14.25H5.25V6A2.25 2.25 0 0 1 7.5 3.75ZM8.25 8.25h7.5M8.25 12h7.5M8.25 15.75h4.5'],
                        ['route' => 'rules.*', 'label' => 'ui.nav.rules', 'icon' => 'M12 3.75 5.25 6v5.25c0 4.18 2.88 7.9 6.75 9 3.87-1.1 6.75-4.82 6.75-9V6L12 3.75ZM9 12l1.9 1.9L15 9.75'],
                        ['route' => 'payment-rules.*', 'href' => 'payment-rules.index', 'label' => 'ui.nav.payment_rules', 'icon' => 'M3.75 7.5h16.5M6.75 12h3m-3 4.5h7.5M5.25 4.5h13.5A2.25 2.25 0 0 1 21 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 17.25V6.75A2.25 2.25 0 0 1 5.25 4.5Z'],
                        ['route' => 'reports.index', 'label' => 'ui.nav.reports', 'icon' => 'M4.5 19.5V12m5.25 7.5V6m5.25 13.5v-9m5.25 9V3.75'],
                        ['route' => 'settings.freelancer.edit', 'label' => 'ui.nav.freelancer_profile', 'icon' => 'M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm7.5 8.25a7.5 7.5 0 0 0-15 0'],
                        ['route' => 'settings.data', 'label' => 'ui.nav.personal_data', 'icon' => 'M12 3.75v16.5m8.25-8.25H3.75m2.4-5.85 11.7 11.7m0-11.7L6.15 17.85'],
                    ];
                @endphp
                @foreach ($navigation as $item)
                    @php($active = request()->routeIs($item['route']))
                    <a href="{{ route($item['href'] ?? ($item['route'] === 'rules.*' ? 'rules.index' : $item['route'])) }}"
                       @if ($active) aria-current="page" @endif
                       class="flex shrink-0 items-center gap-3 rounded-xl px-3.5 py-3 text-sm font-medium transition {{ $active ? 'bg-teal-500/15 text-teal-300 ring-1 ring-inset ring-teal-400/15' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">
                        <svg class="size-[18px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{{ $item['icon'] }}" /></svg>
                        <span>{{ __($item['label']) }}</span>
                    </a>
                @endforeach
            </nav>

            <div class="mt-auto hidden p-5 lg:block">
                <div class="rounded-2xl border border-white/10 bg-white/[0.04] p-4">
                    <div class="mb-3 flex size-8 items-center justify-center rounded-xl bg-white/10 text-teal-300">
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M12 3v18M3 12h18" /></svg>
                    </div>
                    <p class="text-sm font-semibold">{{ __('ui.app.subtitle') }}</p>
                    <p class="mt-2 text-xs leading-5 text-slate-400">{{ __('ui.dashboard.description') }}</p>
                </div>
            </div>
        </aside>

        <main class="min-w-0 flex-1">
            <header class="border-b border-slate-200/80 bg-white/85 px-5 py-4 backdrop-blur sm:px-8">
                <div class="mx-auto flex max-w-7xl items-center justify-between gap-4">
                    <div class="min-w-0">
                        <p class="truncate text-xs font-medium text-slate-500">{{ __('ui.app.today') }}</p>
                        <p class="mt-1 truncate text-sm font-semibold text-slate-900">{{ __('ui.app.subtitle') }}</p>
                    </div>
                    <div class="flex shrink-0 items-center gap-3">
                        <span class="hidden text-xs font-medium text-slate-500 sm:inline">{{ __('ui.app.language') }}</span>
                        <div class="flex items-center rounded-xl border border-slate-200 bg-slate-50 p-1">
                            @foreach (['fa' => __('ui.app.persian'), 'en' => __('ui.app.english')] as $locale => $label)
                                <form method="POST" action="{{ route('locale.update', $locale) }}">
                                    @csrf
                                    <input type="hidden" name="return_to" value="{{ request()->getRequestUri() }}">
                                    <button type="submit" @if (app()->getLocale() === $locale) aria-current="true" @endif
                                            class="rounded-lg px-2.5 py-1.5 text-xs font-semibold transition {{ app()->getLocale() === $locale ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-800' }}">
                                        {{ $label }}
                                    </button>
                                </form>
                            @endforeach
                        </div>
                    </div>
                </div>
            </header>

            <div class="mx-auto max-w-7xl px-5 py-7 sm:px-8 sm:py-9">
                @if (session('success'))
                    <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800" role="status">{{ session('success') }}</div>
                @endif
                @if ($errors->any())
                    <div class="mb-6 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800" role="alert">
                        <p class="font-semibold">{{ __('ui.flash.error') }}</p>
                        <ul class="mt-2 list-inside list-disc space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                {{ $slot }}
            </div>
        </main>
    </div>
</body>
</html>
