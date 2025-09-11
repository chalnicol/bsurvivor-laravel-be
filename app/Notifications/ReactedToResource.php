<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Broadcasting\PrivateChannel;

use Illuminate\Notifications\Notification;
use App\Mail\RepliedToCommentMailable; // Import the Mailable


class ReactedToResource extends Notification implements ShouldQueue, ShouldBroadcast
{
    use Queueable;

    protected $notifiableUserId;
    protected $sender;
    protected $url;
    protected $resource;
    protected $vote;


    /**
     * Create a new notification instance.
     */
    public function __construct(int $notifiableUserId, string $sender, string $resource, string $vote, string $url)
    {
      
        // $this->entry = $entry;
        $this->notifiableUserId = $notifiableUserId;
        $this->sender = $sender;
        $this->resource = $resource;
        $this->vote = $vote;
        $this->url = url( config('app.frontend_url') . $url );

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
    public function toMail(object $notifiable): RepliedToCommentMailable
    {

        return (new RepliedToCommentMailable($this->sender, $notifiable->username, $this->url ))
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
            
            'message' => $this->sender . ' gave your ' . $this->resource . ' a ' . $this->vote . '!',
            'url' => $this->url
        ];
    }

}
