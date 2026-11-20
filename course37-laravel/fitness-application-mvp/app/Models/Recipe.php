<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Recipe extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'creation_date',
    ];

    protected function casts(): array
    {
        return [
            'creation_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The foods this recipe is made of (via the recipe_food pivot).
     */
    public function foods(): BelongsToMany
    {
        return $this->belongsToMany(Food::class, 'recipe_food')->withTimestamps();
    }

    /**
     * Total calories of the recipe: the sum of each ingredient's
     * calories at its standard amount.
     */
    public function totalCalories(): float
    {
        return $this->foods->sum(fn (Food $food) => $food->getTotalCalories());
    }
}
