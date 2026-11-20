<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\DiaryEntry;
use App\Models\Food;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DiaryEntryController extends Controller
{
    /**
     * Only the owner of a diary entry may access it.
     */
    private function ensureOwner(Request $request, DiaryEntry $diaryEntry): void
    {
        abort_unless($diaryEntry->user_id === $request->user()->id, 403);
    }

    public function index(Request $request): View
    {
        $entries = $request->user()->diaryEntries()
            ->with(['foodItems.food', 'recipeItems.recipe.foods'])
            ->orderByDesc('date')
            ->paginate(10);

        return view('dashboard.diary.index', [
            'entries' => $entries,
        ]);
    }

    public function create(): View
    {
        return view('dashboard.diary.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'date' => ['required', 'date'],
        ]);

        // Diary dates are stored in UTC.
        $date = Carbon::parse($validated['date'])->utc();

        $entry = $request->user()->diaryEntries()->create([
            'date' => $date,
        ]);

        return redirect()
            ->route('dashboard.diary-entries.show', $entry)
            ->with('success', '日誌を作成しました。');
    }

    public function show(Request $request, DiaryEntry $diaryEntry): View
    {
        $this->ensureOwner($request, $diaryEntry);

        $diaryEntry->load(['foodItems.food', 'recipeItems.recipe.foods']);

        return view('dashboard.diary.show', [
            'entry' => $diaryEntry,
            'foods' => Food::orderBy('name')->get(),
            'recipes' => $request->user()->recipes()->orderBy('name')->get(),
        ]);
    }

    public function destroy(Request $request, DiaryEntry $diaryEntry): RedirectResponse
    {
        $this->ensureOwner($request, $diaryEntry);

        if ($diaryEntry->image_path) {
            Storage::disk('local')->delete($diaryEntry->image_path);
        }

        $diaryEntry->delete();

        return redirect()
            ->route('dashboard.diary-entries.index')
            ->with('success', '日誌を削除しました。');
    }

    /**
     * Upload a private reminder image for the diary entry.
     * The file is stored on the private (local) disk, never in public/.
     */
    public function uploadImage(Request $request, DiaryEntry $diaryEntry): RedirectResponse
    {
        $this->ensureOwner($request, $diaryEntry);

        $request->validate([
            'image' => ['required', 'image', 'max:5120'],
        ]);

        if ($diaryEntry->image_path) {
            Storage::disk('local')->delete($diaryEntry->image_path);
        }

        $path = $request->file('image')->store('diary-images', 'local');

        $diaryEntry->update(['image_path' => $path]);

        return back()->with('success', '画像をアップロードしました。');
    }

    /**
     * Serve the private diary image. Only the owner can access it;
     * the file itself is not reachable through any public URL.
     */
    public function showImage(Request $request, DiaryEntry $diaryEntry): StreamedResponse
    {
        $this->ensureOwner($request, $diaryEntry);

        abort_unless($diaryEntry->image_path && Storage::disk('local')->exists($diaryEntry->image_path), 404);

        return Storage::disk('local')->response($diaryEntry->image_path);
    }
}
