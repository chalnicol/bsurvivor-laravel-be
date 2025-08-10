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
        'slug'
    ];

    protected $casts = [
        // 'is_winner' => 'boolean',
    ];

     public function user () {
        return $this->belongsTo(User::class);
    }

    public function bracketChallenge () {
        return $this->belongsTo(BracketChallenge::class, 'bracket_challenge_id');
    }

    public function predictions()
    {
        return $this->hasMany(BracketChallengeEntryPrediction::class, 'bracket_challenge_entry_id');
    }

    public function scopeOwnedBy($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    

}
