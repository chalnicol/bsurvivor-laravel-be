<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Broadcasting\PrivateChannel;

use Illuminate\Notifications\Notification;
use App\Mail\CommentToEntryMailable; // Import the Mailable

use App\Models\BracketChallengeEntry;

class CommentToEntry extends Notification implements ShouldQueue, ShouldBroadcast
{
    use Queueable;

    protected $notifiableUserId;
    protected $sender;
    protected $entryName;
    protected $entryUrl;


    /**
     * Create a new notification instance.
     */
    public function __construct(int $notifiableUserId, string $sender, BracketChallengeEntry $entry )
    {
      
        // $this->entry = $entry;
        $this->notifiableUserId = $notifiableUserId;
        $this->sender = $sender;
        $this->entryName = $entry->name;

        $this->entryUrl = url( config('app.frontend_url') . '/bracket-challenge-entries/'. $entry->slug );

    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail', 'broadcast'];
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
    public function toMail(object $notifiable): CommentToEntryMailable
    {
        

        return (new CommentToEntryMailable($this->sender, $notifiable->username, $this->entryName, $this->entryUrl ))
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
            'sender' => $this->sender,
            'entryName' => $this->entryName,
            'message' => 'Your entry '. $this->entryName .' has a new comment from '. $this->sender .'!',
            'url' => $this->entryUrl
        ];
    }

}
