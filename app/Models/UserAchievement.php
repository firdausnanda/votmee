<?php

namespace App\Models;

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
     * Attach each comment author's featured achievement keys, matched by username.
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

        return $comments->each(function (object $comment) use ($featuredByUsername): void {
            $comment->badges = ($featuredByUsername->get($comment->username) ?? collect())
                ->pluck('achievement_key')
                ->values()
                ->all();
        });
    }
}
