<?php

namespace App\Jobs;

use Illuminate\Support\Collection;
use Illuminate\Contracts\Queue\ShouldQueue;
// use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;

use App\Models\BracketChallenge;


class CheckEntryStatus implements ShouldQueue
{
    // use Queueable;
    use Dispatchable, Queueable;

    /**
     * Create a new job instance.
     */
    // public function __construct(BracketChallengeEntry $bracketChallengeEntry )
    // {
    //     //
    //     $this->bracketChallengeEntry = $bracketChallengeEntry;

    // }

    /**
     * Execute the job.
     */
    // public function handle()
    // {
    //     $actualWinners = $this->bracketChallengeEntry->bracketChallenge->rounds
    //         ->flatMap(fn ($round) => $round->matchups)
    //         ->filter(fn ($matchup) => $matchup->winner_team_id !== null &&  )
    //         ->mapWithKeys(fn ($matchup) => [$matchup->id => $matchup->winner_team_id]);

    //     // Use a variable to track if the bracket is still in the running
    //     $isEliminated = false;
    //     $correctCount = 0;

    //     foreach ($this->bracketChallengeEntry->predictions as $prediction) {
    //         $actualWinnerId = $actualWinners->get($prediction->matchup_id);

    //         if ($actualWinnerId !== null) {
    //             // Check if the prediction was correct
    //             if ($actualWinnerId == $prediction->predicted_winner_team_id) {
    //                 $correctCount++;
    //             } else {
    //                 // One wrong prediction means the entry is eliminated
    //                 $isEliminated = true;
    //                 // We can break here since the status is now determined
    //                 // ...or continue to get a full count of correct predictions
    //             }
    //         }
    //     }
        
    //     $entryStatus = $isEliminated ? 'eliminated' : 'active';
        
    //     // You could set a 'complete' status here if all matchups have winners
    //     if ($actualWinners->count() === $this->bracketChallengeEntry->predictions->count()) {
    //         $entryStatus = $isEliminated ? 'eliminated' : 'won';
    //     }

    //     $this->bracketChallengeEntry->update([
    //         'status' => $entryStatus,
    //         'correct_predictions_count' => $correctCount
    //     ]);
    // }

    public $entries;
    public $bracketChallenge;

    public function __construct(Collection $entries, BracketChallenge $bracketChallenge)
    {
        $this->entries = $entries;
        $this->bracketChallenge = $bracketChallenge;
    }

    public function handle()
    {
        // Eager load all actual matchups at once
        $actualMatchups = $this->bracketChallenge->rounds
            ->flatMap(fn ($round) => $round->matchups)
            ->keyBy('id');

        foreach ($this->entries as $entry) {
            $isEliminated = false;
            $correctCount = 0;

            // Eager load predictions to prevent N+1 issue
            $entry->load('predictions.matchup');

            $sortedPredictions = $entry->predictions->sortBy(fn($p) => $p->matchup->order_index);

            foreach ($sortedPredictions as $prediction) {
                
                $actualMatchup = $actualMatchups->get($prediction->matchup_id);

                // Skip if the matchup has not been completed yet
                if ($actualMatchup->winner_team_id === null) {
                    continue;
                }

                // If the bracket has already been eliminated, stop processing
                // if ($isEliminated) {
                //     continue;
                // }

                // Get the actual team IDs for this matchup
                $actualTeams = $actualMatchup->teams->pluck('id')->sort()->values();

                // Get the user's predicted team IDs for this matchup
                // The 'teams' column is already cast to an array
                $predictedTeams = collect($prediction->teams)->pluck('id')->sort()->values();
            
                // If the teams in the user's predicted matchup do not match the actual teams, the bracket is eliminated
                if ($predictedTeams->toJson() !== $actualTeams->toJson()) {
                    $isEliminated = true;
                    continue;
                }

                // If the matchup is valid, check if the predicted winner is correct
                if ($prediction->predicted_winner_team_id == $actualMatchup->winner_team_id) {
                    $correctCount++;
                } else {
                    $isEliminated = true;
                }
            }
            
            $entryStatus = $isEliminated ? 'eliminated' : 'active';
            if ($actualMatchups->filter(fn($m) => $m->winner_team_id !== null)->count() === $sortedPredictions->count()) {
                $entryStatus = $isEliminated ? 'eliminated' : 'won';
            }

            $entry->update([
                'status' => $entryStatus,
                'correct_predictions_count' => $correctCount
            ]);
        }
    }
}
