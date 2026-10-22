<?php

namespace App\Events;

use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Queue\SerializesModels;

/** An unread notification was updated in place, so the client replaces it by id. */
class NotificationRefreshed implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public User $user, public DatabaseNotification $notification) {}

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('App.Models.User.'.$this->user->id)];
    }

    public function broadcastAs(): string
    {
        return 'notification.refreshed';
    }

    /**
     * @return array{notification: array<string, mixed>, unread_count: int}
     */
    public function broadcastWith(): array
    {
        return [
            'notification' => app(NotificationService::class)->listing($this->notification),
            'unread_count' => $this->user->unreadNotifications()->count(),
        ];
    }
}
