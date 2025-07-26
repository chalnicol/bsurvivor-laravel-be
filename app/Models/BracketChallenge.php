<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class BracketChallenge extends Model
{
    
    use HasFactory;

    protected $table = 'bracket_challenge'; // Explicitly define table name if it's not 'bracket_challenges'

    protected $fillable = [
        'league_id',
        'name',
        'description',
        'is_public',
        'start_date',
        'end_date',
        'bracket_data',
        'slug',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_public' => 'boolean',
        'bracket_data' => 'array',
    ];

    public function league () {
        return $this->belongsTo(League::class);
    }

    public function rounds () {
        return $this->hasMany(Round::class);
    }


  
}
