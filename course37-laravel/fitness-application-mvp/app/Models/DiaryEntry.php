<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DiaryEntry extends Model
{
    protected $fillable = [
        'user_id',
        'date',
        'image_path',
    ];

    protected function casts(): array
    {
        return [
            // Stored as a UTC datetime in the database.
            'date' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function foodItems(): HasMany
    {
        return $this->hasMany(DiaryFoodItem::class);
    }

    public function recipeItems(): HasMany
    {
        return $this->hasMany(DiaryRecipeItem::class);
    }

    /**
     * Total calories of everything recorded in this diary entry:
     * food items (prorated by grams) plus the full calories of recipes.
     */
    public function totalCalories(): float
    {
        $total = 0.0;

        foreach ($this->foodItems as $item) {
            $total += $item->calories();
        }

        foreach ($this->recipeItems as $item) {
            $total += $item->recipe->totalCalories();
        }

        return $total;
    }
}
