<x-layouts.app :title="__('ui.onboarding.steps.'.$step)">
    <div class="mx-auto max-w-3xl">
        <div class="mb-5 flex items-center justify-between gap-4">
            <span class="rounded-full bg-teal-50 px-3 py-1.5 text-xs font-semibold text-teal-800">{{ __('ui.onboarding.priority', ['number' => $stepNumber]) }}</span>
            <span class="text-xs font-medium text-slate-500">{{ __('ui.onboarding.progress', ['current' => $stepNumber, 'total' => $totalSteps]) }}</span>
        </div>
        <div class="mb-7 h-2 overflow-hidden rounded-full bg-slate-200" role="progressbar" aria-valuemin="1" aria-valuemax="{{ $totalSteps }}" aria-valuenow="{{ $stepNumber }}">
            <div class="h-full rounded-full bg-teal-600 transition-all" style="width: {{ ($stepNumber / $totalSteps) * 100 }}%"></div>
        </div>

        <section class="panel p-5 sm:p-8">
            <h1 class="text-2xl font-bold tracking-tight text-slate-950">{{ __('ui.onboarding.steps.'.$step) }}</h1>
            <p class="mt-2 text-sm leading-6 text-slate-600">{{ __('ui.onboarding.step_descriptions.'.$step) }}</p>

            <form method="POST" action="{{ route('onboarding.save-step', $step) }}" class="mt-7 space-y-5">
                @csrf
                @if ($step === 'profile')
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label class="label" for="display_name">{{ __('ui.profile.display_name') }}</label>
                            <input class="field" id="display_name" name="display_name" maxlength="120" value="{{ old('display_name', $profileValues['display_name'] ?? '') }}" autocomplete="name">
                        </div>
                        <div>
                            <label class="label" for="professional_title">{{ __('ui.profile.professional_title') }}</label>
                            <input class="field" id="professional_title" name="professional_title" maxlength="160" value="{{ old('professional_title', $profileValues['professional_title'] ?? '') }}">
                        </div>
                        <div>
                            <label class="label" for="experience_years">{{ __('ui.profile.experience_years') }}</label>
                            <input class="field" id="experience_years" name="experience_years" type="number" min="0" max="60" value="{{ old('experience_years', $profileValues['experience_years'] ?? '') }}" inputmode="numeric">
                        </div>
                        <div>
                            <label class="label" for="location">{{ __('ui.profile.location') }}</label>
                            <input class="field" id="location" name="location" maxlength="120" value="{{ old('location', $profileValues['location'] ?? '') }}" autocomplete="address-level2">
                        </div>
                        <div>
                            <label class="label" for="portfolio_url">{{ __('ui.profile.portfolio_url') }}</label>
                            <input class="field" id="portfolio_url" name="portfolio_url" type="url" maxlength="2048" value="{{ old('portfolio_url', $profile?->portfolio_url ?? '') }}" inputmode="url">
                        </div>
                        <div>
                            <label class="label" for="website_url">{{ __('ui.profile.website_url') }}</label>
                            <input class="field" id="website_url" name="website_url" type="url" maxlength="2048" value="{{ old('website_url', $profile?->website_url ?? '') }}" inputmode="url">
                        </div>
                    </div>
                @elseif ($step === 'expertise')
                    <div class="space-y-5">
                        <div>
                            <label class="label" for="services">{{ __('ui.profile.services') }}</label>
                            <textarea class="field min-h-24 resize-y leading-6" id="services" name="services" rows="3">{{ old('services', implode("\n", $profileValues['services'] ?? [])) }}</textarea>
                            <p class="mt-1 text-xs text-slate-500">{{ __('ui.profile.list_hint') }}</p>
                        </div>
                        <div>
                            <label class="label" for="technologies">{{ __('ui.profile.technologies') }}</label>
                            <textarea class="field min-h-24 resize-y leading-6" id="technologies" name="technologies" rows="3">{{ old('technologies', implode("\n", $profileValues['technologies'] ?? [])) }}</textarea>
                        </div>
                        <div>
                            <label class="label" for="languages">{{ __('ui.profile.languages') }}</label>
                            <textarea class="field min-h-20 resize-y leading-6" id="languages" name="languages" rows="2">{{ old('languages', implode("\n", $profileValues['languages'] ?? [])) }}</textarea>
                        </div>
                        <div>
                            <label class="label" for="bio">{{ __('ui.profile.bio') }}</label>
                            <textarea class="field min-h-24 resize-y leading-6" id="bio" name="bio" rows="3" maxlength="4000">{{ old('bio', $profileValues['bio'] ?? '') }}</textarea>
                        </div>
                    </div>
                @else
                    <div>
                        <label class="label" for="title">{{ __('ui.common.title') }}</label>
                        <input class="field" id="title" name="title" maxlength="255" value="{{ old('title') }}">
                    </div>
                    <div>
                        <label class="label" for="body">{{ __('ui.rules.body') }}</label>
                        <textarea class="field min-h-48 resize-y leading-6" id="body" name="body" rows="8" maxlength="20000">{{ old('body') }}</textarea>
                    </div>
                @endif

                <div class="flex flex-col-reverse gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:items-center sm:justify-between">
                    <button class="btn-secondary" type="submit" form="skip-step">{{ __('ui.onboarding.skip_question') }}</button>
                    <button class="btn-primary" type="submit">{{ $nextStep ? __('ui.onboarding.continue') : __('ui.onboarding.finish') }}</button>
                </div>
            </form>
            <form id="skip-step" method="POST" action="{{ route('onboarding.skip-step', $step) }}" class="hidden">@csrf</form>
        </section>
    </div>
</x-layouts.app>
