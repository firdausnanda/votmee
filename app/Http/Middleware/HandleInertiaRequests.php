<?php

namespace App\Http\Middleware;

use App\Services\AchievementService;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class HandleInertiaRequests extends Middleware
{
    public function handle(Request $request, \Closure $next): SymfonyResponse
    {
        $ssrEnabled = config('inertia.ssr.enabled');

        try {
            if (! $request->routeIs('community.home', 'community.explore', 'community.ranking', 'community.object', 'community.article')) {
                config(['inertia.ssr.enabled' => false]);
            }

            return parent::handle($request, $next);
        } finally {
            config(['inertia.ssr.enabled' => $ssrEnabled]);
        }
    }

    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user,
                'can' => [
                    'createRanking' => $user !== null,
                    'viewWallet' => $user !== null,
                    'moderate' => $user?->can('moderate') ?? false,
                ],
            ],
            'walletBalance' => fn (): ?int => $user ? (int) ($user->wallet()->value('balance') ?? 0) : null,
            'unseenAchievements' => fn (): array => $user ? app(AchievementService::class)->unseen($user) : [],
        ];
    }
}
