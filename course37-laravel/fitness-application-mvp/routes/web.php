<?php

use App\Http\Controllers\Dashboard\DiaryEntryController;
use App\Http\Controllers\Dashboard\DiaryFoodItemController;
use App\Http\Controllers\Dashboard\DiaryRecipeItemController;
use App\Http\Controllers\Dashboard\ExerciseController;
use App\Http\Controllers\Dashboard\FoodController;
use App\Http\Controllers\Dashboard\RecipeController;
use App\Http\Controllers\Dashboard\WeightEntryController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

/*
|--------------------------------------------------------------------------
| Fitness Application (authenticated dashboard area)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified'])->prefix('dashboard')->name('dashboard.')->group(function () {
    // Foods (shared master data; edit/delete restricted by FoodPolicy)
    Route::resource('foods', FoodController::class);

    // Diary entries and their items
    Route::resource('diary-entries', DiaryEntryController::class)
        ->only(['index', 'create', 'store', 'show', 'destroy']);
    Route::post('diary-entries/{diaryEntry}/food-items', [DiaryFoodItemController::class, 'store'])
        ->name('diary-entries.food-items.store');
    Route::delete('diary-entries/{diaryEntry}/food-items/{diaryFoodItem}', [DiaryFoodItemController::class, 'destroy'])
        ->name('diary-entries.food-items.destroy');
    Route::post('diary-entries/{diaryEntry}/recipe-items', [DiaryRecipeItemController::class, 'store'])
        ->name('diary-entries.recipe-items.store');
    Route::delete('diary-entries/{diaryEntry}/recipe-items/{diaryRecipeItem}', [DiaryRecipeItemController::class, 'destroy'])
        ->name('diary-entries.recipe-items.destroy');

    // Private diary image: upload + owner-only delivery
    Route::put('diary-entries/{diaryEntry}/image', [DiaryEntryController::class, 'uploadImage'])
        ->name('diary-entries.image.update');
    Route::get('diary-entries/{diaryEntry}/image', [DiaryEntryController::class, 'showImage'])
        ->name('diary-entries.image.show');

    // Recipes (personal)
    Route::resource('recipes', RecipeController::class);

    // Exercise records
    Route::get('exercises', [ExerciseController::class, 'index'])->name('exercises.index');
    Route::post('exercises', [ExerciseController::class, 'store'])->name('exercises.store');
    Route::delete('exercises/{exercise}', [ExerciseController::class, 'destroy'])->name('exercises.destroy');

    // Weight entries
    Route::get('weight-entries', [WeightEntryController::class, 'index'])->name('weight.index');
    Route::post('weight-entries', [WeightEntryController::class, 'store'])->name('weight.store');
    Route::delete('weight-entries/{weightEntry}', [WeightEntryController::class, 'destroy'])->name('weight.destroy');
});

require __DIR__.'/auth.php';
