<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Broadcasting\PrivateChannel;

use Illuminate\Notifications\Notification;
use App\Models\BracketChallengeEntry;
use App\Mail\BracketEntryUpdatedMailable; // Import the Mailable


class BracketEntryUpdated extends Notification implements ShouldQueue, ShouldBroadcast
{
    use Queueable;

    protected $notifiableUserId;
    protected $challengeName;
    protected $entryUrl;

    /**
     * Create a new notification instance.
     */
    public function __construct(BracketChallengeEntry $entry)
    {
      
        // $this->entry = $entry;
        $this->notifiableUserId = $entry->user->id;
        $this->challengeName = $entry->bracketChallenge->name;
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
    public function toMail(object $notifiable): BracketEntryUpdatedMailable
    {

        
        return (new BracketEntryUpdatedMailable($notifiable->username, $this->challengeName, $this->entryUrl ))
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
            'message' => '"'. $this->challengeName . '" bracket challenge has been updated.',
            'url' => $this->entryUrl
        ];
    }

}
