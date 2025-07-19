<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

use Illuminate\Contracts\Auth\CanResetPassword; // Add this
use Illuminate\Auth\Passwords\CanResetPassword as CanResetPasswordTrait; // Add this trait
use Illuminate\Support\Facades\Mail;

use App\Notifications\ResetPasswordNotification; // Import your custom notification
use App\Mail\PasswordResetMail;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasRoles, HasFactory, Notifiable, HasApiTokens, CanResetPasswordTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'username',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Send the password reset notification.
     *
     * @param  string  $token
     * @return void
     */
    public function sendPasswordResetNotification($token): void
    {
        // Get user's email and name for the Mailable
        $userEmail = $this->getEmailForPasswordReset();
        $userName = $this->name ?? $this->email; // Assuming 'name' column exists, otherwise use email

        // Send your custom Mailable
        // Mail::to($userEmail)->send(new PasswordResetMail($token, $userEmail, $userName));
        Mail::to($userEmail)->queue(new PasswordResetMail($token, $userEmail, $userName));

    }
}
