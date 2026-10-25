<?php

namespace App\Services;

use App\Events\MessageSent;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Message;
use App\Models\User;
use App\Models\UserBlock;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Rules for private messages. A new thread is a request: the initiator may send
 * only `messaging.pending_message_limit` messages until the recipient accepts,
 * and a reply from the recipient accepts it.
 */
class MessagingService
{
    /** @var array<int, Collection<int, int>> */
    private array $blockedIds = [];

    public function __construct(private NotificationService $notifications) {}

    /** Opens the pair's thread, or reuses the existing one, and sends the first message. */
    public function start(User $sender, User $recipient, string $body): Conversation
    {
        abort_if($sender->is($recipient), 422);
        $this->ensureCanMessage($sender, $recipient);

        $existing = $this->findDirect($sender, $recipient);

        if ($existing !== null) {
            $this->send($sender, $existing, $body);

            return $existing;
        }

        try {
            $conversation = DB::transaction(function () use ($sender, $recipient, $body): Conversation {
                $conversation = Conversation::create([
                    'direct_key' => Conversation::directKey($sender, $recipient),
                    'initiator_id' => $sender->id,
                ]);
                $conversation->participants()->createMany([
                    ['user_id' => $sender->id, 'accepted_at' => now()],
                    ['user_id' => $recipient->id],
                ]);
                $conversation->load('participants');
                $this->storeMessage($sender, $conversation, $body);

                return $conversation;
            });
        } catch (UniqueConstraintViolationException) {
            $conversation = $this->findDirect($sender, $recipient);
            $this->send($sender, $conversation, $body);

            return $conversation;
        }

        $this->notifications->messageRequested($sender, $recipient, $conversation, $body);

        return $conversation;
    }

    public function send(User $sender, Conversation $conversation, string $body): Message
    {
        $conversation->loadMissing('participants.user');
        $own = $this->participantOrFail($conversation, $sender);
        $other = $conversation->otherParticipant($sender);

        abort_unless($sender->canParticipate(), 403);
        abort_if($other?->user === null || $other->declined_at !== null, 403);
        $this->ensureCanMessage($sender, $other->user);

        if ($other->accepted_at === null && $conversation->messages()->where('user_id', $sender->id)->count() >= config('messaging.pending_message_limit')) {
            throw ValidationException::withMessages(['body' => __('votmee.message_request_pending')]);
        }

        return DB::transaction(function () use ($sender, $conversation, $own, $body): Message {
            if (! $own->hasAccepted()) {
                $own->update(['accepted_at' => now(), 'declined_at' => null]);
            }

            return $this->storeMessage($sender, $conversation, $body);
        });
    }

    public function accept(User $user, Conversation $conversation): void
    {
        $this->participantOrFail($conversation, $user)->update(['accepted_at' => now(), 'declined_at' => null]);
    }

    /** The initiator is not told; the thread simply stops accepting their messages. */
    public function decline(User $user, Conversation $conversation): void
    {
        $this->participantOrFail($conversation, $user)->update(['declined_at' => now()]);
    }

    public function markRead(User $user, Conversation $conversation): void
    {
        $participant = $this->participantOrFail($conversation, $user);
        $latest = $conversation->messages()->max('id');

        if ($latest !== null && $participant->last_read_message_id !== $latest) {
            $participant->update(['last_read_message_id' => $latest]);
        }
    }

    public function participantOrFail(Conversation $conversation, User $user): ConversationParticipant
    {
        $participant = $conversation->loadMissing('participants')->participantFor($user);
        abort_if($participant === null, 404);

        return $participant;
    }

    /** Accepted threads with a visible message from the other member past the read marker. */
    public function unreadCount(User $user): int
    {
        return $this->participations($user, 'inbox')
            ->whereExists(fn ($query) => $query->selectRaw('1')->from('messages')
                ->whereColumn('messages.conversation_id', 'conversation_participants.conversation_id')
                ->where('messages.user_id', '!=', $user->id)
                ->where('messages.status', 'visible')
                ->whereNull('messages.deleted_at')
                ->where(fn ($query) => $query->whereNull('conversation_participants.last_read_message_id')
                    ->orWhereColumn('messages.id', '>', 'conversation_participants.last_read_message_id')))
            ->count();
    }

