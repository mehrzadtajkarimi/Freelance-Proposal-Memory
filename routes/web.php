<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
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
});
