<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\League; // Make sure to import your League model
use Illuminate\Support\Str;

class LeagueSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $leagues = [
            [
                "abbr" => "NBA",
                "name" => "National Basketball Association",
                "logo" => ""
            ],
            [
                "abbr" => "PBA",
                "name" => "Philippine Basketball Association",
                "logo" => ""
            ],
        ];

        foreach ($leagues as $league) {
            League::firstOrCreate(
                [
                    'name' => $league['name'],
                ],
                [
                    'abbr' => $league['abbr'],
                    'logo' => $league['logo'],
                    'slug' => Str::slug($league['name'])
                ]
            );
        }

    }
}
