<?php

use App\Http\Middleware\EnsureUsernameIsSet;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
            EnsureUsernameIsSet::class,
        ]);

        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->respond(function (SymfonyResponse $response, Throwable $exception, Request $request): SymfonyResponse {
            $status = $response->getStatusCode();
            $isServerError = in_array($status, [500, 503], true);

            if ($response instanceof JsonResponse
                || ! in_array($status, [403, 404, 419, 429, 500, 503], true)
                || ($isServerError && config('app.debug') && ! app()->isDownForMaintenance())) {
                return $response;
            }

            config(['inertia.ssr.enabled' => false]);

            $locale = in_array($request->segment(1), ['id', 'en'], true) ? $request->segment(1) : 'id';

            $errorPage = Inertia::render('Error', ['status' => $status, 'locale' => $locale])
                ->toResponse($request)
                ->setStatusCode($status);

            if ($response->headers->has('Retry-After')) {
                $errorPage->headers->set('Retry-After', $response->headers->get('Retry-After'));
            }

            return $errorPage;
        });
    })->create();
