<?php

namespace App\Traits; // Important: Define the correct namespace

trait BracketChallengeTrait
{
    public function create_initial_matchups (int $roundId, array $teams) {

        if ( count($teams) !== 8 ) return [];

        $matchupBasis = [
            [0, 7],
            [3, 4],
            [2, 5],
            [1, 6],
        ];

        foreach ( $matchupBasis as $key => $matchup ) {

           $teamId_1 = $teams[$matchup[0]];
           $teamId_2 = $teams[$matchup[1]];

           $newMatchup = Matchup::create([
                'round_id' => $roundId,
                'wins_team_1' => 0,
                'wins_team_2' => 0,
                'winner_team_id' => null,
                'index' => $key,
            ]);
            
            if ( $newMatchup ) {
                $newMatchup->teams()->attach($teamId_1, [
                    'seed' => $matchup[0] + 1,
                    'slot' => 1,
                ]);
                $newMatchup->teams()->attach($teamId_2, [
                    'seed' => $matchup[1] + 1,
                    'slot' => 2
                ]); 
            }

        }

    }

    public function create_empty_matchups (int $bracketId, int $order) {

        $count = $order == 2 ? 2 : 1;

        for ( $i = 0; $i < $count; $i++ ) {
            $newMatchup = Matchup::create([
                'round_id' => $bracketId,
                'wins_team_1' => 0,
                'wins_team_2' => 0,
                'winner_team_id' => null,
                'index' => $i,
            ]);
        }
    }

    public function create_bracket_structure (int $bracketId, string $leagueName, array $teams ) 
    {

        //create 
        if ( $leagueName === 'NBA' ) {

            //create rounds for each conference 
            foreach ( $teams as $key => $team ) {
                for ( $i = 0; $i < 3; $i++ ) {
                    $newRound = Round::create([
                        'conference' => $key,
                        'order' => $i + 1,
                        'bracket_challenge_id' => $bracketId,
                    ]);

                    //create initial matchups on round 1
                    if ( $newRound ) {
                        if ( $i == 0 ) {
                            //..
                            $this->create_initial_matchups($newRound->id, $team);
                        }else {
                            //
                            $this->create_empty_matchups($newRound->id, $newRound->order);
                        }
                    }
                }
            }

            //create finals round.
            $finalRound = Round::create([
                'conference' => null,
                'order' => 4,
                'bracket_challenge_id' => $bracketId,
            ]);

            if ( $finalRound ) {
                $this->create_empty_matchups($finalRound->id, $finalRound->order);
            }

        }else {
            //todo for pba or non-nba leagues..
        }
        
        
    }
    
}