<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserAchievement;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class AvatarFrameService
{
    /** Frames from rarest to most common; the rarest one held shows unless the member picks another. */
    public const FRAMES = ['champion', 'veteran', 'legend', 'gold'];

    /** The preference that hides the frame altogether. */
    public const NONE = 'none';

    public function __construct(private AchievementService $achievements) {}

    /**
     * The frames each member holds, rarest first, the board of a reigning champion
     * and how many champion titles they have won.
     *
     * @param  iterable<int>  $userIds
     * @return array<int, array{frames: list<string>, board: string|null, titles: int}>
     */
    public function holdings(iterable $userIds): array
    {
        $userIds = collect($userIds)->filter()->unique()->values();

        if ($userIds->isEmpty()) {
            return [];
        }

        $goldKeys = array_map(fn (string $category): string => "{$category}_gold", array_keys(config('gamification.achievements')));
        $reigningPrefix = 'champion_'.$this->achievements->currentMonth()->subMonth()->format('Y_m').'_';
        $keysByUser = UserAchievement::query()
            ->whereIn('user_id', $userIds)
            ->where(fn (Builder $query) => $query->whereIn('achievement_key', $goldKeys)->orWhere('achievement_key', 'like', 'champion_%'))
            ->get(['user_id', 'achievement_key'])
            ->groupBy('user_id');

        return $userIds->mapWithKeys(function (int $userId) use ($keysByUser, $reigningPrefix): array {
            $keys = $keysByUser->get($userId, collect())->pluck('achievement_key');
            $titles = $keys->filter(fn (string $key): bool => str_starts_with($key, 'champion_'));
            $golds = $keys->count() - $titles->count();
            $board = collect(array_keys(config('gamification.seasonal')))
                ->first(fn (string $category): bool => $titles->contains($reigningPrefix.$category));

            $frames = array_values(array_filter([
                $board !== null ? 'champion' : null,
                $titles->count() >= (int) config('gamification.avatar_frames.veteran_titles') ? 'veteran' : null,
                $golds >= (int) config('gamification.avatar_frames.legend_gold_badges') ? 'legend' : null,
                $golds >= 1 ? 'gold' : null,
            ]));

            return [$userId => ['frames' => $frames, 'board' => $board, 'titles' => $titles->count()]];
        })->all();
    }

    /**
     * Each member's avatar frame and the flair beside their name. The frame is their pick
     * while they still hold it, otherwise their rarest one; champion frames name their board,
     * as in "champion-ranker", and veteran frames their title count, as in "veteran-4". The name flair is "champion-{board}" while they reign as last
     * month's champion, otherwise "trusted" for trusted contributors.
     *
     * @param  iterable<int>  $userIds
     * @return array<int, array{avatar_frame: string|null, name_flair: string|null}>
     */
    public function flairFor(iterable $userIds): array
    {
        $holdings = $this->holdings($userIds);
        $users = $holdings === [] ? collect() : User::query()
            ->whereIn('id', array_keys($holdings))
            ->get(['id', 'preferred_avatar_frame', 'trusted_at', 'email_verified_at', 'restricted_until'])
            ->keyBy('id');

        return collect($holdings)
            ->map(fn (array $holding, int $userId): array => $this->flair($holding, $users->get($userId)))
            ->all();
    }

    /**
     * One member's avatar frame and name flair.
     *
     * @return array{avatar_frame: string|null, name_flair: string|null}
     */
    public function flairForUser(User $user): array
    {
        return $this->flair($this->holdings([$user->id])[$user->id], $user);
    }

    /**
     * Add each member's avatar frame and name flair to member arrays that carry an "id".
     *
     * @param  array<int|string, array<string, mixed>|null>  $members
     * @return array<int|string, array<string, mixed>|null>
     */
    public function decorate(array $members): array
    {
        $flair = $this->flairFor(collect($members)->pluck('id'));

        return array_map(
            fn (?array $member): ?array => $member === null ? null : [...$member, ...($flair[$member['id']] ?? [])],
            $members,
        );
    }

    /**
     * Fill the avatar frame and name flair onto partially loaded member models that are
     * only serialized, such as eager-loaded authors. Never use it on models that get saved.
     *
     * @param  iterable<User|null>  $users
     */
    public function fill(iterable $users): void
    {
        $users = collect($users)->filter();
        $flair = $this->flairFor($users->pluck('id'));
        $users->each(fn (User $user) => $user->forceFill($flair[$user->id] ?? []));
    }

    /**
     * The frame shown around each member's avatar.
     *
     * @param  iterable<int>  $userIds
     * @return array<int, string|null>
     */
    public function framesFor(iterable $userIds): array
    {
        return array_map(fn (array $flair): ?string => $flair['avatar_frame'], $this->flairFor($userIds));
    }

    /** The frame shown around one member's avatar. */
    public function frameFor(User $user): ?string
    {
        return $this->flairForUser($user)['avatar_frame'];
    }

    /**
     * What the frame picker on the member's own profile needs.
     *
     * @return array{preferred: string|null, held: list<string>, board: string|null, titles: int, requirements: array{veteran_titles: int, legend_gold_badges: int}}
     */
    public function options(User $user): array
    {
        $holding = $this->holdings([$user->id])[$user->id];

        return [
            'preferred' => $user->preferred_avatar_frame,
            'held' => $holding['frames'],
            'board' => $holding['board'],
            'titles' => $holding['titles'],
            'requirements' => config('gamification.avatar_frames'),
        ];
    }

    /** Save the member's frame pick: null follows the rarest frame held, "none" hides it. */
    public function choose(User $user, ?string $frame): void
    {
        if ($frame !== null && $frame !== self::NONE && ! in_array($frame, $this->holdings([$user->id])[$user->id]['frames'], true)) {
            throw ValidationException::withMessages(['frame' => __('votmee.avatar_frame_locked')]);
        }

        $user->forceFill(['preferred_avatar_frame' => $frame])->save();
    }

    /**
     * @param  array{frames: list<string>, board: string|null, titles: int}  $holding
     * @return array{avatar_frame: string|null, name_flair: string|null}
     */
    private function flair(array $holding, ?User $user): array
    {
        return [
            'avatar_frame' => $this->display($holding, $user?->preferred_avatar_frame),
            'name_flair' => $holding['board'] !== null
                ? "champion-{$holding['board']}"
                : ($user?->isTrustedContributor() ? 'trusted' : null),
        ];
    }

    /** @param array{frames: list<string>, board: string|null, titles: int} $holding */
    private function display(array $holding, ?string $preferred): ?string
    {
        if ($preferred === self::NONE) {
            return null;
        }

        $frame = in_array($preferred, $holding['frames'], true) ? $preferred : ($holding['frames'][0] ?? null);

        return match ($frame) {
            'champion' => "champion-{$holding['board']}",
            'veteran' => "veteran-{$holding['titles']}",
            default => $frame,
        };
    }
}
