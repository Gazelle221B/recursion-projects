<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Exercise;
use App\Models\ExerciseTag;
use App\Models\ExerciseType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExerciseController extends Controller
{
    public function index(Request $request): View
    {
        $exercises = $request->user()->exercises()
            ->with(['exerciseType', 'tags'])
            ->orderByDesc('performed_at')
            ->paginate(10);

        return view('dashboard.exercises.index', [
            'exercises' => $exercises,
            'exerciseTypes' => ExerciseType::all(),
            'exerciseTags' => ExerciseTag::all(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'exercise_type_id' => ['required', 'integer', 'exists:exercise_types,id'],
            'duration_minutes' => ['required', 'integer', 'min:1'],
            'performed_at' => ['required', 'date'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['integer', 'exists:exercise_tags,id'],
        ]);

        $exercise = $request->user()->exercises()->create([
            'exercise_type_id' => $validated['exercise_type_id'],
            'duration_minutes' => $validated['duration_minutes'],
            'performed_at' => $validated['performed_at'],
        ]);
        $exercise->tags()->sync($validated['tags'] ?? []);

        return redirect()
            ->route('dashboard.exercises.index')
            ->with('success', '運動を記録しました。');
    }

    public function destroy(Request $request, Exercise $exercise): RedirectResponse
    {
        abort_unless($exercise->user_id === $request->user()->id, 403);

        $exercise->delete();

        return redirect()
            ->route('dashboard.exercises.index')
            ->with('success', '運動の記録を削除しました。');
    }
}
