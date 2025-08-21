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
use App\Notifications\VerifyEmailNotification;

use App\Mail\PasswordResetMailable;

use App\Models\BracketChallengeEntry;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;

use Illuminate\Database\Eloquent\Builder;


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
        'is_blocked',
        'email_verification_token'
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
            'is_blocked' => 'boolean'
        ];
    }

     /**
     * The accessors to append to the model's array form.
     * This makes 'roles' and 'permissions' available when the user model is converted to an array/JSON.
     *
     * @var array
     */
    protected $appends = [
        // 'roles', // Spatie adds roles directly, no need to append.
        'all_permissions', // Custom accessor to flatten all permissions
        // 'can_access', // (Optional) For a simple boolean check 
    ];

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

        $userName = $this->username ?? $this->email; // Assuming 'name' column exists, otherwise use email

        // Send your custom Mailable
        Mail::to($userEmail)->queue(new PasswordResetMailable($token, $userEmail, $userName));

    }

    // public function sendEmailVerificationNotification()
    // {
    //     $this->notify(new VerifyEmailNotification);
    // }

    /**
     * Get all permissions of the user, including those from roles.
     * This consolidates permissions into a single array for easier frontend consumption.
     */
    public function getAllPermissionsAttribute()
    {
        // Spatie provides this helper to get all permissions directly
        return $this->getAllPermissions()->pluck('name');
    }

    // Optional: Add a helper method
    public function isBlocked(): bool
    {
        return (bool) $this->is_blocked;
    }

    public function entries()
    {
        return $this->hasMany(BracketChallengeEntry::class);
    }

    /**
     * The friends that the user has accepted.
     */

    public function hasAnyFriendshipWith(User $user): bool
    {
        return $this->belongsToMany(User::class, 'friendships')
            ->where(function (Builder $query) use ($user) {
                $query->where('user_id', $this->id)
                    ->where('friend_id', $user->id);
            })
            ->orWhere(function (Builder $query) use ($user) {
                $query->where('user_id', $user->id)
                    ->where('friend_id', $this->id);
            })
            ->exists();
    }
    public function friendsOfMine(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'friendships', 'user_id', 'friend_id')
            ->wherePivot('status', 'accepted')
            ->withTimestamps();
    }

    public function friendOf(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'friendships', 'friend_id', 'user_id')
            ->wherePivot('status', 'accepted')
            ->withTimestamps();
    }

    /**
     * The users who have requested the current user as a friend.
     */
    public function friendRequestsSent(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'friendships', 'user_id', 'friend_id')
            ->wherePivot('status', 'pending')
            ->withTimestamps();
    }

    /**
     * The users to whom the current user has sent a friend request.
     */
    public function friendRequestsReceived(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'friendships', 'friend_id', 'user_id')
            ->wherePivot('status', 'pending')
            ->withTimestamps();
    }



}
