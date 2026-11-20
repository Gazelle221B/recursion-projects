<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Pivot table for the Exercise <-> ExerciseTag many-to-many relation,
     * mirroring the Food / FoodTag design.
     */
    public function up(): void
    {
        Schema::create('exercise_exercise_tag', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exercise_id')->constrained('exercises')->cascadeOnDelete();
            $table->foreignId('exercise_tag_id')->constrained('exercise_tags')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['exercise_id', 'exercise_tag_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exercise_exercise_tag');
    }
};
