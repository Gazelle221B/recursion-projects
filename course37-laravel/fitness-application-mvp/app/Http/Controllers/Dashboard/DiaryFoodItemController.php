<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\DiaryEntry;
use App\Models\DiaryFoodItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DiaryFoodItemController extends Controller
{
    public function store(Request $request, DiaryEntry $diaryEntry): RedirectResponse
    {
        abort_unless($diaryEntry->user_id === $request->user()->id, 403);

        $validated = $request->validate([
            'food_id' => ['required', 'integer', 'exists:foods,id'],
            'grams' => ['required', 'numeric', 'gt:0'],
        ]);

        $diaryEntry->foodItems()->create($validated);

        return back()->with('success', '食品を日誌に追加しました。');
    }

    public function destroy(Request $request, DiaryEntry $diaryEntry, DiaryFoodItem $diaryFoodItem): RedirectResponse
    {
        abort_unless($diaryEntry->user_id === $request->user()->id, 403);
        abort_unless($diaryFoodItem->diary_entry_id === $diaryEntry->id, 404);

        $diaryFoodItem->delete();

        return back()->with('success', '食品を日誌から削除しました。');
    }
}
