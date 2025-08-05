<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\League; // Make sure to import your League model
use App\Models\Team;   // Make sure to import your Team model
use Illuminate\Support\Str;

class NbaTeamsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $nbaTeams = [
            // Eastern Conference
            [
                'fname' => "Atlanta",
                'lname' => "Hawks",
                'abbr' => "ATL",
                'logo' => "https://cdn.nba.com/logos/nba/1610612737/primary/L/logo.svg",
                'conference' => "EAST"
            ],
            [
                'fname' => "Boston",
                'lname' => "Celtics",
                'abbr' => "BOS",
                'logo' => "https://cdn.nba.com/logos/nba/1610612738/primary/L/logo.svg",
                'conference' => "EAST"
            ],
            [
                'fname' => "Brooklyn",
                'lname' => "Nets",
                'abbr' => "BKN",
                'logo' => "https://cdn.nba.com/logos/nba/1610612751/primary/L/logo.svg",
                'conference' => "EAST"
            ],
            [
                'fname' => "Charlotte",
                'lname' => "Hornets",
                'abbr' => "CHA",
                'logo' => "https://cdn.nba.com/logos/nba/1610612766/primary/L/logo.svg",
                'conference' => "EAST"
            ],
            [
                'fname' => "Chicago",
                'lname' => "Bulls",
                'abbr' => "CHI",
                'logo' => "https://cdn.nba.com/logos/nba/1610612741/primary/L/logo.svg",
                'conference' => "EAST"
            ],
            [
                'fname' => "Cleveland",
                'lname' => "Cavaliers",
                'abbr' => "CLE",
                'logo' => "https://cdn.nba.com/logos/nba/1610612739/primary/L/logo.svg",
                'conference' => "EAST"
            ],
            [
                'fname' => "Detroit",
                'lname' => "Pistons",
                'abbr' => "DET",
                'logo' => "https://cdn.nba.com/logos/nba/1610612765/primary/L/logo.svg",
                'conference' => "EAST"
            ],
            [
                'fname' => "Indiana",
                'lname' => "Pacers",
                'abbr' => "IND",
                'logo' => "https://cdn.nba.com/logos/nba/1610612754/primary/L/logo.svg",
                'conference' => "EAST"
            ],
            [
                'fname' => "Miami",
                'lname' => "Heat",
                'abbr' => "MIA",
                'logo' => "https://cdn.nba.com/logos/nba/1610612748/primary/L/logo.svg",
                'conference' => "EAST"
            ],
            [
                'fname' => "Milwaukee",
                'lname' => "Bucks",
                'abbr' => "MIL",
                'logo' => "https://cdn.nba.com/logos/nba/1610612749/primary/L/logo.svg",
                'conference' => "EAST"
            ],
            [
                'fname' => "New York",
                'lname' => "Knicks",
                'abbr' => "NYK",
                'logo' => "https://cdn.nba.com/logos/nba/1610612752/primary/L/logo.svg",
                'conference' => "EAST"
            ],
            [
                'fname' => "Orlando",
                'lname' => "Magic",
                'abbr' => "ORL",
                'logo' => "https://cdn.nba.com/logos/nba/1610612753/primary/L/logo.svg",
                'conference' => "EAST"
            ],
            [
                'fname' => "Philadelphia",
                'lname' => "76ers",
                'abbr' => "PHI",
                'logo' => "https://cdn.nba.com/logos/nba/1610612755/primary/L/logo.svg",
                'conference' => "EAST"
            ],
            [
                'fname' => "Toronto",
                'lname' => "Raptors",
                'abbr' => "TOR",
                'logo' => "https://cdn.nba.com/logos/nba/1610612761/primary/L/logo.svg",
                'conference' => "EAST"
            ],
            [
                'fname' => "Washington",
                'lname' => "Wizards",
                'abbr' => "WAS",
                'logo' => "https://cdn.nba.com/logos/nba/1610612764/primary/L/logo.svg",
                'conference' => "EAST"
            ],

            // Western Conference
            [
                'fname' => "Dallas",
                'lname' => "Mavericks",
                'abbr' => "DAL",
                'logo' => "https://cdn.nba.com/logos/nba/1610612742/primary/L/logo.svg",
                'conference' => "WEST"
            ],
            [
                'fname' => "Denver",
                'lname' => "Nuggets",
                'abbr' => "DEN",
                'logo' => "https://cdn.nba.com/logos/nba/1610612743/primary/L/logo.svg",
                'conference' => "WEST"
            ],
            [
                'fname' => "Golden State",
                'lname' => "Warriors",
                'abbr' => "GSW",
                'logo' => "https://cdn.nba.com/logos/nba/1610612744/primary/L/logo.svg",
                'conference' => "WEST"
            ],
            [
                'fname' => "Houston",
                'lname' => "Rockets",
                'abbr' => "HOU",
                'logo' => "https://cdn.nba.com/logos/nba/1610612745/primary/L/logo.svg",
                'conference' => "WEST"
            ],
            [
                'fname' => "Los Angeles",
                'lname' => "Clippers",
                'abbr' => "LAC",
                'logo' => "https://cdn.nba.com/logos/nba/1610612746/primary/L/logo.svg",
                'conference' => "WEST"
            ],
            [
                'fname' => "Los Angeles",
                'lname' => "Lakers",
                'abbr' => "LAL",
                'logo' => "https://cdn.nba.com/logos/nba/1610612747/primary/L/logo.svg",
                'conference' => "WEST"
            ],
            [
                'fname' => "Memphis",
                'lname' => "Grizzlies",
                'abbr' => "MEM",
                'logo' => "https://cdn.nba.com/logos/nba/1610612763/primary/L/logo.svg",
                'conference' => "WEST"
            ],
            [
                'fname' => "Minnesota",
                'lname' => "Timberwolves",
                'abbr' => "MIN",
                'logo' => "https://cdn.nba.com/logos/nba/1610612750/primary/L/logo.svg",
                'conference' => "WEST"
            ],
            [
                'fname' => "New Orleans",
                'lname' => "Pelicans",
                'abbr' => "NOP",
                'logo' => "https://cdn.nba.com/logos/nba/1610612740/primary/L/logo.svg",
                'conference' => "WEST"
            ],
            [
                'fname' => "Oklahoma City",
                'lname' => "Thunder",
                'abbr' => "OKC",
                'logo' => "https://cdn.nba.com/logos/nba/1610612760/primary/L/logo.svg",
                'conference' => "WEST"
            ],
            [
                'fname' => "Phoenix",
                'lname' => "Suns",
                'abbr' => "PHX",
                'logo' => "https://cdn.nba.com/logos/nba/1610612756/primary/L/logo.svg",
                'conference' => "WEST"
            ],
            [
                'fname' => "Portland",
                'lname' => "Trail Blazers",
                'abbr' => "POR",
                'logo' => "https://cdn.nba.com/logos/nba/1610612757/primary/L/logo.svg",
                'conference' => "WEST"
            ],
            [
                'fname' => "Sacramento",
                'lname' => "Kings",
                'abbr' => "SAC",
                'logo' => "https://cdn.nba.com/logos/nba/1610612758/primary/L/logo.svg",
                'conference' => "WEST"
            ],
            [
                'fname' => "San Antonio",
                'lname' => "Spurs",
                'abbr' => "SAS",
                'logo' => "https://cdn.nba.com/logos/nba/1610612759/primary/L/logo.svg",
                'conference' => "WEST"
            ],
            [
                'fname' => "Utah",
                'lname' => "Jazz",
                'abbr' => "UTA",
                'logo' => "https://cdn.nba.com/logos/nba/1610612762/primary/L/logo.svg",
                'conference' => "WEST"
            ],
        ];

        $nbaLeague = League::where('abbr', 'NBA')->first();

        // IMPORTANT: If the league isn't found for some reason, handle it.
        // This indicates a seeding order issue or a typo.
        if (!$nbaLeague) {
            $this->command->error('Error: "NBA" league not found. Run LeagueSeeder first.');
            return;
        }

        foreach ($nbaTeams as $team) {
            Team::firstOrCreate(
                [
                    'abbr' => $team['abbr'],
                    'league_id' => $nbaLeague->id
                ],
                [
                    'fname' => $team['fname'],
                    'lname' => $team['lname'],
                    'abbr' => $team['abbr'],
                    'slug' => Str::slug($team['fname']. ' ' . $team['lname']),
                    'lname' => $team['lname'],
                    'abbr' => $team['abbr'],
                    'logo' => $team['logo'],
                    'conference' => $team['conference'], 
                ]
            );
        }

    }
}
