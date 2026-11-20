<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRecipeRequest;
use App\Http\Requests\UpdateRecipeRequest;
use App\Models\Food;
use App\Models\Recipe;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RecipeController extends Controller
{
    public function index(Request $request): View
    {
        $recipes = $request->user()->recipes()
            ->with('foods')
            ->orderByDesc('creation_date')
            ->paginate(10);

        return view('dashboard.recipes.index', [
            'recipes' => $recipes,
        ]);
    }

    public function create(): View
    {
        return view('dashboard.recipes.create', [
            'recipe' => new Recipe,
            'foods' => Food::orderBy('name')->get(),
        ]);
    }

    public function store(StoreRecipeRequest $request): RedirectResponse
    {
        $recipe = $request->user()->recipes()->create([
            'name' => $request->validated('name'),
            'creation_date' => $request->validated('creation_date', now()->toDateString()),
        ]);
        $recipe->foods()->sync($request->validated('food_ids'));

        return redirect()
            ->route('dashboard.recipes.show', $recipe)
            ->with('success', 'レシピを作成しました。');
    }

    public function show(Recipe $recipe): View
    {
        $this->authorize('view', $recipe);

        return view('dashboard.recipes.show', [
            'recipe' => $recipe->load('foods'),
        ]);
    }

    public function edit(Recipe $recipe): View
    {
        $this->authorize('update', $recipe);

        return view('dashboard.recipes.edit', [
            'recipe' => $recipe,
            'foods' => Food::orderBy('name')->get(),
        ]);
    }

    public function update(UpdateRecipeRequest $request, Recipe $recipe): RedirectResponse
    {
        $recipe->update([
            'name' => $request->validated('name'),
            'creation_date' => $request->validated('creation_date', $recipe->creation_date),
        ]);
        $recipe->foods()->sync($request->validated('food_ids'));

        return redirect()
            ->route('dashboard.recipes.show', $recipe)
            ->with('success', 'レシピを更新しました。');
    }

    public function destroy(Recipe $recipe): RedirectResponse
    {
        $this->authorize('delete', $recipe);

        $recipe->delete();

        return redirect()
            ->route('dashboard.recipes.index')
            ->with('success', 'レシピを削除しました。');
    }
}
