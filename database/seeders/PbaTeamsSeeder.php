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
                'name' => "Brgy. Ginebra San Miguel",
                'abbr' => "BGSM",
                'logo' => null,
            ],
            [
                'name' => "Blackwater Bossing",
                'abbr' => "BLW",
                'logo' => null,
            ],
            [
                'name' => "Converge FiberXers",
                'abbr' => "CNF",
                'logo' => null,
            ],
            [
                'name' => 'Magnolia Hotshots',
                'abbr' => "MAG",
                'logo' => null,
            ],
            [
                'name' => "Meralco Bolts",
                'abbr' => "MER",
                'logo' => null,
            ],
            [
                'name' => 'NLEX Road Warriors',
                'abbr' => "NLEX",
                'logo' => null,
            ],
            [
                'name' => "NorthPort Batang Pier",
                'abbr' => "NRP",
                'logo' => null,
            ],
            [
                'name' => "Phoenix Super LPG Fuel Masters",
                'abbr' => "PHX",
                'logo' => null,
            ],
            [
                'name' => "Rain or Shine Elasto Painters",
                'abbr' => "ROS",
                'logo' => null,
            ],
            [
                'name' => "San Miguel Beermen",
                'abbr' => "SMB",
                'logo' => null,
            ],
            [
                'name' => "TNT Tropang Giga",
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
                    'name' => $team['name'],
                    'logo' => $team['logo'],
                    'slug' => Str::slug($team['name'])
                ]
            );
        }

    }
}
