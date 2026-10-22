<?php

namespace App\Events;

use App\Models\Message;
use App\Models\User;
use App\Services\MessagingService;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/** A private message was sent; dispatched once per participant so each gets their own counters. */
class MessageSent implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public int $userId, public Message $message) {}

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('App.Models.User.'.$this->userId)];
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    /**
     * @return array{message: array<string, mixed>, unread_messages: int, pending_requests: int}
     */
    public function broadcastWith(): array
    {
        $messaging = app(MessagingService::class);
        $user = User::query()->findOrFail($this->userId);

        return [
            'message' => $messaging->listing($this->message),
            'unread_messages' => $messaging->unreadCount($user),
            'pending_requests' => $messaging->pendingRequestCount($user),
        ];
    }
}
