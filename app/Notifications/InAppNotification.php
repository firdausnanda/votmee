<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

/**
 * Base for notifications stored for the in-app bell. The payload is structured data;
 * the sentence is rendered on the client in the viewer's locale.
 */
abstract class InAppNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public const TYPE = '';

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(public array $payload = [])
    {
        $this->afterCommit();
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return $notifiable->wantsNotification(static::TYPE) ? ['database', 'broadcast'] : [];
    }

    /** Same shape as NotificationService::listing, so the client adds it without another request. */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'notification' => [
                'id' => $this->id,
                'type' => static::TYPE,
                'data' => $this->toArray($notifiable),
                'read' => false,
                'created_at' => now('UTC')->toIso8601String(),
            ],
            'unread_count' => $notifiable->unreadNotifications()->count(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return ['type' => static::TYPE, ...$this->payload];
    }
}
