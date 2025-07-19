<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\League; // Make sure to import your League model
use App\Models\Team;   // Make sure to import your Team model

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
                'clubname' => "Atlanta",
                'nickname' => "Hawks",
                'abbr' => "ATL",
                'logo' => "https://cdn.nba.com/logos/nba/1610612737/primary/L/logo.svg",
                'conference' => "EAST"
            ],
            [
                'clubname' => "Boston",
                'nickname' => "Celtics",
                'abbr' => "BOS",
                'logo' => "https://cdn.nba.com/logos/nba/1610612738/primary/L/logo.svg",
                'conference' => "EAST"
            ],
            [
                'clubname' => "Brooklyn",
                'nickname' => "Nets",
                'abbr' => "BKN",
                'logo' => "https://cdn.nba.com/logos/nba/1610612751/primary/L/logo.svg",
                'conference' => "EAST"
            ],
            [
                'clubname' => "Charlotte",
                'nickname' => "Hornets",
                'abbr' => "CHA",
                'logo' => "https://cdn.nba.com/logos/nba/1610612766/primary/L/logo.svg",
                'conference' => "EAST"
            ],
            [
                'clubname' => "Chicago",
                'nickname' => "Bulls",
                'abbr' => "CHI",
                'logo' => "https://cdn.nba.com/logos/nba/1610612741/primary/L/logo.svg",
                'conference' => "EAST"
            ],
            [
                'clubname' => "Cleveland",
                'nickname' => "Cavaliers",
                'abbr' => "CLE",
                'logo' => "https://cdn.nba.com/logos/nba/1610612739/primary/L/logo.svg",
                'conference' => "EAST"
            ],
            [
                'clubname' => "Detroit",
                'nickname' => "Pistons",
                'abbr' => "DET",
                'logo' => "https://cdn.nba.com/logos/nba/1610612765/primary/L/logo.svg",
                'conference' => "EAST"
            ],
            [
                'clubname' => "Indiana",
                'nickname' => "Pacers",
                'abbr' => "IND",
                'logo' => "https://cdn.nba.com/logos/nba/1610612754/primary/L/logo.svg",
                'conference' => "EAST"
            ],
            [
                'clubname' => "Miami",
                'nickname' => "Heat",
                'abbr' => "MIA",
                'logo' => "https://cdn.nba.com/logos/nba/1610612748/primary/L/logo.svg",
                'conference' => "EAST"
            ],
            [
                'clubname' => "Milwaukee",
                'nickname' => "Bucks",
                'abbr' => "MIL",
                'logo' => "https://cdn.nba.com/logos/nba/1610612749/primary/L/logo.svg",
                'conference' => "EAST"
            ],
            [
                'clubname' => "New York",
                'nickname' => "Knicks",
                'abbr' => "NYK",
                'logo' => "https://cdn.nba.com/logos/nba/1610612752/primary/L/logo.svg",
                'conference' => "EAST"
            ],
            [
                'clubname' => "Orlando",
                'nickname' => "Magic",
                'abbr' => "ORL",
                'logo' => "https://cdn.nba.com/logos/nba/1610612753/primary/L/logo.svg",
                'conference' => "EAST"
            ],
            [
                'clubname' => "Philadelphia",
                'nickname' => "76ers",
                'abbr' => "PHI",
                'logo' => "https://cdn.nba.com/logos/nba/1610612755/primary/L/logo.svg",
                'conference' => "EAST"
            ],
            [
                'clubname' => "Toronto",
                'nickname' => "Raptors",
                'abbr' => "TOR",
                'logo' => "https://cdn.nba.com/logos/nba/1610612761/primary/L/logo.svg",
                'conference' => "EAST"
            ],
            [
                'clubname' => "Washington",
                'nickname' => "Wizards",
                'abbr' => "WAS",
                'logo' => "https://cdn.nba.com/logos/nba/1610612764/primary/L/logo.svg",
                'conference' => "EAST"
            ],

            // Western Conference
            [
                'clubname' => "Dallas",
                'nickname' => "Mavericks",
                'abbr' => "DAL",
                'logo' => "https://cdn.nba.com/logos/nba/1610612742/primary/L/logo.svg",
                'conference' => "WEST"
            ],
            [
                'clubname' => "Denver",
                'nickname' => "Nuggets",
                'abbr' => "DEN",
                'logo' => "https://cdn.nba.com/logos/nba/1610612743/primary/L/logo.svg",
                'conference' => "WEST"
            ],
            [
                'clubname' => "Golden State",
                'nickname' => "Warriors",
                'abbr' => "GSW",
                'logo' => "https://cdn.nba.com/logos/nba/1610612744/primary/L/logo.svg",
                'conference' => "WEST"
            ],
            [
                'clubname' => "Houston",
                'nickname' => "Rockets",
                'abbr' => "HOU",
                'logo' => "https://cdn.nba.com/logos/nba/1610612745/primary/L/logo.svg",
                'conference' => "WEST"
            ],
            [
                'clubname' => "Los Angeles",
                'nickname' => "Clippers",
                'abbr' => "LAC",
                'logo' => "https://cdn.nba.com/logos/nba/1610612746/primary/L/logo.svg",
                'conference' => "WEST"
            ],
            [
                'clubname' => "Los Angeles",
                'nickname' => "Lakers",
                'abbr' => "LAL",
                'logo' => "https://cdn.nba.com/logos/nba/1610612747/primary/L/logo.svg",
                'conference' => "WEST"
            ],
            [
                'clubname' => "Memphis",
                'nickname' => "Grizzlies",
                'abbr' => "MEM",
                'logo' => "https://cdn.nba.com/logos/nba/1610612763/primary/L/logo.svg",
                'conference' => "WEST"
            ],
            [
                'clubname' => "Minnesota",
                'nickname' => "Timberwolves",
                'abbr' => "MIN",
                'logo' => "https://cdn.nba.com/logos/nba/1610612750/primary/L/logo.svg",
                'conference' => "WEST"
            ],
            [
                'clubname' => "New Orleans",
                'nickname' => "Pelicans",
                'abbr' => "NOP",
                'logo' => "https://cdn.nba.com/logos/nba/1610612740/primary/L/logo.svg",
                'conference' => "WEST"
            ],
            [
                'clubname' => "Oklahoma City",
                'nickname' => "Thunder",
                'abbr' => "OKC",
                'logo' => "https://cdn.nba.com/logos/nba/1610612760/primary/L/logo.svg",
                'conference' => "WEST"
            ],
            [
                'clubname' => "Phoenix",
                'nickname' => "Suns",
                'abbr' => "PHX",
                'logo' => "https://cdn.nba.com/logos/nba/1610612756/primary/L/logo.svg",
                'conference' => "WEST"
            ],
            [
                'clubname' => "Portland",
                'nickname' => "Trail Blazers",
                'abbr' => "POR",
                'logo' => "https://cdn.nba.com/logos/nba/1610612757/primary/L/logo.svg",
                'conference' => "WEST"
            ],
            [
                'clubname' => "Sacramento",
                'nickname' => "Kings",
                'abbr' => "SAC",
                'logo' => "https://cdn.nba.com/logos/nba/1610612758/primary/L/logo.svg",
                'conference' => "WEST"
            ],
            [
                'clubname' => "San Antonio",
                'nickname' => "Spurs",
                'abbr' => "SAS",
                'logo' => "https://cdn.nba.com/logos/nba/1610612759/primary/L/logo.svg",
                'conference' => "WEST"
            ],
            [
                'clubname' => "Utah",
                'nickname' => "Jazz",
                'abbr' => "UTA",
                'logo' => "https://cdn.nba.com/logos/nba/1610612762/primary/L/logo.svg",
                'conference' => "WEST"
            ],
        ];

        $nbaLeague = League::where('name', 'NBA')->first();

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
                    'clubname' => $team['clubname'],
                    'nickname' => $team['nickname'], // Using name as nickname for simplicity, adjust if you have actual nicknames
                    'logo' => $team['logo'],
                    'conference' => $team['conference'], // Add conference if you have this column
                ]
            );
        }

    }
}
