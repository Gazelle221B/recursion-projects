<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WeightEntry extends Model
{
    protected $fillable = [
        'user_id',
        'weight',
        'recorded_on',
    ];

    protected function casts(): array
    {
        return [
            'weight' => 'float',
            'recorded_on' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
