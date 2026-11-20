<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiaryFoodItem extends Model
{
    protected $fillable = [
        'diary_entry_id',
        'food_id',
        'grams',
    ];

    protected function casts(): array
    {
        return [
            'grams' => 'float',
        ];
    }

    public function diaryEntry(): BelongsTo
    {
        return $this->belongsTo(DiaryEntry::class);
    }

    public function food(): BelongsTo
    {
        return $this->belongsTo(Food::class);
    }

    /**
     * Calories of this item, prorated by the recorded grams.
     */
    public function calories(): float
    {
        return $this->food->caloriesForGrams($this->grams);
    }
}
