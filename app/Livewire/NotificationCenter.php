<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Bell + dropdown listing the current user's database notifications.
 *
 * Open/close state lives in Alpine so the panel opens instantly with no
 * server round-trip; this class only supplies data and the read actions.
 * Polls while the app is open so a tag made elsewhere shows up without a
 * page reload -- the in-app equivalent of a push.
 */
class NotificationCenter extends Component
{
    /** How many notifications to list in the dropdown. */
    public int $limit = 15;

    #[On('notifications-changed')]
    public function refreshNotifications(): void
    {
        // Body intentionally empty -- receiving the event re-renders the
        // component, which re-reads the notification list below.
    }

    public function markAsRead(string $id): void
    {
        $notification = Auth::user()->notifications()->whereKey($id)->first();

        $notification?->markAsRead();
    }

    public function markAllAsRead(): void
    {
        Auth::user()->unreadNotifications->markAsRead();
    }

    public function render()
    {
        $user = Auth::user();

        $notifications = $user
            ? $user->notifications()->latest()->limit($this->limit)->get()
            : collect();

        return view('livewire.notification-center', [
            'notifications' => $notifications,
            'unreadCount' => $user ? $user->unreadNotifications()->count() : 0,
        ]);
    }
}
