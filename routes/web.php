<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FreelancerProfileController;
use App\Http\Controllers\LocaleController;
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
    Route::get('/requests', [ProjectRequestController::class, 'index'])->name('requests.index');
    Route::get('/reports', ReportController::class)->name('reports.index');
    Route::get('/settings/freelancer', [FreelancerProfileController::class, 'edit'])
        ->name('settings.freelancer.edit');
    Route::put('/settings/freelancer', [FreelancerProfileController::class, 'update'])
        ->name('settings.freelancer.update');

    Route::resource('rules', RuleController::class)->except(['destroy']);
    Route::post('/rule-versions/{version}/approve', [RuleController::class, 'approve'])
        ->name('rule-versions.approve');
});
