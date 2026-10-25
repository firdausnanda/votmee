<?php

namespace App\Models;

use App\Services\AvatarFrameService;
use Database\Factories\UserAchievementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

#[Fillable(['user_id', 'achievement_key', 'unlocked_at', 'seen_at', 'featured'])]
class UserAchievement extends Model
{
    /** @use HasFactory<UserAchievementFactory> */
    use HasFactory;

    public const MAX_FEATURED = 3;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'unlocked_at' => 'datetime',
            'seen_at' => 'datetime',
            'featured' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Attach each comment author's photo, featured achievement keys, avatar frame and name flair, matched by username.
     *
     * @param  Collection<int, object>  $comments
     * @return Collection<int, object>
     */
    public static function attachToComments(Collection $comments): Collection
    {
        $featuredByUsername = self::query()
            ->join('users', 'users.id', '=', 'user_achievements.user_id')
            ->whereIn('users.username', $comments->pluck('username')->unique())
            ->where('user_achievements.featured', true)
            ->orderBy('user_achievements.unlocked_at')
            ->get(['users.username', 'user_achievements.achievement_key'])
            ->groupBy('username');

        $authors = User::query()->whereIn('username', $comments->pluck('username')->unique())->get(['id', 'username', 'avatar_path'])->keyBy('username');
        $flair = app(AvatarFrameService::class)->flairFor($authors->pluck('id'));

        return $comments->each(function (object $comment) use ($featuredByUsername, $authors, $flair): void {
            $author = $authors->get($comment->username);
            $comment->avatar_url = $author?->avatar_url;
            $comment->badges = ($featuredByUsername->get($comment->username) ?? collect())
                ->pluck('achievement_key')
                ->values()
                ->all();
            $authorFlair = $flair[$author?->id] ?? null;
            $comment->avatar_frame = $authorFlair['avatar_frame'] ?? null;
            $comment->name_flair = $authorFlair['name_flair'] ?? null;
        });
    }
}
