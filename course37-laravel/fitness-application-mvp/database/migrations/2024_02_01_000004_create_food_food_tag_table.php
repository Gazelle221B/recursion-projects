<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Pivot table for the Food <-> FoodTag many-to-many relation.
     * Laravel's convention names it by joining the two model names
     * in alphabetical order, singular: food_food_tag.
     */
    public function up(): void
    {
        Schema::create('food_food_tag', function (Blueprint $table) {
            $table->id();
            $table->foreignId('food_id')->constrained('foods')->cascadeOnDelete();
            $table->foreignId('food_tag_id')->constrained('food_tags')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['food_id', 'food_tag_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('food_food_tag');
    }
};
