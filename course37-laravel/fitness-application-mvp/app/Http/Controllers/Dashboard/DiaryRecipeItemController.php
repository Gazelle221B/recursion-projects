<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\DiaryEntry;
use App\Models\DiaryRecipeItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DiaryRecipeItemController extends Controller
{
    public function store(Request $request, DiaryEntry $diaryEntry): RedirectResponse
    {
        abort_unless($diaryEntry->user_id === $request->user()->id, 403);

        $validated = $request->validate([
            // Only the user's own recipes can be added to their diary.
            'recipe_id' => ['required', 'integer', 'exists:recipes,id'],
        ]);

        abort_unless(
            $request->user()->recipes()->whereKey($validated['recipe_id'])->exists(),
            403
        );

        $diaryEntry->recipeItems()->create($validated);

        return back()->with('success', 'レシピを日誌に追加しました。');
    }

    public function destroy(Request $request, DiaryEntry $diaryEntry, DiaryRecipeItem $diaryRecipeItem): RedirectResponse
    {
        abort_unless($diaryEntry->user_id === $request->user()->id, 403);
        abort_unless($diaryRecipeItem->diary_entry_id === $diaryEntry->id, 404);

        $diaryRecipeItem->delete();

        return back()->with('success', 'レシピを日誌から削除しました。');
    }
}
