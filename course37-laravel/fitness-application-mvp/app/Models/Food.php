<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Food extends Model
{
    /**
     * The table name does not follow from the class name
     * ("food" would be uncountable), so it is set explicitly.
     */
    protected $table = 'foods';

    protected $fillable = [
        'name',
        'protein',
        'carbs',
        'fat',
        'grams',
        'food_type_name',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'protein' => 'float',
            'carbs' => 'float',
            'fat' => 'float',
            'grams' => 'float',
        ];
    }

    public function foodType(): BelongsTo
    {
        return $this->belongsTo(FoodType::class, 'food_type_name', 'name');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(FoodTag::class)->withTimestamps();
    }

    /**
     * The user who created this food (null when created by the system).
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Total calories of this food at its standard amount (grams).
     * protein x 4 + carbs x 4 + fat x 9.
     */
    public function getTotalCalories(): float
    {
        return $this->protein * 4 + $this->carbs * 4 + $this->fat * 9;
    }

    /**
     * Calories when an arbitrary amount (in grams) of this food is eaten,
     * prorated from the standard amount.
     */
    public function caloriesForGrams(float $grams): float
    {
        if ($this->grams <= 0) {
            return 0.0;
        }

        return $this->getTotalCalories() * $grams / $this->grams;
    }
}
