<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\League; // Make sure to import your League model

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
                "name" => "NBA",
                "fullname" => "National Basketball Association",
                "logo" => ""
            ],
            [
                "name" => "PBA",
                "fullname" => "Philippine Basketball Association",
                "logo" => ""
            ],
        ];

        foreach ($leagues as $league) {
            League::firstOrCreate(
                [
                    'name' => $league['name'],
                ],
                [
                    'fullname' => $league['fullname'],
                    'logo' => $league['logo'],
                ]
            );
        }

    }
}
