<?php

namespace App\Http\Controllers;

use App\Http\Requests\NotificationPreferencesRequest;
use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Category;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        $locale = $request->user()->locale ?? app()->getLocale();

        return Inertia::render('Profile/Edit', [
            'locale' => $locale,
            'categories' => Category::query()->withCount(['rankingPosts' => fn ($query) => $query->where('status', 'published')])
                ->orderBy('name')->get(['id', 'name', 'slug']),
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
            'notificationPreferences' => collect(config('notification_preferences.groups'))
                ->mapWithKeys(fn (string $group): array => [$group => ($request->user()->notification_preferences[$group] ?? true) !== false])
                ->all(),
        ]);
    }

    /**
     * Switch in-app notification groups on or off. Only switched-off groups are stored.
     */
    public function updateNotifications(NotificationPreferencesRequest $request): RedirectResponse
    {
        $disabled = collect($request->validated('preferences'))
            ->reject(fn ($enabled): bool => (bool) $enabled)
            ->map(fn (): bool => false)
            ->all();

        $request->user()->update(['notification_preferences' => $disabled === [] ? null : $disabled]);

        return Redirect::route('profile.edit');
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
