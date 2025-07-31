<?php

namespace App\Traits; // Important: Define the correct namespace

use App\Models\Round;
use App\Models\Matchup;
use App\Models\BracketChallenge;
use App\Models\Team;

trait BracketChallengeTrait
{

    public function generate_matchups (array $teams)
    {
        if ( count($teams) !== 8 ) return [];

        $matchupBasis = [
            [0, 7],
            [3, 4],
            [2, 5],
            [1, 6],
        ];

        $matchups = [];

        foreach ( $matchupBasis as $key => $basis ) {

            $newMatchup = [
                'team1' => [ 'id' => $teams[$basis[0]], 'seed' => $basis[0] + 1 ],
                'team2' => [ 'id' => $teams[$basis[1]], 'seed' => $basis[1] + 1 ],          
            ];
            
            $matchups[] = $newMatchup;
        }
        return $matchups;
    }

    public function create_initial_matchups ( string $league, string $conference, Round $round, array $teams ) 
    {

        $generatedMatchups = $this->generate_matchups($teams);
        
        $conferenceLetter = $conference == 'east' ? 'E' : 'W';

        foreach ( $generatedMatchups as $key => $matchup ) {

            $matchupIndex = $key + 1;

            $matchupName = 
                $league == 'NBA' ?  $conferenceLetter . '_R'. $round->order_index .'M' . $matchupIndex  : 'R'. $round->order_index .'M' . $matchupIndex;

            $newMatchup = Matchup::create([
                'name' => $matchupName,
                'round_id' => $round->id,
                'wins_team_1' => 0,
                'wins_team_2' => 0,
                'winner_team_id' => null,
                'matchup_index' => $matchupIndex,
            ]);
            
            if ( $newMatchup ) {
                $newMatchup->teams()->attach($matchup['team1']['id'], [
                    'seed' => $matchup['team1']['seed'],
                    'slot' => 1,
                ]);
                $newMatchup->teams()->attach($matchup['team2']['id'], [
                    'seed' => $matchup['team2']['seed'],
                    'slot' => 2
                ]); 
            }

        }

    }

    public function create_empty_matchups (string $league, string $conference, Round $round, int $count) {

        $conferenceLetter = $conference == 'east' ? 'E' : 'W';
         
        for ( $i = 0; $i < $count; $i++ ) {
            $matchupIndex = $i + 1;

            $matchupName = 
                $league == 'NBA' ?  $conferenceLetter . '_R'. $round->order_index .'M' . $matchupIndex  : 'R'. $round->order_index .'M' . $matchupIndex;
            
            $newMatchup = Matchup::create([
                'name' => $matchupName,
                'round_id' => $round->id,
                'wins_team_1' => 0,
                'wins_team_2' => 0,
                'winner_team_id' => null,
                'matchup_index' => $matchupIndex,
            ]);
        }
    }

    public function create_bracket (BracketChallenge $bracketChallenge, array $teams ) 
    {
        
        //create 
        if ( $bracketChallenge->league->abbr === 'NBA' ) {

            $nbaRounds = ['First Round', 'Semifinals', 'Conference Finals' ];

            //create rounds for each conference 
            foreach ( $teams as $key => $team ) {

                for ( $i = 0; $i < 3; $i++ ) {
                    $newRound = Round::create([
                        'name' => $nbaRounds[$i],
                        'conference' => strtoupper($key),
                        'order_index' => $i + 1,
                        'bracket_challenge_id' => $bracketChallenge->id,
                    ]);

                    //create initial matchups on round 1
                    if ( $newRound ) {
                        if ( $i == 0 ) {
                            $this->create_initial_matchups('NBA', $key, $newRound, $team);
                        }else {
                             $this->create_empty_matchups('NBA', $key, $newRound, $i == 1 ? 2 : 1 );
                        }
                    }
                }
            }

            //create finals round.
            $finalRound = Round::create([
                'name' => 'Finals',
                'bracket_challenge_id' => $bracketChallenge->id,
            ]);

            if ( $finalRound ) {
                $this->create_empty_matchups('NBA', $key, $finalRound, 1);
            }

        } else if ($bracketChallenge->league->abbr === 'PBA') {
            //todo for pba or non-nba leagues..
            
            $pbaRounds = ['QuarterFinals', 'Semifinals', 'Finals' ];

            //create pba rounds 
            for ( $i = 0; $i < 3; $i++ ) {
                $newRound = Round::create([
                    'name' => $pbaRounds[$i],
                    'bracket_challenge_id' => $bracketChallenge->id,
                    'order_index' => $i + 1,
                ]);

                if ( $newRound ) {
                    if ( $i == 0 ) {
                        $this->create_initial_matchups('PBA', '', $newRound, $teams);
                    }else {
                        $this->create_empty_matchups('PBA', '', $newRound, $i == 1 ? 2 : 1);
                    }
                }
            }

        }else {
            //todo...
        }
        
        
    }

    public function update_existing_matchups (int $roundId, array $toGenerate)
    {

        $generatedMatchups = $this->generate_matchups($toGenerate);

        foreach ( $generatedMatchups as $matchupKey => $matchup ) {
            //..
            $matchupIndex = $matchupKey + 1;

            $existingMatchup = Matchup::where('round_id', $roundId )
                ->where('matchup_index', $matchupIndex )
                ->first();

            if ( $existingMatchup ) {

                $existingMatchup->teams()->sync([]);
                $existingMatchup->teams()->attach($matchup['team1']['id'], [
                    'seed' => $matchup['team1']['seed'],
                    'slot' => 1,
                ]);
                $existingMatchup->teams()->attach($matchup['team2']['id'], [
                    'seed' => $matchup['team2']['seed'],
                    'slot' => 2
                ]); 
                
            }else {
                //throw an error..
                return response()->json([
                    'message' => 'Matchup not found for update.',
                ], 404);
            }

        }

    }

    public function update_bracket (BracketChallenge $bracketChallenge, array $teams ) 
    {
        
        //..
        if ( $bracketChallenge->league->abbr === 'NBA' ) {

            foreach ( $teams as $key => $team ) {

                $round = Round::where('bracket_challenge_id', $bracketChallenge->id)
                    ->where('conference', strtoupper($key))
                    ->where('order_index', 1)
                    ->first();

                if ( $round ) {
                    $this->update_existing_matchups ($round->id, $team);
                }else {
                    //throw an error..
                    return response()->json([
                        'message' => 'Round not found for update.',
                    ], 404);
                }
                      
            }
           
        }else if ( $bracketChallenge->league->abbr === 'PBA' ) {
            
            //for pba league..
            $round = Round::where('bracket_challenge_id', $bracketChallenge->id)
                    ->where('order_index', 1)
                    ->first();

            if ( $round ) {
                $this->update_existing_matchups ($round->id, $teams);
            }else {
                //throw an error..
                return response()->json([
                    'message' => 'Round not found for update.',
                ], 404);
            }
        }else {
            //todo..
        }
    }
    
}