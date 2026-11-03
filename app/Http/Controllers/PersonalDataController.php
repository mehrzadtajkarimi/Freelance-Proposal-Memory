<?php

namespace App\Http\Controllers;

use App\Models\FreelancerProfile;
use App\Services\PersonalDataPackage;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class PersonalDataController
{
    public function index(): View
    {
        return view('settings.data');
    }

    public function export(PersonalDataPackage $package): StreamedResponse
    {
        $content = json_encode(
            $package->export(),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
        );

        return response()->streamDownload(
            static function () use ($content): void {
                echo $content;
            },
            'freelance-proposal-memory-'.now()->format('Y-m-d').'.json',
            ['Content-Type' => 'application/json; charset=UTF-8'],
        );
    }

    public function import(Request $request, PersonalDataPackage $package): RedirectResponse
    {
        $summary = $this->importUpload($request, $package);

        return redirect()->route('settings.data')->with('success', $this->summaryMessage($summary));
    }

    public function importForOnboarding(Request $request, PersonalDataPackage $package): RedirectResponse
    {
        $summary = $this->importUpload($request, $package);
        FreelancerProfile::query()->updateOrCreate(
            ['profile_key' => 'primary'],
            ['setup_completed_at' => now()],
        );

        return redirect()->route('dashboard')->with('success', $this->summaryMessage($summary));
    }

    /** @return array{profile_fields: int, experiences: int, rules_created: int, rules_updated: int, rules_unchanged: int} */
    private function importUpload(Request $request, PersonalDataPackage $package): array
    {
        $data = $request->validate([
            'file' => ['required', 'file', 'max:2048'],
        ]);

        return $package->import($package->validateUpload($data['file']));
    }

    /** @param array{profile_fields: int, experiences: int, rules_created: int, rules_updated: int, rules_unchanged: int} $summary */
    private function summaryMessage(array $summary): string
    {
        return __('ui.data.imported', [
            'profile' => $summary['profile_fields'],
            'experiences' => $summary['experiences'],
            'created' => $summary['rules_created'],
            'updated' => $summary['rules_updated'],
            'same' => $summary['rules_unchanged'],
        ]);
    }
}
