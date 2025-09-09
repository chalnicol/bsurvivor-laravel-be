<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class FriendRequestSentMailable extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $sender;
    public $recipient;
    public $url;

    /**
     * Create a new message instance.
     */
    public function __construct(string $sender, string $recipient, string $url)
    {
        $this->sender = $sender;
        $this->recipient = $recipient;
        $this->url = $url;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Friend Request Received from ' . $this->sender,
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
                '$recipient' => $this->recipient,
                '$sender' => $this->sender,
                '$url' => $this->url,
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
