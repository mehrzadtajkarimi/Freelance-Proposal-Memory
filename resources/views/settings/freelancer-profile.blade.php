<x-layouts.app :title="__('ui.profile.title')">
    <x-page-heading :title="__('ui.profile.title')" :description="__('ui.profile.description')" />

    <form method="POST" action="{{ route('settings.freelancer.update') }}" class="panel max-w-4xl p-5 sm:p-7">
        @csrf
        @method('PUT')

        @php
            $experienceRows = old('experiences', $experiences);
            if (! is_array($experienceRows) || $experienceRows === []) {
                $experienceRows = [['domain' => '', 'position' => '', 'organization' => '', 'years' => '', 'description' => '', 'skills' => '']];
            }
            $deletedExperienceIds = array_map('strval', old('deleted_experiences', []));
        @endphp

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

        <section class="mt-8 border-t border-slate-100 pt-6">
            <h2 class="text-base font-semibold text-slate-950">{{ __('ui.profile.experiences') }}</h2>
            <p class="mt-1 text-sm leading-6 text-slate-500">{{ __('ui.profile.experiences_hint') }}</p>

            <div id="experience-list" class="mt-5 space-y-5">
                @foreach ($experienceRows as $index => $experience)
                    @php($persisted = filled($experience['id'] ?? null))
                    @php($markedForDeletion = $persisted && in_array((string) $experience['id'], $deletedExperienceIds, true))
                    <fieldset class="rounded-xl border border-slate-200 p-4 sm:p-5" data-experience-row data-persisted="{{ $persisted ? 'true' : 'false' }}" @if ($markedForDeletion) hidden @endif>
                        @if ($persisted)
                            <input type="hidden" name="experiences[{{ $index }}][id]" value="{{ $experience['id'] }}">
                        @endif

                        <legend class="mb-4 flex w-full items-center justify-between gap-3 text-sm font-semibold text-slate-800">
                            <span>{{ __('ui.profile.experience_entry') }}</span>
                            <button type="button" class="btn-secondary px-3 py-2 text-sm" data-remove-experience>{{ __('ui.profile.remove_experience') }}</button>
                        </legend>

                        @if ($persisted)
                            <label class="mb-4 flex items-center gap-2 text-sm text-slate-600">
                                <input type="checkbox" name="deleted_experiences[]" value="{{ $experience['id'] }}" data-delete-experience @checked($markedForDeletion)>
                                {{ __('ui.profile.delete_experience') }}
                            </label>
                        @endif

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="label" for="experience-{{ $index }}-domain">{{ __('ui.profile.experience_domain') }}</label>
                                <input class="field" id="experience-{{ $index }}-domain" name="experiences[{{ $index }}][domain]" value="{{ $experience['domain'] ?? '' }}" maxlength="120">
                                @error("experiences.{$index}.domain")
                                    <p class="mt-1 text-sm text-rose-700">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="label" for="experience-{{ $index }}-position">{{ __('ui.profile.experience_position') }}</label>
                                <input class="field" id="experience-{{ $index }}-position" name="experiences[{{ $index }}][position]" value="{{ $experience['position'] ?? '' }}" maxlength="160">
                            </div>
                            <div>
                                <label class="label" for="experience-{{ $index }}-organization">{{ __('ui.profile.experience_organization') }}</label>
                                <input class="field" id="experience-{{ $index }}-organization" name="experiences[{{ $index }}][organization]" value="{{ $experience['organization'] ?? '' }}" maxlength="180">
                            </div>
                            <div>
                                <label class="label" for="experience-{{ $index }}-years">{{ __('ui.profile.experience_duration') }}</label>
                                <input class="field" id="experience-{{ $index }}-years" name="experiences[{{ $index }}][years]" type="number" min="0" max="60" value="{{ $experience['years'] ?? '' }}" inputmode="numeric">
                            </div>
                            <div class="sm:col-span-2">
                                <label class="label" for="experience-{{ $index }}-description">{{ __('ui.profile.experience_description') }}</label>
                                <textarea class="field min-h-24 resize-y leading-6" id="experience-{{ $index }}-description" name="experiences[{{ $index }}][description]" rows="3" maxlength="2000">{{ $experience['description'] ?? '' }}</textarea>
                            </div>
                            <div class="sm:col-span-2">
                                <label class="label" for="experience-{{ $index }}-skills">{{ __('ui.profile.experience_skills') }}</label>
                                <textarea class="field min-h-20 resize-y leading-6" id="experience-{{ $index }}-skills" name="experiences[{{ $index }}][skills]" rows="2" maxlength="1200">{{ is_array($experience['skills'] ?? null) ? implode("\n", $experience['skills']) : ($experience['skills'] ?? '') }}</textarea>
                                <p class="mt-2 text-xs leading-5 text-slate-500">{{ __('ui.profile.list_hint') }}</p>
                            </div>
                        </div>
                    </fieldset>
                @endforeach
            </div>

            <button type="button" id="add-experience" class="btn-secondary mt-4">{{ __('ui.profile.add_experience') }}</button>

            <template id="experience-template">
                <fieldset class="rounded-xl border border-slate-200 p-4 sm:p-5" data-experience-row data-persisted="false">
                    <legend class="mb-4 flex w-full items-center justify-between gap-3 text-sm font-semibold text-slate-800">
                        <span>{{ __('ui.profile.experience_entry') }}</span>
                        <button type="button" class="btn-secondary px-3 py-2 text-sm" data-remove-experience>{{ __('ui.profile.remove_experience') }}</button>
                    </legend>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="label" for="experience-__INDEX__-domain">{{ __('ui.profile.experience_domain') }}</label>
                            <input class="field" id="experience-__INDEX__-domain" name="experiences[__INDEX__][domain]" maxlength="120">
                        </div>
                        <div>
                            <label class="label" for="experience-__INDEX__-position">{{ __('ui.profile.experience_position') }}</label>
                            <input class="field" id="experience-__INDEX__-position" name="experiences[__INDEX__][position]" maxlength="160">
                        </div>
                        <div>
                            <label class="label" for="experience-__INDEX__-organization">{{ __('ui.profile.experience_organization') }}</label>
                            <input class="field" id="experience-__INDEX__-organization" name="experiences[__INDEX__][organization]" maxlength="180">
                        </div>
                        <div>
                            <label class="label" for="experience-__INDEX__-years">{{ __('ui.profile.experience_duration') }}</label>
                            <input class="field" id="experience-__INDEX__-years" name="experiences[__INDEX__][years]" type="number" min="0" max="60" inputmode="numeric">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="label" for="experience-__INDEX__-description">{{ __('ui.profile.experience_description') }}</label>
                            <textarea class="field min-h-24 resize-y leading-6" id="experience-__INDEX__-description" name="experiences[__INDEX__][description]" rows="3" maxlength="2000"></textarea>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="label" for="experience-__INDEX__-skills">{{ __('ui.profile.experience_skills') }}</label>
                            <textarea class="field min-h-20 resize-y leading-6" id="experience-__INDEX__-skills" name="experiences[__INDEX__][skills]" rows="2" maxlength="1200"></textarea>
                            <p class="mt-2 text-xs leading-5 text-slate-500">{{ __('ui.profile.list_hint') }}</p>
                        </div>
                    </div>
                </fieldset>
            </template>
        </section>

        <div class="mt-7 flex justify-end border-t border-slate-100 pt-5">
            <button type="submit" class="btn-primary">{{ __('ui.common.save') }}</button>
        </div>
    </form>

    <script>
        const experienceList = document.getElementById('experience-list');
        const experienceTemplate = document.getElementById('experience-template');
        let nextExperienceIndex = experienceList.children.length;

        document.getElementById('add-experience').addEventListener('click', () => {
            const row = experienceTemplate.innerHTML.replaceAll('__INDEX__', String(nextExperienceIndex++));
            experienceList.insertAdjacentHTML('beforeend', row);
        });

        experienceList.addEventListener('click', (event) => {
            const button = event.target.closest('[data-remove-experience]');
            if (!button) return;

            const row = button.closest('[data-experience-row]');
            if (row.dataset.persisted === 'true') {
                row.querySelector('[data-delete-experience]').checked = true;
                row.hidden = true;
                return;
            }

            row.remove();
        });
    </script>
</x-layouts.app>
