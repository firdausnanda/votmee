<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class UsernameController extends Controller
{
    public function suggest(Request $request): JsonResponse
    {
        $validated = $request->validate(['name' => ['required', 'string', 'max:255']]);

        return response()->json(['username' => User::suggestedUsername($validated['name'])]);
    }

    public function create(Request $request, string $locale): Response|RedirectResponse
    {
        if ($request->user()->username !== null) {
            return redirect()->route('community.home', ['locale' => $locale]);
        }

        return Inertia::render('Auth/ChooseUsername', [
            'locale' => $locale,
            'suggestedUsername' => User::suggestedUsername($request->user()->name),
        ]);
    }

    public function store(Request $request, string $locale): RedirectResponse
    {
        if ($request->user()->username !== null) {
            return redirect()->route('community.home', ['locale' => $locale]);
        }

        $validated = $request->validate([
            'username' => ['required', 'string', 'min:3', 'max:30', 'regex:/\A[a-z0-9][a-z0-9_-]*\z/', Rule::unique(User::class, 'username')],
        ]);

        $request->user()->update(['username' => $validated['username']]);
        $returnTo = $request->session()->pull('username_return_to');

        return redirect()->to(is_string($returnTo) && str_starts_with($returnTo, '/')
            && ! str_starts_with($returnTo, '//') && ! str_contains($returnTo, '\\')
            ? $returnTo
            : route('community.home', ['locale' => $locale]));
    }
}
