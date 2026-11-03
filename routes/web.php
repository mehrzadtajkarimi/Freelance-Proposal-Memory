<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FreelancerProfileController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\PaymentStageRuleController;
use App\Http\Controllers\PersonalDataController;
use App\Http\Controllers\ProjectRequestController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RuleController;
use App\Http\Middleware\SetLocale;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    try {
        DB::connection()->select('select 1');
    } catch (\Throwable) {
        return response()->json([
            'application' => config('app.name'),
            'status' => 'degraded',
            'database' => 'unavailable',
        ], 503);
    }

    return response()->json([
        'application' => config('app.name'),
        'status' => 'ready',
        'database' => 'connected',
    ]);
})->name('health');

Route::post('/locale/{locale}', [LocaleController::class, 'update'])
    ->whereIn('locale', ['en', 'fa'])
    ->name('locale.update');

Route::middleware(SetLocale::class)->group(function (): void {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::get('/setup', [OnboardingController::class, 'index'])->name('onboarding.index');
    Route::post('/setup/start', [OnboardingController::class, 'start'])->name('onboarding.start');
    Route::get('/setup/step/{step}', [OnboardingController::class, 'showStep'])
        ->where('step', 'profile|expertise|writing|estimates')
        ->name('onboarding.step');
    Route::post('/setup/step/{step}', [OnboardingController::class, 'saveStep'])
        ->where('step', 'profile|expertise|writing|estimates')
        ->name('onboarding.save-step');
    Route::post('/setup/step/{step}/skip', [OnboardingController::class, 'skipStep'])
        ->where('step', 'profile|expertise|writing|estimates')
        ->name('onboarding.skip-step');
    Route::post('/setup/skip', [OnboardingController::class, 'skip'])->name('onboarding.skip');
    Route::post('/setup/import', [OnboardingController::class, 'import'])->name('onboarding.import');

    Route::get('/settings/data', [PersonalDataController::class, 'index'])->name('settings.data');
    Route::get('/settings/data/export', [PersonalDataController::class, 'export'])->name('settings.data.export');
    Route::post('/settings/data/import', [PersonalDataController::class, 'import'])->name('settings.data.import');

    Route::get('/requests', [ProjectRequestController::class, 'index'])->name('requests.index');
    Route::put('/requests/{projectRequest}/full-time', [ProjectRequestController::class, 'updateFullTime'])
        ->name('requests.full-time.update');
    Route::get('/reports', ReportController::class)->name('reports.index');
    Route::get('/settings/freelancer', [FreelancerProfileController::class, 'edit'])
        ->name('settings.freelancer.edit');
    Route::put('/settings/freelancer', [FreelancerProfileController::class, 'update'])
        ->name('settings.freelancer.update');

    Route::get('/settings/payment-stages', [PaymentStageRuleController::class, 'index'])->name('payment-rules.index');
    Route::get('/settings/payment-stages/create', [PaymentStageRuleController::class, 'create'])->name('payment-rules.create');
    Route::post('/settings/payment-stages', [PaymentStageRuleController::class, 'store'])->name('payment-rules.store');
    Route::get('/settings/payment-stages/{paymentRule}/edit', [PaymentStageRuleController::class, 'edit'])->name('payment-rules.edit');
    Route::put('/settings/payment-stages/{paymentRule}', [PaymentStageRuleController::class, 'update'])->name('payment-rules.update');
    Route::delete('/settings/payment-stages/{paymentRule}', [PaymentStageRuleController::class, 'destroy'])->name('payment-rules.destroy');

    Route::resource('rules', RuleController::class)->except(['destroy']);
    Route::post('/rule-versions/{version}/approve', [RuleController::class, 'approve'])
        ->name('rule-versions.approve');
});
