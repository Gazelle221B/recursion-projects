<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Demo user for trying the application.
        User::updateOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
            ]
        );

        $this->call([
            FoodTypeSeeder::class,
            FoodTagSeeder::class,
            FoodSeeder::class,
            ExerciseTypeSeeder::class,
            ExerciseTagSeeder::class,
        ]);
    }
}
