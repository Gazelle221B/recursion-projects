<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Records that the foods of a recipe were consumed as part of a
     * diary entry.
     */
    public function up(): void
    {
        Schema::create('diary_recipe_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('diary_entry_id')->constrained('diary_entries')->cascadeOnDelete();
            $table->foreignId('recipe_id')->constrained('recipes')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('diary_recipe_items');
    }
};
