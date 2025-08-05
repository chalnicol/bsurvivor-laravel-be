<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BracketChallengeEntry extends Model
{
    //
    protected $table = 'bracket_challenge_entries';

    protected $fillable = [
        'name',
        'bracket_challenge_id',
        'user_id',
        'entry_data',
        'slug'
    ];

    protected $casts = [
        'is_winner' => 'boolean',
        'entry_data' => 'array',
    ];

    public function bracket_challenge () {
        return $this->belongsTo(BracketChallenge::class);
    }

    public function user () {
        return $this->belongsTo(User::class);
    }

    public function scopeOwnedBy($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

}
