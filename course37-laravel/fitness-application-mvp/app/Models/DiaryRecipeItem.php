<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiaryRecipeItem extends Model
{
    protected $fillable = [
        'diary_entry_id',
        'recipe_id',
    ];

    public function diaryEntry(): BelongsTo
    {
        return $this->belongsTo(DiaryEntry::class);
    }

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }
}
