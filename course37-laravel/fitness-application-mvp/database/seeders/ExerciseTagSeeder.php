<?php

namespace Database\Seeders;

use App\Models\ExerciseTag;
use Illuminate\Database\Seeder;

class ExerciseTagSeeder extends Seeder
{
    public function run(): void
    {
        $tags = ['高強度', '低負荷', '屋内', '屋外'];

        foreach ($tags as $name) {
            ExerciseTag::updateOrCreate(['name' => $name]);
        }
    }
}
