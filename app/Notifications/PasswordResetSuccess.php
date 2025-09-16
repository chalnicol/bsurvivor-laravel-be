<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Broadcasting\PrivateChannel;

use Illuminate\Notifications\Notification;
use App\Mail\PasswordResetSuccessMailable; // Import the Mailable

class PasswordResetSuccess extends Notification implements ShouldQueue, ShouldBroadcast
{
    use Queueable;

    protected $notifiableUserId;
    protected $url;

    /**
     * Create a new notification instance.
     */
    public function __construct(int $notifiableUserId, string $url)
    {
        $this->notifiableUserId = $notifiableUserId;
        $this->url = $url;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
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
    public function toMail(object $notifiable): PasswordResetSuccessMailable
    {
        $url = url(config('app.frontend_url') . $this->url);

        return (new PasswordResetSuccessMailable($notifiable->username, $url))
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
            'message' => 'You have updated your password successfully.',
            'url' => url( config('app.frontend_url') . $this->url)
        ];
    }

}
