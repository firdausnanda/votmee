<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
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
        RateLimiter::for('object-uploads', fn (Request $request): array => [
            Limit::perMinute(6)->by('object-uploads:minute:'.($request->user()?->id ?? $request->ip())),
            Limit::perDay(40)->by('object-uploads:day:'.($request->user()?->id ?? $request->ip())),
        ]);
        RateLimiter::for('profile-uploads', fn (Request $request): array => [
            Limit::perMinute(5)->by('profile-uploads:minute:'.($request->user()?->id ?? $request->ip())),
            Limit::perDay(20)->by('profile-uploads:day:'.($request->user()?->id ?? $request->ip())),
        ]);
        RateLimiter::for('content-uploads', fn (Request $request): array => [
            Limit::perMinute(10)->by('content-uploads:minute:'.($request->user()?->id ?? $request->ip())),
            Limit::perDay(60)->by('content-uploads:day:'.($request->user()?->id ?? $request->ip())),
        ]);
        RateLimiter::for('comment-uploads', fn (Request $request): array => $request->hasFile('images') ? [
            Limit::perMinute(5)->by('comment-uploads:minute:'.($request->user()?->id ?? $request->ip())),
            Limit::perDay(40)->by('comment-uploads:day:'.($request->user()?->id ?? $request->ip())),
        ] : [Limit::none()]);
        Gate::define('moderate', fn (User $user): bool => $user->hasRole('administrator') || in_array($user->role, ['moderator', 'admin'], true));
        Gate::define('administer', fn (User $user): bool => $user->hasRole('administrator') || $user->role === 'admin');
    }
}
