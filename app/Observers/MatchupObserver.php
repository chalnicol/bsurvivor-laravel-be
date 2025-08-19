<?php

namespace App\Observers;

use App\Models\Matchup;
use App\Jobs\CheckEntryStatus;

class MatchupObserver
{
    /**
     * Handle the Matchup "created" event.
     */
    public function created(Matchup $matchup): void
    {
        //
    }

    /**
     * Handle the Matchup "updated" event.
     */
    public function updated(Matchup $matchup): void
    {
        // if ($matchup->isDirty('winner_team_id') && $matchup->winner_team_id !== null) {
        if ( $matchup->isDirty('winner_team_id') ) {
            // Fetch the bracket challenge associated with this matchup (assuming it's in a relationship with BracketChallenge model'))
            $bracketChallenge = $matchup->round->bracketChallenge;

            // Dispatch a job for each entry to update its status
            $bracketChallenge->entries()->chunk(100, function($entries) use($bracketChallenge) {
                // foreach ($entries as $entry) {
                //     CheckEntryStatus::dispatch($entry);
                // }
                CheckEntryStatus::dispatch($entries, $bracketChallenge);
            });
        }
    }

    /**
     * Handle the Matchup "deleted" event.
     */
    public function deleted(Matchup $matchup): void
    {
        //
    }

    /**
     * Handle the Matchup "restored" event.
     */
    public function restored(Matchup $matchup): void
    {
        //
    }

    /**
     * Handle the Matchup "force deleted" event.
     */
    public function forceDeleted(Matchup $matchup): void
    {
        //
    }
}
