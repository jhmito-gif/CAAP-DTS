<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/**
 * Sent to the signer whenever a signing session is opened on their account, so
 * a session they did not open is visible to them immediately.
 */
class SigningSessionOpened extends Notification
{
    public function __construct(
        protected ?string $device = null,
        protected ?string $ip = null,
        protected bool $remembered = false,
    ) {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'signing_session_opened',
            'url' => route('esign.queue', [], false),
            'title' => 'Signing session opened',
            'message' => 'A signing session was opened on your account'
                . ($this->ip ? " from {$this->ip}" : '')
                . ($this->remembered ? ', and this device will be remembered.' : '.'),
            'device' => $this->device,
            'ip' => $this->ip,
        ];
    }
}