    public function pendingRequestCount(User $user): int
    {
        return $this->participations($user, 'requests')->count();
    }

    /**
     * @return Builder<ConversationParticipant>
     */
    public function participations(User $user, string $box): Builder
    {
        return ConversationParticipant::query()
            ->where('user_id', $user->id)
            ->whereNull('declined_at')
            ->when($box === 'requests', fn ($query) => $query->whereNull('accepted_at'), fn ($query) => $query->whereNotNull('accepted_at'));
    }

    /**
     * @return array{id: int, state: string, other: array<string, mixed>|null, last_message: array<string, mixed>|null, unread: bool, last_message_at: string|null}
     */
    /**
     * @param  array<int, array{avatar_frame: string|null, name_flair: string|null}>|null  $flair  preloaded for a page of conversations
     */
    public function summary(Conversation $conversation, User $viewer, ?array $flair = null): array
    {
        $conversation->loadMissing(['participants.user', 'latestMessage']);
        $own = $conversation->participantFor($viewer);
        $other = $conversation->otherParticipant($viewer);
        $latest = $conversation->latestMessage;

        return [
            'id' => $conversation->id,
            'state' => match (true) {
                $other?->user === null || $this->blockedWith($viewer)->contains($other->user_id) => 'closed',
                $own?->accepted_at === null => 'request',
                $other->declined_at !== null => 'closed',
                $other->accepted_at === null => 'pending',
                default => 'active',
            },
            'other' => $other?->user ? [
                ...$this->notifications->actor($other->user),
                ...(($flair ?? app(AvatarFrameService::class)->flairFor([$other->user_id]))[$other->user_id] ?? []),
            ] : null,
            'last_message' => $latest ? $this->listing($latest) : null,
            'unread' => $latest !== null && $latest->user_id !== $viewer->id && $latest->id > (int) $own?->last_read_message_id,
            'last_message_at' => $conversation->last_message_at?->toIso8601String(),
        ];
    }

    /**
     * @return array{id: int, conversation_id: int, user_id: int, body: string, created_at: string|null}
     */
    public function listing(Message $message): array
    {
        return [
            'id' => $message->id,
            'conversation_id' => $message->conversation_id,
            'user_id' => $message->user_id,
            'body' => $message->body,
            'created_at' => $message->created_at?->toIso8601String(),
        ];
    }

    /**
     * Members the viewer blocked or was blocked by, loaded once per request for conversation lists.
     *
     * @return Collection<int, int>
     */
    private function blockedWith(User $viewer): Collection
    {
        return $this->blockedIds[$viewer->id] ??= UserBlock::query()
            ->where('blocker_id', $viewer->id)->pluck('blocked_id')
            ->merge(UserBlock::query()->where('blocked_id', $viewer->id)->pluck('blocker_id'));
    }

    private function ensureCanMessage(User $sender, User $recipient): void
    {
        abort_unless($sender->canParticipate(), 403);
        abort_if($sender->hasBlockedOrIsBlockedBy($recipient), 403);
    }

    private function findDirect(User $first, User $second): ?Conversation
    {
        return Conversation::query()->with('participants.user')->where('direct_key', Conversation::directKey($first, $second))->first();
    }

    /** The sender has read everything up to their own message. Both members' clients are told after commit. */
    private function storeMessage(User $sender, Conversation $conversation, string $body): Message
    {
        $message = $conversation->messages()->create(['user_id' => $sender->id, 'body' => Str::of($body)->trim()->toString(), 'status' => 'visible']);
        $conversation->update(['last_message_at' => $message->created_at]);
        $conversation->participantFor($sender)?->update(['last_read_message_id' => $message->id]);

        foreach ($conversation->participants as $participant) {
            MessageSent::dispatch($participant->user_id, $message);
        }

        return $message;
    }
}
