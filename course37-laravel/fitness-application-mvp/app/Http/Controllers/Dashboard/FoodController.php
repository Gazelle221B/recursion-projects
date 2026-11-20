<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFoodRequest;
use App\Http\Requests\UpdateFoodRequest;
use App\Models\Food;
use App\Models\FoodTag;
use App\Models\FoodType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FoodController extends Controller
{
    /**
     * List foods, optionally filtered by name / type / tags.
     */
    public function index(Request $request): View
    {
        $query = Food::query()->with(['foodType', 'tags', 'owner'])->orderByDesc('created_at');

        if ($request->filled('name')) {
            $query->where('name', 'like', '%'.$request->input('name').'%');
        }

        if ($request->filled('food_type')) {
            $query->whereHas('foodType', fn ($q) => $q->where('name', $request->input('food_type')));
        }

        if ($request->filled('tags')) {
            foreach (explode(',', $request->input('tags')) as $tagName) {
                $tagName = trim($tagName);
                if ($tagName !== '') {
                    $query->whereHas('tags', fn ($q) => $q->where('name', $tagName));
                }
            }
        }

        return view('dashboard.foods.index', [
            'foods' => $query->paginate(10)->withQueryString(),
            'foodTypes' => FoodType::all(),
        ]);
    }

    public function create(): View
    {
        return view('dashboard.foods.create', [
            'food' => new Food,
            'foodTypes' => FoodType::all(),
            'foodTags' => FoodTag::all(),
        ]);
    }

    public function store(StoreFoodRequest $request): RedirectResponse
    {
        $food = $request->user()->foods()->create($request->safe()->except('tags'));
        $food->tags()->sync($request->validated('tags', []));

        return redirect()
            ->route('dashboard.foods.show', $food)
            ->with('success', '食品を作成しました。');
    }

    public function show(Food $food): View
    {
        return view('dashboard.foods.show', [
            'food' => $food->load(['foodType', 'tags', 'owner']),
        ]);
    }

    public function edit(Food $food): View
    {
        $this->authorize('update', $food);

        return view('dashboard.foods.edit', [
            'food' => $food,
            'foodTypes' => FoodType::all(),
            'foodTags' => FoodTag::all(),
        ]);
    }

    public function update(UpdateFoodRequest $request, Food $food): RedirectResponse
    {
        $food->update($request->safe()->except('tags'));
        $food->tags()->sync($request->validated('tags', []));

        return redirect()
            ->route('dashboard.foods.show', $food)
            ->with('success', '食品を更新しました。');
    }

    public function destroy(Food $food): RedirectResponse
    {
        $this->authorize('delete', $food);

        $food->delete();

        return redirect()
            ->route('dashboard.foods.index')
            ->with('success', '食品を削除しました。');
    }
}
