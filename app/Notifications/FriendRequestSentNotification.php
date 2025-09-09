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


class FriendRequestSentNotification extends Notification implements ShouldQueue, ShouldBroadcast
{
    use Queueable;

    protected $sender;
    protected $notifiableUserId;
    protected $url;

    /**
     * Create a new notification instance.
     */
    public function __construct(User $sender, int $notifiableUserId)
    {
        //
        $this->sender = $sender;
        $this->notifiableUserId = $notifiableUserId;
        $this->url = url( config('app.frontend_url') . '/friends?tab=received');
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
            new PrivateChannel('users.' . $this->notifiableUserId),
        ];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): FriendRequestSentMailable
    {
        return (new FriendRequestSentMailable($this->sender->username, $notifiable->username, $this->url ))
            ->to($notifiable->email);
    }

    public function toBroadcast(object $notifiable): array
    {
        $unreadCount = $notifiable->unreadNotifications()->count();

        return [
            'unread_count' => $unreadCount,
        ];

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
            'message' => 'You received a friend request from ' . $this->sender->username .'.',
            'url' => $this->url,
        ];
    }

}
