<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\BracketChallengeEntry;
use Illuminate\Bus\Batchable; // Add this line

class ProcessEntryChunk implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable; // Add Batchable here

    public $entryIds;

    public function __construct(array $entryIds)
    {
        $this->entryIds = $entryIds;
    }

    public function handle()
    {
        $entries = BracketChallengeEntry::whereIn('id', $this->entryIds)
            ->with(['predictions.matchup.teams', 'bracketChallenge.rounds.matchups.teams'])
            ->get();
        
        if ($entries->isEmpty()) {
            return;
        }

        $actualMatchups = $entries->first()->bracketChallenge->rounds
            ->flatMap(fn ($round) => $round->matchups)
            ->keyBy('id');

        foreach ($entries as $entry) {
            $isEliminated = false;
            $correctCount = 0;

            foreach ($entry->predictions->sortBy(fn($p) => $p->matchup->order_index) as $prediction) {
                $actualMatchup = $actualMatchups->get($prediction->matchup_id);

                if ($actualMatchup->winner_team_id === null) {
                    continue;
                }

                $actualTeams = $actualMatchup->teams->pluck('id')->sort()->values();
                $predictedTeams = collect($prediction->teams)->pluck('id')->sort()->values();
            
                if ($predictedTeams->toJson() !== $actualTeams->toJson()) {
                    $isEliminated = true;
                    continue;
                }

                if ($prediction->predicted_winner_team_id == $actualMatchup->winner_team_id) {
                    $correctCount++;
                } else {
                    $isEliminated = true;
                }
            }
            
            $entryStatus = $isEliminated ? 'eliminated' : 'active';
            if ($actualMatchups->filter(fn($m) => $m->winner_team_id !== null)->count() === $entry->predictions->count()) {
                $entryStatus = $isEliminated ? 'eliminated' : 'won';
            }

            $entry->update([
                'status' => $entryStatus,
                'correct_predictions_count' => $correctCount
            ]);
        }
    }
}
