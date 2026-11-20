<?php

namespace Database\Seeders;

use App\Models\FoodTag;
use Illuminate\Database\Seeder;

class FoodTagSeeder extends Seeder
{
    public function run(): void
    {
        $tags = ['低カロリー', '高タンパク', '低糖質', 'ビタミン豊富', '食物繊維'];

        foreach ($tags as $name) {
            FoodTag::updateOrCreate(['name' => $name]);
        }
    }
}
