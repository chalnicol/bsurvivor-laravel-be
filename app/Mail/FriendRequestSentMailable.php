<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\User;

class FriendRequestSentMailable extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $sender;
    public $recipient;

    /**
     * Create a new message instance.
     */
    public function __construct(User $sender, User $recipient)
    {
        $this->sender = $sender;
        $this->recipient = $recipient;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Friend Request from ' . $this->sender->name,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
         return new Content(
            // markdown: 'emails.password-reset', // Use a Blade Markdown view
            view: 'emails.custom-friend-request',
            with: [
                '$recipient' => $this->recipient->name,
                '$sender' => $this->sender->name,
                '$url' => url('/friends'),
            ]
        );      
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
