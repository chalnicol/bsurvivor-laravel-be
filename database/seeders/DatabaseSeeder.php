<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();
        // Users
        $this->call(UserSeeder::class);
        // Leagues
        $this->call(LeagueSeeder::class);
        //nba teams..
        $this->call(NbaTeamsSeeder::class);
        //pba teams..
        $this->call(PbaTeamsSeeder::class);
        //roles and permissions..
        $this->call(RolesAndPermissionsSeeder::class);

    }
}
