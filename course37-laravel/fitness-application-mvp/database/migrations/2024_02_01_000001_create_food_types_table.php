<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * food_types uses its name as a string primary key
     * (as in the course's fitness-app schema).
     */
    public function up(): void
    {
        Schema::create('food_types', function (Blueprint $table) {
            $table->string('name')->primary();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('food_types');
    }
};
