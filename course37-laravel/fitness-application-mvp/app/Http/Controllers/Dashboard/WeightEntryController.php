<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\WeightEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WeightEntryController extends Controller
{
    public function index(Request $request): View
    {
        $entries = $request->user()->weightEntries()
            ->orderByDesc('recorded_on')
            ->paginate(20);

        return view('dashboard.weight.index', [
            'entries' => $entries,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'weight' => ['required', 'numeric', 'gt:0'],
            'recorded_on' => ['required', 'date'],
        ]);

        $request->user()->weightEntries()->create($validated);

        return redirect()
            ->route('dashboard.weight.index')
            ->with('success', '体重を記録しました。');
    }

    public function destroy(Request $request, WeightEntry $weightEntry): RedirectResponse
    {
        abort_unless($weightEntry->user_id === $request->user()->id, 403);

        $weightEntry->delete();

        return redirect()
            ->route('dashboard.weight.index')
            ->with('success', '体重の記録を削除しました。');
    }
}
