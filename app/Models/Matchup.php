<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Matchup extends Model
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
        'matchup_index',
        'wins_team_1',
        'wins_team_2',
        'winner_team_id',
        'round_id',
    ];

    public function round () 
    {
        return $this->belongsTo(Round::class);
    }

    public function teams()
    {
        return $this->belongsToMany(Team::class)
                    ->withPivot('seed', 'slot') // Include any pivot columns you need
                    ->withTimestamps(); // If matchup_team has created_at/updated_at
    }

    // If you want to easily get the winner
    public function winner()
    {
        return $this->belongsTo(Team::class, 'winner_team_id');
    }


    
}
