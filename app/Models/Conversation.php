<?php

namespace App\Models;

use Database\Factories\ConversationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/** A private thread between exactly two members, keyed by the pair so each pair has one thread. */
#[Fillable(['direct_key', 'initiator_id', 'last_message_at'])]
class Conversation extends Model
{
    /** @use HasFactory<ConversationFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['last_message_at' => 'datetime'];
    }

    public static function directKey(User $first, User $second): string
    {
        return min($first->id, $second->id).':'.max($first->id, $second->id);
    }

    public function initiator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiator_id');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(ConversationParticipant::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(Message::class)->ofMany(['id' => 'max'], fn ($query) => $query->where('status', 'visible'));
    }

    public function participantFor(User $user): ?ConversationParticipant
    {
        return $this->participants->firstWhere('user_id', $user->id);
    }

    public function otherParticipant(User $user): ?ConversationParticipant
    {
        return $this->participants->first(fn (ConversationParticipant $participant): bool => $participant->user_id !== $user->id);
    }
}
