<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
// use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;

use App\Models\BracketChallengeEntry;
use App\Models\User;

class CheckEntryStatus implements ShouldQueue
{
    // use Queueable;
    use Dispatchable, Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(BracketChallengeEntry $bracketChallengeEntry )
    {
        //
        $this->bracketChallengeEntry = $bracketChallengeEntry;

    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        $actualWinners = $this->bracketChallengeEntry->bracketChallenge->rounds
            ->flatMap(fn ($round) => $round->matchups)
            ->filter(fn ($matchup) => $matchup->winner_team_id !== null)
            ->mapWithKeys(fn ($matchup) => [$matchup->id => $matchup->winner_team_id]);

        // Use a variable to track if the bracket is still in the running
        $isEliminated = false;
        $correctCount = 0;

        foreach ($this->bracketChallengeEntry->predictions as $prediction) {
            $actualWinnerId = $actualWinners->get($prediction->matchup_id);

            if ($actualWinnerId !== null) {
                // Check if the prediction was correct
                if ($actualWinnerId == $prediction->predicted_winner_team_id) {
                    $correctCount++;
                } else {
                    // One wrong prediction means the entry is eliminated
                    $isEliminated = true;
                    // We can break here since the status is now determined
                    // ...or continue to get a full count of correct predictions
                }
            }
        }
        
        $entryStatus = $isEliminated ? 'eliminated' : 'active';
        
        // You could set a 'complete' status here if all matchups have winners
        if ($actualWinners->count() === $this->bracketChallengeEntry->predictions->count()) {
            $entryStatus = $isEliminated ? 'eliminated' : 'won';
        }

        $this->bracketChallengeEntry->update([
            'status' => $entryStatus,
            'correct_predictions_count' => $correctCount
        ]);
    }
}
