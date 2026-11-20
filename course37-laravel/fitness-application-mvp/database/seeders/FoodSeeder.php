<?php

namespace Database\Seeders;

use App\Models\Food;
use App\Models\FoodTag;
use Illuminate\Database\Seeder;

class FoodSeeder extends Seeder
{
    /**
     * Sample foods. Nutrition values are per "grams" grams of the food.
     * Foods are seeded by the system (user_id = null) and identified
     * by their unique name so re-seeding does not duplicate them.
     */
    public function run(): void
    {
        $foods = [
            ['name' => 'りんご', 'protein' => 0.3, 'carbs' => 14.0, 'fat' => 0.2, 'grams' => 100, 'food_type_name' => '果物', 'tags' => ['低カロリー', '食物繊維']],
            ['name' => 'バナナ', 'protein' => 1.1, 'carbs' => 22.5, 'fat' => 0.2, 'grams' => 100, 'food_type_name' => '果物', 'tags' => ['ビタミン豊富']],
            ['name' => '鶏むね肉', 'protein' => 24.4, 'carbs' => 0.0, 'fat' => 1.9, 'grams' => 100, 'food_type_name' => '肉類', 'tags' => ['高タンパク', '低糖質']],
            ['name' => '鮭', 'protein' => 22.3, 'carbs' => 0.1, 'fat' => 4.1, 'grams' => 100, 'food_type_name' => '魚介類', 'tags' => ['高タンパク', '低糖質']],
            ['name' => '白米（炊飯）', 'protein' => 2.5, 'carbs' => 37.1, 'fat' => 0.3, 'grams' => 150, 'food_type_name' => '穀物', 'tags' => []],
            ['name' => 'ブロッコリー', 'protein' => 4.3, 'carbs' => 5.2, 'fat' => 0.5, 'grams' => 100, 'food_type_name' => '野菜', 'tags' => ['低カロリー', 'ビタミン豊富', '食物繊維']],
            ['name' => '牛乳', 'protein' => 3.3, 'carbs' => 4.8, 'fat' => 3.8, 'grams' => 200, 'food_type_name' => '乳製品', 'tags' => []],
            ['name' => '卵', 'protein' => 6.2, 'carbs' => 0.2, 'fat' => 5.2, 'grams' => 50, 'food_type_name' => 'その他', 'tags' => ['高タンパク', '低糖質']],
        ];

        foreach ($foods as $data) {
            $tagNames = $data['tags'];
            unset($data['tags']);

            $food = Food::updateOrCreate(['name' => $data['name']], $data);

            $tagIds = FoodTag::whereIn('name', $tagNames)->pluck('id');
            $food->tags()->sync($tagIds);
        }
    }
}
