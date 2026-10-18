<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);
        Gate::define('moderate', fn (User $user): bool => $user->hasRole('administrator') || in_array($user->role, ['moderator', 'admin'], true));
        Gate::define('administer', fn (User $user): bool => $user->hasRole('administrator') || $user->role === 'admin');
    }
}
