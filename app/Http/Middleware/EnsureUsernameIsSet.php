<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUsernameIsSet
{
    public function handle(Request $request, Closure $next): Response|RedirectResponse
    {
        $user = $request->user();

        if ($user !== null && $user->username === null && ! $request->routeIs('community.username.*', 'logout')) {
            return redirect()->route('community.username.create', [
                'locale' => in_array($user->locale, ['id', 'en'], true) ? $user->locale : 'id',
            ]);
        }

        return $next($request);
    }
}
