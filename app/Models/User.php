<?php

namespace App\Models;

use App\Services\ProfileImageProcessor;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Mattiverse\Userstamps\Traits\Userstamps;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'username', 'role', 'locale', 'region_code', 'restricted_until', 'trusted_at', 'trust_revoked_at', 'notification_preferences', 'deleted_by'])]
#[Hidden(['password', 'remember_token', 'google_id', 'avatar_path', 'cover_path'])]
#[Appends(['avatar_url', 'cover_url'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable, SoftDeletes, Userstamps;

    public const CREATED_BY = null;

    public const UPDATED_BY = null;

    public const DELETED_BY = 'deleted_by';

    public static function suggestedUsername(string $name): string
    {
        $baseUsername = rtrim(Str::limit(Str::slug($name), 24, ''), '-');
        $baseUsername = strlen($baseUsername) >= 3 ? $baseUsername : 'user';
        $username = $baseUsername;
        $suffix = 2;

        while (self::withTrashed()->where('username', $username)->exists()) {
            $username = $baseUsername.'-'.$suffix;
            $suffix++;
        }

        return $username;
    }

    /**
     * The labelled Votmee account that publishes generated rankings. It cannot sign in
     * and never earns points, missions, achievements, or leaderboard places.
     */
    public static function official(): self
    {
        $account = config('autopilot.official_account');
        $official = self::query()->where('is_official', true)->orderBy('id')->first();

        if ($official !== null) {
            return $official;
        }

        $official = self::withTrashed()->firstOrNew(['email' => $account['email']]);
        $official->forceFill([
            'name' => $account['name'],
            'username' => $official->username ?? (self::withTrashed()->where('username', $account['username'])->exists() ? self::suggestedUsername($account['username']) : $account['username']),
            'password' => Str::random(64),
            'role' => 'user',
            'is_official' => true,
            'locale' => 'id',
            'region_code' => 'GLOBAL',
            'deleted_at' => null,
        ])->save();

        return $official;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'restricted_until' => 'datetime',
            'trusted_at' => 'datetime',
            'trust_revoked_at' => 'datetime',
            'is_official' => 'boolean',
            'notification_preferences' => 'array',
            'password' => 'hashed',
        ];
    }

    /**
     * @return Attribute<string|null, never>
     */
    protected function avatarUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->profileImageUrl('avatar'));
    }

    /**
     * @return Attribute<string|null, never>
     */
    protected function coverUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->profileImageUrl('cover'));
    }

    /**
     * Versioned, immutable-cacheable URL of a profile image, or null when none is set.
     */
    private function profileImageUrl(string $type): ?string
    {
        return ProfileImageProcessor::url($this->id, $type, $this->attributes[ProfileImageProcessor::column($type)] ?? null);
    }

    /** Trusted contributors publish content revisions without waiting for review. */
    public function isTrustedContributor(): bool
    {
        return $this->trusted_at !== null && $this->canParticipate();
    }

    public function canParticipate(): bool
    {
        return $this->email_verified_at !== null
            && ($this->restricted_until === null || $this->restricted_until->isPast());
    }

    /** Whether the member receives this notification type. Only switched-off groups are stored. */
    public function wantsNotification(string $type): bool
    {
        $group = config("notification_preferences.types.{$type}");

        return $group === null || ($this->notification_preferences[$group] ?? true) !== false;
    }

    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class);
    }

    public function pointLedgers(): HasMany
    {
        return $this->hasMany(PointLedger::class);
    }

    public function supportEvents(): HasMany
    {
        return $this->hasMany(SupportEvent::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function savedRankings(): HasMany
    {
        return $this->hasMany(SavedRanking::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class, 'reporter_id');
    }

    public function conversationParticipations(): HasMany
    {
        return $this->hasMany(ConversationParticipant::class);
    }

    public function blocks(): HasMany
    {
        return $this->hasMany(UserBlock::class, 'blocker_id');
    }

    /** A block in either direction stops both members from messaging each other. */
    public function hasBlockedOrIsBlockedBy(User $other): bool
    {
        return UserBlock::query()
            ->where(fn ($query) => $query->where('blocker_id', $this->id)->where('blocked_id', $other->id))
            ->orWhere(fn ($query) => $query->where('blocker_id', $other->id)->where('blocked_id', $this->id))
            ->exists();
    }
}
