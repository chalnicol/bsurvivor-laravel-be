<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\BracketChallenge; // Import your User model
use Carbon\Carbon;
use App\Traits\BracketChallengeTrait;

class BracketChallengeSeeder extends Seeder
{
    use BracketChallengeTrait;
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $now = Carbon::now('UTC');
        $teams = [41, 40, 39, 36, 35, 31, 34, 32];

        $bracketChallenge =BracketChallenge::create([
            'name' => 'PBA 2025',
            'start_date' => $now->toDateString(),
            'end_date' => $now->addDay()->toDateString(),
            'is_public' => true,
            'description' => '',
            'slug' => 'pba-2025',
            'league_id' => 2,
            'bracket_data' => [
                'teams' => $teams,
            ]
        ]);

        if ( $bracketChallenge ) {
            $this->create_bracket($bracketChallenge, $teams);
        }

    }
}
