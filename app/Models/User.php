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
}
