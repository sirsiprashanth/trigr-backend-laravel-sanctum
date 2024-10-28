<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CoachesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $coaches = [];

        for ($i = 1; $i <= 20; $i++) {
            $coaches[] = [
                'name' => 'Coach ' . $i,
                'email' => 'coach' . $i . '@example.com',
                'password' => Hash::make('password'),
                'role' => 'coach',
                'experience_years' => rand(1, 20),
                'brief_bio' => 'This is a brief bio for Coach ' . $i,
                'clients_coached' => rand(10, 100),
                'rating' => rand(1, 5),
                'client_reviews' => 'Client reviews for Coach ' . $i,
                'photo' => 'https://via.placeholder.com/150',
                'additional_info' => 'Certifications, awards, achievements for Coach ' . $i,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('users')->insert($coaches);
    }
}
