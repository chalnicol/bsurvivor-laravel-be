<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Round extends Model
{
    use HasFactory;
    //
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'conference',
        'order_index',
        'bracket_challenge_id'
    ];

    public function matchups()
    {
        return $this->hasMany(Matchup::class);
    }

    public function bracket_challenge()
    {
        return $this->belongsTo(BracketChallenge::class);
    }

}
