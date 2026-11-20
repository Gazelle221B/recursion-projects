<?php

namespace Tests\Feature;

use App\Models\ExerciseTag;
use App\Models\ExerciseType;
use App\Models\Food;
use App\Models\FoodType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FitnessFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
        $this->user = User::where('email', 'test@example.com')->firstOrFail();
    }

    public function test_demo_user_can_run_the_full_fitness_flow(): void
    {
        // Food creation (with a food type and tag attached by the seeder)
        $this->actingAs($this->user)
            ->post(route('dashboard.foods.store'), [
                'name' => 'テスト食品',
                'protein' => 10,
                'carbs' => 20,
                'fat' => 5,
                'grams' => 100,
                'food_type_name' => '果物',
            ])
            ->assertRedirect();

        $food = Food::where('name', 'テスト食品')->firstOrFail();
        // 10*4 + 20*4 + 5*9 = 165 kcal per 100 g
        $this->assertEqualsWithDelta(165.0, $food->getTotalCalories(), 0.001);
        $this->assertSame($this->user->id, $food->user_id);

        // Diary entry creation
        $this->actingAs($this->user)
            ->post(route('dashboard.diary-entries.store'), ['date' => '2026-11-20'])
            ->assertRedirect();

        $entry = $this->user->diaryEntries()->firstOrFail();

        // Add a food item (200 g -> 330 kcal)
        $this->actingAs($this->user)
            ->post(route('dashboard.diary-entries.food-items.store', $entry), [
                'food_id' => $food->id,
                'grams' => 200,
            ])
            ->assertRedirect();

        $this->assertEqualsWithDelta(330.0, $entry->fresh()->totalCalories(), 0.001);

        // Recipe creation from seeded foods and adding it to the diary
        $apple = Food::where('name', 'りんご')->firstOrFail();
        $banana = Food::where('name', 'バナナ')->firstOrFail();

        $this->actingAs($this->user)
            ->post(route('dashboard.recipes.store'), [
                'name' => 'フルーツ盛り合わせ',
                'creation_date' => '2026-11-20',
                'food_ids' => [$apple->id, $banana->id],
            ])
            ->assertRedirect();

        $recipe = $this->user->recipes()->firstOrFail();
        $expectedRecipeCalories = $apple->getTotalCalories() + $banana->getTotalCalories();
        $this->assertEqualsWithDelta($expectedRecipeCalories, $recipe->totalCalories(), 0.001);

        $this->actingAs($this->user)
            ->post(route('dashboard.diary-entries.recipe-items.store', $entry), [
                'recipe_id' => $recipe->id,
            ])
            ->assertRedirect();

        $this->assertEqualsWithDelta(
            330.0 + $expectedRecipeCalories,
            $entry->fresh()->totalCalories(),
            0.001
        );

        // Exercise record with type and tags
        $type = ExerciseType::where('name', '有酸素運動')->firstOrFail();
        $tag = ExerciseTag::where('name', '高強度')->firstOrFail();

        $this->actingAs($this->user)
            ->post(route('dashboard.exercises.store'), [
                'exercise_type_id' => $type->id,
                'duration_minutes' => 45,
                'performed_at' => '2026-11-20T07:30',
                'tags' => [$tag->id],
            ])
            ->assertRedirect();

        $exercise = $this->user->exercises()->firstOrFail();
        $this->assertTrue($exercise->tags->contains($tag));

        // Weight entry
        $this->actingAs($this->user)
            ->post(route('dashboard.weight.store'), [
                'weight' => 65.5,
                'recorded_on' => '2026-11-20',
            ])
            ->assertRedirect();

        $this->assertSame(1, $this->user->weightEntries()->count());

        // The main pages render for the signed-in user
        $this->actingAs($this->user)->get(route('dashboard.foods.index'))->assertOk();
        $this->actingAs($this->user)->get(route('dashboard.diary-entries.show', $entry))->assertOk();
        $this->actingAs($this->user)->get(route('dashboard.recipes.show', $recipe))->assertOk();
        $this->actingAs($this->user)->get(route('dashboard.exercises.index'))->assertOk();
        $this->actingAs($this->user)->get(route('dashboard.weight.index'))->assertOk();
    }

    public function test_food_edit_and_delete_are_restricted_to_the_creator(): void
    {
        $food = $this->user->foods()->create([
            'name' => '自分の食品',
            'protein' => 1,
            'carbs' => 1,
            'fat' => 1,
            'grams' => 100,
            'food_type_name' => FoodType::first()->name,
        ]);

        $other = User::factory()->create();

        $this->actingAs($other)
            ->put(route('dashboard.foods.update', $food), [
                'name' => '乗っ取り食品',
                'protein' => 1,
                'carbs' => 1,
                'fat' => 1,
                'grams' => 100,
            ])
            ->assertForbidden();

        $this->actingAs($other)
            ->delete(route('dashboard.foods.destroy', $food))
            ->assertForbidden();

        $this->assertDatabaseHas('foods', ['id' => $food->id, 'name' => '自分の食品']);
    }

    public function test_private_diary_image_is_only_accessible_by_its_owner(): void
    {
        Storage::fake('local');

        $entry = $this->user->diaryEntries()->create(['date' => now()]);

        $this->actingAs($this->user)
            ->put(route('dashboard.diary-entries.image.update', $entry), [
                'image' => UploadedFile::fake()->image('meal.jpg'),
            ])
            ->assertRedirect();

        $entry->refresh();
        $this->assertNotNull($entry->image_path);
        Storage::disk('local')->assertExists($entry->image_path);

        // The owner can view it through the dedicated route.
        $this->actingAs($this->user)
            ->get(route('dashboard.diary-entries.image.show', $entry))
            ->assertOk();

        // Another user cannot.
        $other = User::factory()->create();
        $this->actingAs($other)
            ->get(route('dashboard.diary-entries.image.show', $entry))
            ->assertForbidden();

        // Guests are redirected to login.
        auth()->logout();
        $this->get(route('dashboard.diary-entries.image.show', $entry))
            ->assertRedirect(route('login'));
    }

    public function test_guests_cannot_open_the_dashboard_area(): void
    {
        $this->get(route('dashboard.foods.index'))->assertRedirect(route('login'));
        $this->get(route('dashboard.diary-entries.index'))->assertRedirect(route('login'));
        $this->get(route('dashboard.recipes.index'))->assertRedirect(route('login'));
    }
}
