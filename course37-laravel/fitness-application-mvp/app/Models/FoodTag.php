<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class FoodTag extends Model
{
    protected $fillable = [
        'name',
    ];

    public function foods(): BelongsToMany
    {
        return $this->belongsToMany(Food::class)->withTimestamps();
    }
}
