<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetRequestLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $preferred = $request->header('X-Locale')
            ?? $request->route('locale')
            ?? $request->user()?->locale
            ?? substr((string) $request->header('Accept-Language', 'id'), 0, 2);
        App::setLocale(in_array($preferred, ['id', 'en'], true) ? $preferred : 'id');

        return $next($request);
    }
}
