<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Application bindings are added here as the workflow grows.
    }

    public function boot(): void
    {
        // Keep application boot hooks small and explicit.
    }
}
