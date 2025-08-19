<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL; // Import URL facade

class ResetPasswordNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public string $token;
    public string $frontendUrl;

    /**
     * Create a new notification instance.
     */
    public function __construct()
    {
        //
        $this->token = $token;
        // IMPORTANT: Use config('app.frontend_url') for dynamic frontend URL
        // This is where the user lands to enter their new password
        $this->frontendUrl = config('app.frontend_url') . '/reset-password?token=' . $this->token . '&email=' . $this->notifiable->getEmailForPasswordReset();
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        // return (new MailMessage)
        //     ->line('The introduction to the notification.')
        //     ->action('Notification Action', url('/'))
        //     ->line('Thank you for using our application!');
        return (new MailMessage)
                    ->subject('Reset Your Password')
                    ->line('You are receiving this email because we received a password reset request for your account.')
                    ->action('Reset Password', $this->frontendUrl)
                    ->line('This password reset link will expire in ' . config('auth.passwords.users.expire') . ' minutes.')
                    ->line('If you did not request a password reset, no further action is required.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
