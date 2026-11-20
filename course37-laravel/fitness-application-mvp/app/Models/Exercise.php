<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Exercise extends Model
{
    protected $fillable = [
        'user_id',
        'exercise_type_id',
        'duration_minutes',
        'performed_at',
    ];

    protected function casts(): array
    {
        return [
            'duration_minutes' => 'integer',
            'performed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function exerciseType(): BelongsTo
    {
        return $this->belongsTo(ExerciseType::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(ExerciseTag::class)->withTimestamps();
    }
}
