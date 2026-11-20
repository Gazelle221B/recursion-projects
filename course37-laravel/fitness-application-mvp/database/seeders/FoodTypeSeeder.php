<?php

namespace Database\Seeders;

use App\Models\FoodType;
use Illuminate\Database\Seeder;

class FoodTypeSeeder extends Seeder
{
    /**
     * Food types keyed by their name (the primary key).
     */
    public function run(): void
    {
        $types = ['果物', '野菜', '肉類', '魚介類', '穀物', '乳製品', '飲料', 'その他'];

        foreach ($types as $name) {
            FoodType::updateOrCreate(['name' => $name]);
        }
    }
}
