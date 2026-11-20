<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FoodType extends Model
{
    /**
     * The primary key is the type name itself (a string).
     */
    protected $primaryKey = 'name';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'name',
    ];

    public function foods(): HasMany
    {
        return $this->hasMany(Food::class, 'food_type_name', 'name');
    }
}
