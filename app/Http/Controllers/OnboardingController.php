<?php

namespace App\Http\Controllers;

use App\Models\FreelancerProfile;
use App\Models\Rule;
use App\Services\PersonalDataPackage;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class OnboardingController
{
    private const STEPS = ['profile', 'expertise', 'writing', 'estimates'];

    public function index(): View|RedirectResponse
    {
        if ($this->isComplete()) {
            return redirect()->route('dashboard');
        }

        return view('onboarding.index');
    }

    public function start(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'action' => ['required', 'in:start,skip'],
        ]);

        if ($data['action'] === 'skip') {
            return $this->finish();
        }

        return redirect()->route('onboarding.step', 'profile');
    }

    public function showStep(string $step): View|RedirectResponse
    {
        if ($this->isComplete()) {
            return redirect()->route('dashboard');
        }

        $index = array_search($step, self::STEPS, true);
        abort_if($index === false, 404);

        $profile = FreelancerProfile::query()->where('profile_key', 'primary')->first();

        return view('onboarding.step', [
            'step' => $step,
            'stepNumber' => $index + 1,
            'totalSteps' => count(self::STEPS),
            'nextStep' => self::STEPS[$index + 1] ?? null,
            'profile' => $profile,
            'profileValues' => $profile?->localizedValues(app()->getLocale()) ?? [],
        ]);
    }

    public function saveStep(Request $request, string $step): RedirectResponse
    {
        abort_unless(in_array($step, self::STEPS, true), 404);
        if ($this->isComplete()) {
            return redirect()->route('dashboard');
        }

        $locale = app()->getLocale();
        if ($step === 'profile') {
            $data = $request->validate([
                'display_name' => ['nullable', 'string', 'max:120'],
                'professional_title' => ['nullable', 'string', 'max:160'],
                'experience_years' => ['nullable', 'integer', 'between:0,60'],
                'location' => ['nullable', 'string', 'max:120'],
                'portfolio_url' => ['nullable', 'url:http,https', 'max:2048'],
                'website_url' => ['nullable', 'url:http,https', 'max:2048'],
            ]);

            $profileData = [
                "display_name_{$locale}" => $data['display_name'] ?? null,
                "professional_title_{$locale}" => $data['professional_title'] ?? null,
                "location_{$locale}" => $data['location'] ?? null,
            ];
            foreach (['experience_years', 'portfolio_url', 'website_url'] as $field) {
                if (array_key_exists($field, $data)) {
                    $profileData[$field] = $data[$field];
                }
            }

            FreelancerProfile::query()->updateOrCreate(['profile_key' => 'primary'], $profileData);
        } elseif ($step === 'expertise') {
            $data = $request->validate([
                'services' => ['nullable', 'string', 'max:2000'],
                'technologies' => ['nullable', 'string', 'max:2400'],
                'languages' => ['nullable', 'string', 'max:1200'],
                'bio' => ['nullable', 'string', 'max:4000'],
            ]);

            $profileData = [
                "services_{$locale}" => $this->parseList($data['services'] ?? null),
                "technologies_{$locale}" => $this->parseList($data['technologies'] ?? null),
                "languages_{$locale}" => $this->parseList($data['languages'] ?? null),
                "bio_{$locale}" => $data['bio'] ?? null,
            ];
            FreelancerProfile::query()->updateOrCreate(['profile_key' => 'primary'], $profileData);
        } else {
            $data = $request->validate([
                'title' => ['nullable', 'string', 'max:255', 'required_with:body'],
                'body' => ['nullable', 'string', 'max:20000', 'required_with:title'],
            ]);

            if (filled($data['body'] ?? null)) {
                $this->saveOnboardingRule($step, $data['title'], $data['body']);
            }
        }

        return $this->advance($step);
    }

    public function skipStep(string $step): RedirectResponse
    {
        abort_unless(in_array($step, self::STEPS, true), 404);

        return $this->advance($step);
    }

    public function skip(): RedirectResponse
    {
        return $this->finish();
    }

    public function import(Request $request, PersonalDataController $personalData, PersonalDataPackage $package): RedirectResponse
    {
        return $personalData->importForOnboarding($request, $package);
    }

    private function advance(string $step): RedirectResponse
    {
        $index = array_search($step, self::STEPS, true);
        $next = self::STEPS[$index + 1] ?? null;

        return $next
            ? redirect()->route('onboarding.step', $next)
            : $this->finish();
    }

    private function finish(): RedirectResponse
    {
        FreelancerProfile::query()->updateOrCreate(
            ['profile_key' => 'primary'],
            ['setup_completed_at' => now()],
        );

        return redirect()->route('dashboard')->with('success', __('ui.onboarding.completed'));
    }

    private function isComplete(): bool
    {
        return FreelancerProfile::query()
            ->where('profile_key', 'primary')
            ->whereNotNull('setup_completed_at')
            ->exists();
    }

    private function saveOnboardingRule(string $step, string $title, string $body): void
    {
        $key = 'setup_'.substr(hash('sha256', $step), 0, 53);

        DB::transaction(function () use ($key, $title, $body, $step): void {
            $rule = Rule::query()
                ->where('scope', 'global')
                ->where('legacy_key', $key)
                ->lockForUpdate()
                ->first();

            $priority = $step === 'writing' ? 50 : 60;
            $status = 'approved';
            $versionNumber = 1;

            if (! $rule) {
                $rule = Rule::query()->create([
                    'legacy_key' => $key,
                    'scope' => 'global',
                    'title' => $title,
                    'status' => 'approved',
                    'current_version' => 1,
                    'priority' => $priority,
                    'approved_at' => now(),
                ]);
            } else {
                $current = $rule->versions()->where('version', $rule->current_version)->first();
                if ($current?->body === $body && $rule->title === $title) {
                    return;
                }

                $versionNumber = ((int) $rule->versions()->max('version')) + 1;
                $status = 'pending_approval';
            }

            $rule->versions()->create([
                'version' => $versionNumber,
                'title' => $title,
                'priority' => $priority,
                'body' => $body,
                'status' => $status,
                'source_type' => 'onboarding',
                'change_summary' => $status === 'approved'
                    ? 'New rule entered and saved directly by the user during workspace setup.'
                    : 'Existing rule changed during workspace setup; awaiting explicit user approval.',
                'approved_at' => $status === 'approved' ? now() : null,
                'created_at' => now(),
            ]);
        });
    }

    /** @return list<string> */
    private function parseList(?string $value): array
    {
        if ($value === null || trim($value) === '') {
            return [];
        }

        $items = preg_split('/[\r\n,;]+/u', $value) ?: [];
        $items = array_map('trim', $items);

        return array_values(array_unique(array_filter($items, static fn (string $item): bool => $item !== '')));
    }
}
