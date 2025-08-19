<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

use App\Models\User;
use App\Models\BracketChallengeEntry;
use App\Models\BracketChallengeEntryPrediction;
use App\Models\BracketChallenge;
use App\Models\Matchup;

use Carbon\Carbon; // Make sure to use Carbon for timestamps

class BracketChallengeEntrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $bracketChallenge = BracketChallenge::with('rounds.matchups.teams')->find(1);

        if (!$bracketChallenge) {
            $this->command->error('BracketChallenge with ID 1 not found.');
            return;
        }

        for ($i = 0; $i < 5; $i++) {
            $user_id = $i + 1;
            
            // Make sure the user exists, or create them
            $user = User::firstWhere('id', $user_id);
            if (!$user) {
                $this->command->error('user with ID '. $user_id . ' not found.');
                return;
            }

            $padded_user_id = str_pad($user_id, 3, '0', STR_PAD_LEFT);
            $padded_challenge_id = str_pad($bracketChallenge->id, 4, '0', STR_PAD_LEFT);
            $name = 'BCE-' . $padded_challenge_id . '-' . Str::upper(Str::random(5)) . $padded_user_id;

            $entry = BracketChallengeEntry::create([
                'name' => $name,
                'user_id' => $user_id,
                'bracket_challenge_id' => $bracketChallenge->id,
                'status' => 'active',
                'correct_predictions_count' => 0,
                'slug' => Str::slug($name),
            ]);

            $predictionsData = collect();
            $winnersOfPreviousRound = collect();

            foreach ($bracketChallenge->rounds as $round) {
                $matchupsInThisRound = $round->matchups;
                $winnersOfCurrentRound = collect();

                foreach ($matchupsInThisRound as $matchup) {
                    $teamsInMatchup = collect();
                    if ($round->order_index === 1) {
                        $teamsInMatchup = $matchup->teams;
                    } else {
                        // Get the two teams from the previous round's winners based on index
                        $team1 = $winnersOfPreviousRound->shift();
                        $team2 = $winnersOfPreviousRound->shift();
                        
                        if ($team1 && $team2) {
                            $teamsInMatchup = collect([$team1, $team2]);
                        }
                    }

                    if ($teamsInMatchup->isEmpty()) {
                        continue;
                    }

                    $predictedWinner = $teamsInMatchup->random();

                    $predictionsData->push([
                        'bracket_challenge_entry_id' => $entry->id,
                        'matchup_id' => $matchup->id,
                        'predicted_winner_team_id' => $predictedWinner->id,
                        'teams' => json_encode($teamsInMatchup->map(fn($team) => ['id' => $team->id])->toArray()),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    
                    $winnersOfCurrentRound->push($predictedWinner);
                }
                
                // Set the winners of the current round to be used for the next iteration
                $winnersOfPreviousRound = $winnersOfCurrentRound;
            }
            
            BracketChallengeEntryPrediction::insert($predictionsData->toArray());
            $this->command->info("Created bracket entry #{$entry->id} for user #{$user->id} with " . $predictionsData->count() . " predictions.");
        }
        
    }
}