<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Builder; // Import Builder
use App\Models\Like;

class Comment extends Model
{
    protected $guarded = [];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user() : BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Existing relationship
    public function commentable(): MorphTo
    {
        return $this->morphTo();
    }

    // New relationships for replies
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(Comment::class, 'parent_id');
    }

    public function scopeWithUserAndReplyCount(Builder $query): void
    {
        $query->with('user')->withCount('replies');
    }

    public function likes(): MorphMany
    {
        return $this->morphMany(Like::class, 'likeable');
    }

    public function myVote()
    {
        if (auth()->check()) {
            return $this->morphOne(Like::class, 'likeable')
                        ->where('user_id', auth()->id());
        }

        // If no user is authenticated, return a query that will always be empty
        // This prevents any SQL errors and returns an empty relationship
        return $this->morphOne(Like::class, 'likeable')
                    ->whereRaw('1 = 0'); // A condition that is always false
    }

    public function likesOnly()
    {
        return $this->morphMany(Like::class, 'likeable')->where('is_like', true);
    }

    public function dislikesOnly()
    {
        return $this->morphMany(Like::class, 'likeable')->where('is_like', false);
    }
    
}
