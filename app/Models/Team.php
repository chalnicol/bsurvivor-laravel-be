<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    //
    public function league () {
        return $this->belongsTo(League::class);
    }

    public function matchups()
    {
        return $this->belongsToMany(Matchup::class)
                    ->withPivot('seed', 'slot') // Include any pivot columns you need
                    ->withTimestamps(); // If matchup_team has created_at/updated_at
    }
    
}
