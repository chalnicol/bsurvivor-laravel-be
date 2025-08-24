<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Broadcasting\PrivateChannel;

use Illuminate\Notifications\Notification;
use App\Mail\FriendRequestSentMailable; // Import the Mailable
use App\Models\User;


class FriendRequestSent extends Notification implements ShouldBroadcast
{
    use Queueable;

    protected $sender;
    protected $notifiableUser;

    /**
     * Create a new notification instance.
     */
    public function __construct(User $sender, User $notifiableUser)
    {
        //
        $this->sender = $sender;
        $this->notifiableUser = $notifiableUser;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function broadcastOn(): array
    {
        // The $this->notifiable is the recipient of the notification (the User model).
        return [
            new PrivateChannel('users.' . $this->notifiableUser->id),
        ];
    }

    public function toBroadcast(object $notifiable): array
    {
        $unreadCount = $this->notifiableUser->unreadNotifications()->count();

        return [
            'unread_count' => $unreadCount,
        ];

    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        //return new FriendRequestSentMailable($this->sender, $notifiable);
    }

    /**
     * Get the array representation of the notification for the database channel.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toDatabase($notifiable)
    {
        return [
            'sender_id' => $this->sender->id,
            'sender_name' => $this->sender->username,
            'message' => $this->sender->username . ' sent you a friend request.',
            'url' => '/friends'
        ];
    }

}
