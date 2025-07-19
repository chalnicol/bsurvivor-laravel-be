<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\League; // Make sure to import your League model
use App\Models\Team;   // Make sure to import your Team model

class PbaTeamsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $pbaTeams = [
            [
                'clubname' => "Barangay Ginebra",
                'nickname' => "San Miguel",
                'abbr' => "BGY",
                'logo' => null,
            ],
            [
                'clubname' => "Blackwater",
                'nickname' => "Bossing",
                'abbr' => "BLW",
                'logo' => null,
            ],
            [
                'clubname' => "Converge",
                'nickname' => "FiberXers",
                'abbr' => "CNF",
                'logo' => null,
            ],
            [
                'clubname' => "Magnolia",
                'nickname' => "Hotshots",
                'abbr' => "MAG",
                'logo' => null,
            ],
            [
                'clubname' => "Meralco",
                'nickname' => "Bolts",
                'abbr' => "MER",
                'logo' => null,
            ],
            [
                'clubname' => "NLEX",
                'nickname' => "Road Warriors",
                'abbr' => "NLEX",
                'logo' => null,
            ],
            [
                'clubname' => "NorthPort",
                'nickname' => "Batang Pier",
                'abbr' => "NRP",
                'logo' => null,
            ],
            [
                'clubname' => "Phoenix Super LPG",
                'nickname' => "Fuel Masters",
                'abbr' => "PHX",
                'logo' => null,
            ],
            [
                'clubname' => "Rain or Shine",
                'nickname' => "Elasto Painters",
                'abbr' => "ROS",
                'logo' => null,
            ],
            [
                'clubname' => "San Miguel",
                'nickname' => "Beermen",
                'abbr' => "SMB",
                'logo' => null,
            ],
            [
                'clubname' => "TNT",
                'nickname' => "Tropang Giga",
                'abbr' => "TNT",
                'logo' => null,
            ],
            // Note: Bay Area Dragons was a guest team, typically not included in core league seeders
        ];

        $pbaLeague = League::where('name', 'PBA')->first();

        // IMPORTANT: If the league isn't found for some reason, handle it.
        // This indicates a seeding order issue or a typo.
        if (!$pbaLeague) {
            $this->command->error('Error: "PBA" league not found. Run LeagueSeeder first.');
            return;
        }

        foreach ($pbaTeams as $team) {
            Team::firstOrCreate(
                [
                    'abbr' => $team['abbr'],
                    'league_id' => $pbaLeague->id
                ],
                [
                    'clubname' => $team['clubname'],
                    'nickname' => $team['nickname'], // Using name as nickname for simplicity, adjust if you have actual nicknames
                    'logo' => $team['logo'],
                    //'conference' => $team['conference'], // Add conference if you have this column
                ]
            );
        }

    }
}
