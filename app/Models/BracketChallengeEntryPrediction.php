<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BracketChallengeEntryPrediction extends Model
{
    //
    protected $table = 'bce_predictions';

    protected $fillable = [
        'bracket_entry_id',
        'matchup_id',
        'predicted_winner_team_id',
        'teams',
    ];

    protected $casts = [
        'teams' => 'array',
    ];

  
    /**
     * The bracket entry this prediction belongs to.
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function entry()
    {
        return $this->belongsTo(BracketChallengeEntry::class);
    }

    /**
     * The matchup this prediction is for.
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function matchup()
    {
        return $this->belongsTo(Matchup::class);
    }

    /**
     * The team that was predicted to win.
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function predictedWinner()
    {
        return $this->belongsTo(Team::class, 'predicted_winner_team_id');
    }

}
