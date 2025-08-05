<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\League; // Make sure to import your League model
use App\Models\Team;   // Make sure to import your Team model
use Illuminate\Support\Str;

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
                'fname' => "Brgy. Ginebra",
                'lname' => "San Miguel",
                'abbr' => "BGSM",
                'logo' => null,
            ],
            [
                'fname' => "Blackwater",
                'lname' => "Bossing",
                'abbr' => "BLW",
                'logo' => null,
            ],
            [
                'fname' => "Converge",
                'lname' => "FiberXers",
                'abbr' => "CNF",
                'logo' => null,
            ],
            [
                'fname' => 'Magnolia',
                'lname' => 'Hotshots',
                'abbr' => "MAG",
                'logo' => null,
            ],
            [
                'fname' => "Meralco",
                'lname' => "Bolts",
                'abbr' => "MER",
                'logo' => null,
            ],
            [
                'fname' => 'NLEX',
                'lname' => 'Road Warriors',
                'abbr' => "NLEX",
                'logo' => null,
            ],
            [
                'fname' => "NorthPort",
                'lname' => "Batang Pier",
                'abbr' => "NBP",
                'logo' => null,
            ],
            [
                'fname' => "Phoenix Super LPG",
                'lname' => "Fuel Masters",
                'abbr' => "PHX",
                'logo' => null,
            ],
            [
                'fname' => "Rain or Shine",
                'lname' => "Elasto Painters",
                'abbr' => "ROS",
                'logo' => null,
            ],
            [
                'fname' => "San Miguel",
                'lname' => "Beermen",
                'abbr' => "SMB",
                'logo' => null,
            ],
            [
                'fname' => "TNT",
                'lname' => "Tropang Giga",
                'abbr' => "TNT",
                'logo' => null,
            ],
            // Note: Bay Area Dragons was a guest team, typically not included in core league seeders
        ];

        $pbaLeague = League::where('abbr', 'PBA')->first();

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
                    'fname' => $team['fname'],
                    'lname' => $team['lname'],
                    'logo' => $team['logo'],
                    'slug' => Str::slug($team['fname'] . ' ' . $team['lname']),
                    'abbr' => $team['abbr'],
                ]
            );
        }

    }
}
