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
                'name' => "Atlanta Hawks",
                'abbr' => "ATL",
                'logo' => "https://cdn.nba.com/logos/nba/1610612737/primary/L/logo.svg",
                'conference' => "EAST"
            ],
            [
                'name' => "Boston Celtics",
                'abbr' => "BOS",
                'logo' => "https://cdn.nba.com/logos/nba/1610612738/primary/L/logo.svg",
                'conference' => "EAST"
            ],
            [
                'name' => "Brooklyn Nets",
                'abbr' => "BKN",
                'logo' => "https://cdn.nba.com/logos/nba/1610612751/primary/L/logo.svg",
                'conference' => "EAST"
            ],
            [
                'name' => "Charlotte Hornets",
                'abbr' => "CHA",
                'logo' => "https://cdn.nba.com/logos/nba/1610612766/primary/L/logo.svg",
                'conference' => "EAST"
            ],
            [
                'name' => "Chicago Bulls",
                'abbr' => "CHI",
                'logo' => "https://cdn.nba.com/logos/nba/1610612741/primary/L/logo.svg",
                'conference' => "EAST"
            ],
            [
                'name' => "Cleveland Cavaliers",  
                'abbr' => "CLE",
                'logo' => "https://cdn.nba.com/logos/nba/1610612739/primary/L/logo.svg",
                'conference' => "EAST"
            ],
            [
                'name' => "Detroit Pistons",
                'abbr' => "DET",
                'logo' => "https://cdn.nba.com/logos/nba/1610612765/primary/L/logo.svg",
                'conference' => "EAST"
            ],
            [
                'name' => "Indiana Pacers",
                'abbr' => "IND",
                'logo' => "https://cdn.nba.com/logos/nba/1610612754/primary/L/logo.svg",
                'conference' => "EAST"
            ],
            [
                'name' => "Miami Heat",
                'abbr' => "MIA",
                'logo' => "https://cdn.nba.com/logos/nba/1610612748/primary/L/logo.svg",
                'conference' => "EAST"
            ],
            [
                'name' => "Milwaukee Bucks",
                'abbr' => "MIL",
                'logo' => "https://cdn.nba.com/logos/nba/1610612749/primary/L/logo.svg",
                'conference' => "EAST"
            ],
            [
                'name' => "New York Knicks",
                'abbr' => "NYK",
                'logo' => "https://cdn.nba.com/logos/nba/1610612752/primary/L/logo.svg",
                'conference' => "EAST"
            ],
            [
                'name' => "Orlando Magic",
                'abbr' => "ORL",
                'logo' => "https://cdn.nba.com/logos/nba/1610612753/primary/L/logo.svg",
                'conference' => "EAST"
            ],
            [
                'name' => "Philadelphia 76ers",
                'abbr' => "PHI",
                'logo' => "https://cdn.nba.com/logos/nba/1610612755/primary/L/logo.svg",
                'conference' => "EAST"
            ],
            [
                'name' => "Toronto Raptors",
                'abbr' => "TOR",
                'logo' => "https://cdn.nba.com/logos/nba/1610612761/primary/L/logo.svg",
                'conference' => "EAST"
            ],
            [
                'name' => "Washington Wizards",
                'abbr' => "WAS",
                'logo' => "https://cdn.nba.com/logos/nba/1610612764/primary/L/logo.svg",
                'conference' => "EAST"
            ],

            // Western Conference
            [
                'name' => "Dallas Mavericks",
                'abbr' => "DAL",
                'logo' => "https://cdn.nba.com/logos/nba/1610612742/primary/L/logo.svg",
                'conference' => "WEST"
            ],
            [
                'name' => "Denver Nuggets",
                'abbr' => "DEN",
                'logo' => "https://cdn.nba.com/logos/nba/1610612743/primary/L/logo.svg",
                'conference' => "WEST"
            ],
            [
                'name' => "Golden State Warriors",
                'abbr' => "GSW",
                'logo' => "https://cdn.nba.com/logos/nba/1610612744/primary/L/logo.svg",
                'conference' => "WEST"
            ],
            [
                'name' => "Houston Rockets",
                'abbr' => "HOU",
                'logo' => "https://cdn.nba.com/logos/nba/1610612745/primary/L/logo.svg",
                'conference' => "WEST"
            ],
            [
                'name' => "Los Angeles Clippers",
                'abbr' => "LAC",
                'logo' => "https://cdn.nba.com/logos/nba/1610612746/primary/L/logo.svg",
                'conference' => "WEST"
            ],
            [
                'name' => "Los Angeles Lakers",
                'abbr' => "LAL",
                'logo' => "https://cdn.nba.com/logos/nba/1610612747/primary/L/logo.svg",
                'conference' => "WEST"
            ],
            [
                'name' => "Memphis Grizzlies",
                'abbr' => "MEM",
                'logo' => "https://cdn.nba.com/logos/nba/1610612763/primary/L/logo.svg",
                'conference' => "WEST"
            ],
            [
                'name' => "Minnesota Timberwolves",
                'abbr' => "MIN",
                'logo' => "https://cdn.nba.com/logos/nba/1610612750/primary/L/logo.svg",
                'conference' => "WEST"
            ],
            [
                'name' => "New Orleans Pelicans",
                'abbr' => "NOP",
                'logo' => "https://cdn.nba.com/logos/nba/1610612740/primary/L/logo.svg",
                'conference' => "WEST"
            ],
            [
                'name' => "Oklahoma City Thunder",
                'abbr' => "OKC",
                'logo' => "https://cdn.nba.com/logos/nba/1610612760/primary/L/logo.svg",
                'conference' => "WEST"
            ],
            [
                'name' => "Phoenix Suns",
                'abbr' => "PHX",
                'logo' => "https://cdn.nba.com/logos/nba/1610612756/primary/L/logo.svg",
                'conference' => "WEST"
            ],
            [
                'name' => "Portland Trail Blazers",
                'abbr' => "POR",
                'logo' => "https://cdn.nba.com/logos/nba/1610612757/primary/L/logo.svg",
                'conference' => "WEST"
            ],
            [
                'name' => "Sacramento Kings",
                'abbr' => "SAC",
                'logo' => "https://cdn.nba.com/logos/nba/1610612758/primary/L/logo.svg",
                'conference' => "WEST"
            ],
            [
                'name' => "San Antonio Spurs",
                'abbr' => "SAS",
                'logo' => "https://cdn.nba.com/logos/nba/1610612759/primary/L/logo.svg",
                'conference' => "WEST"
            ],
            [
                'name' => "Utah Jazz",
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
                    'name' => $team['name'],
                    'slug' => Str::slug($team['name']),
                    'logo' => $team['logo'],
                    'conference' => $team['conference'], 
                ]
            );
        }

    }
}
