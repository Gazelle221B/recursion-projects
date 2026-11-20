<?php

namespace Database\Seeders;

use App\Models\ExerciseType;
use Illuminate\Database\Seeder;

class ExerciseTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = ['有酸素運動', '筋力トレーニング', 'ヨガ・ストレッチ', '水泳', '球技'];

        foreach ($types as $name) {
            ExerciseType::updateOrCreate(['name' => $name]);
        }
    }
}
