<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Nutrition values (protein / carbs / fat) are the amounts
     * contained in "grams" grams of the food.
     */
    public function up(): void
    {
        Schema::create('foods', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->float('protein')->default(0);
            $table->float('carbs')->default(0);
            $table->float('fat')->default(0);
            $table->float('grams')->default(100);
            $table->string('food_type_name')->nullable();
            $table->foreign('food_type_name')->references('name')->on('food_types')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('foods');
    }
};
