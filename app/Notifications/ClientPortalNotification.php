<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ClientPortalNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $type,
        private readonly string $title,
        private readonly string $message,
        private readonly string $action = 'notifications',
        private readonly array $context = [],
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return array_merge($this->context, [
            'type' => $this->type,
            'title' => $this->title,
            'message' => $this->message,
            'action' => $this->action,
        ]);
    }
}
