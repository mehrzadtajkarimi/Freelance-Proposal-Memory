<x-layouts.app :title="__('ui.profile.title')">
    <x-page-heading :title="__('ui.profile.title')" :description="__('ui.profile.description')" />

    <form method="POST" action="{{ route('settings.freelancer.update') }}" class="panel max-w-4xl p-5 sm:p-7">
        @csrf
        @method('PUT')

        <section>
            <h2 class="text-base font-semibold text-slate-950">{{ __('ui.profile.basics') }}</h2>
            <p class="mt-1 text-sm leading-6 text-slate-500">{{ __('ui.profile.basics_hint') }}</p>
            <p class="mt-1 text-sm leading-6 text-slate-500">{{ __('ui.profile.locale_hint') }}</p>

            <div class="mt-5 grid gap-5 sm:grid-cols-2">
                <div>
                    <label class="label" for="display_name">{{ __('ui.profile.display_name') }}</label>
                    <input class="field" id="display_name" name="display_name" value="{{ old('display_name', $profileValues['display_name']) }}" maxlength="120" autocomplete="name">
                </div>
                <div>
                    <label class="label" for="professional_title">{{ __('ui.profile.professional_title') }}</label>
                    <input class="field" id="professional_title" name="professional_title" value="{{ old('professional_title', $profileValues['professional_title']) }}" maxlength="160">
                </div>
                <div class="sm:col-span-2">
                    <label class="label" for="bio">{{ __('ui.profile.bio') }}</label>
                    <textarea class="field min-h-28 resize-y leading-6" id="bio" name="bio" rows="4" maxlength="4000">{{ old('bio', $profileValues['bio']) }}</textarea>
                </div>
                <div>
                    <label class="label" for="experience_years">{{ __('ui.profile.experience_years') }}</label>
                    <input class="field" id="experience_years" name="experience_years" type="number" min="0" max="60" value="{{ old('experience_years', $profile->experience_years) }}" inputmode="numeric">
                </div>
                <div>
                    <label class="label" for="location">{{ __('ui.profile.location') }}</label>
                    <input class="field" id="location" name="location" value="{{ old('location', $profileValues['location']) }}" maxlength="120" autocomplete="address-level2">
                </div>
                <div>
                    <label class="label" for="portfolio_url">{{ __('ui.profile.portfolio_url') }}</label>
                    <input class="field" id="portfolio_url" name="portfolio_url" type="url" value="{{ old('portfolio_url', $profile->portfolio_url) }}" maxlength="2048" inputmode="url">
                </div>
                <div>
                    <label class="label" for="website_url">{{ __('ui.profile.website_url') }}</label>
                    <input class="field" id="website_url" name="website_url" type="url" value="{{ old('website_url', $profile->website_url) }}" maxlength="2048" inputmode="url">
                </div>
            </div>
        </section>

        <section class="mt-8 border-t border-slate-100 pt-6">
            <h2 class="text-base font-semibold text-slate-950">{{ __('ui.profile.expertise') }}</h2>
            <p class="mt-1 text-sm leading-6 text-slate-500">{{ __('ui.profile.expertise_hint') }}</p>

            <div class="mt-5 grid gap-5 sm:grid-cols-2">
                <div>
                    <label class="label" for="technologies">{{ __('ui.profile.technologies') }}</label>
                    <textarea class="field min-h-32 resize-y leading-6" id="technologies" name="technologies" rows="5" maxlength="2400">{{ old('technologies', implode("\n", $profileValues['technologies'] ?? [])) }}</textarea>
                    <p class="mt-2 text-xs leading-5 text-slate-500">{{ __('ui.profile.list_hint') }}</p>
                </div>
                <div>
                    <label class="label" for="services">{{ __('ui.profile.services') }}</label>
                    <textarea class="field min-h-32 resize-y leading-6" id="services" name="services" rows="5" maxlength="2000">{{ old('services', implode("\n", $profileValues['services'] ?? [])) }}</textarea>
                    <p class="mt-2 text-xs leading-5 text-slate-500">{{ __('ui.profile.list_hint') }}</p>
                </div>
                <div class="sm:col-span-2">
                    <label class="label" for="languages">{{ __('ui.profile.languages') }}</label>
                    <textarea class="field min-h-24 resize-y leading-6" id="languages" name="languages" rows="3" maxlength="1200">{{ old('languages', implode("\n", $profileValues['languages'] ?? [])) }}</textarea>
                    <p class="mt-2 text-xs leading-5 text-slate-500">{{ __('ui.profile.list_hint') }}</p>
                </div>
            </div>
        </section>

        <div class="mt-7 flex justify-end border-t border-slate-100 pt-5">
            <button type="submit" class="btn-primary">{{ __('ui.common.save') }}</button>
        </div>
    </form>
</x-layouts.app>
