<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User; // Import your User model
use Illuminate\Support\Facades\Hash; // For hashing the password

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $users = [
           
            [
                "username" => "adminUser",
                "email" => "admin@example.com",
                "password" => Hash::make('admin123'), // Use a secure password
                "email_verified_at" => now(), // Set email as verified
            ],
             [
                "username" => "chalnicol",
                "email" => "charlou@example.com",
                "password" => Hash::make('asdfasdf'), // Use a secure password
                "email_verified_at" => now(), // Set email as verified
            ],
            
        ];

        foreach ($users as $user) {
            User::firstOrCreate(
                [
                    'username' => $user['username'],
                    'email' => $user['email'],
                ],
                [
                    'password' => $user['password'],
                    'email_verified_at' => $user['email_verified_at'], // Set email verification timestamp
                ]
            );
        }

    }
}
