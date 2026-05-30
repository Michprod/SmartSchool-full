<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DisciplinaryAction;
use App\Models\DisciplinaryCase;
use App\Models\DisciplinaryNote;
use Illuminate\Http\Request;

class DisciplinaryController extends Controller
{
    public function index(Request $request)
    {
        $query = DisciplinaryCase::with(['student', 'user', 'reporter', 'actions', 'notes.author']);

        foreach (['target_type', 'status', 'severity', 'category'] as $filter) {
            if ($request->filled($filter)) {
                $query->where($filter, $request->string($filter));
            }
        }

        return response()->json($query->latest()->paginate(20));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'target_type' => 'required|in:student,staff_teaching,staff_admin',
            'student_id' => 'nullable|exists:students,id',
            'user_id' => 'nullable|exists:users,id',
            'category' => 'required|in:conduct,administrative,professional',
            'severity' => 'required|in:low,medium,high,critical',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'conduct_note' => 'nullable|string',
            'status' => 'nullable|in:open,in_progress,resolved,dismissed',
            'incident_date' => 'nullable|date',
        ]);

        $validated['reported_by'] = $request->user()?->id;
        $model = DisciplinaryCase::create($validated);

        return response()->json($model->load(['student', 'user', 'reporter']), 201);
    }

    public function show(int $id)
    {
        return response()->json(
            DisciplinaryCase::with(['student', 'user', 'reporter', 'actions', 'notes.author'])->findOrFail($id)
        );
    }

    public function update(Request $request, int $id)
    {
        $model = DisciplinaryCase::findOrFail($id);
        $validated = $request->validate([
            'category' => 'sometimes|in:conduct,administrative,professional',
            'severity' => 'sometimes|in:low,medium,high,critical',
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'conduct_note' => 'nullable|string',
            'status' => 'sometimes|in:open,in_progress,resolved,dismissed',
            'incident_date' => 'nullable|date',
        ]);
        $model->update($validated);
        return response()->json($model);
    }

    public function destroy(int $id)
    {
        DisciplinaryCase::findOrFail($id)->delete();
        return response()->json(null, 204);
    }

    public function addAction(Request $request, int $id)
    {
        $case = DisciplinaryCase::findOrFail($id);
        $validated = $request->validate([
            'action_type' => 'required|in:warning,detention,suspension,expulsion,administrative_note,other',
            'reason' => 'nullable|string',
            'action_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:action_date',
        ]);
        $validated['disciplinary_case_id'] = $case->id;
        $validated['decided_by'] = $request->user()?->id;
        $action = DisciplinaryAction::create($validated);
        return response()->json($action, 201);
    }

    public function addNote(Request $request, int $id)
    {
        $case = DisciplinaryCase::findOrFail($id);
        $validated = $request->validate([
            'note' => 'required|string',
        ]);
        $note = DisciplinaryNote::create([
            'disciplinary_case_id' => $case->id,
            'note' => $validated['note'],
            'author_id' => $request->user()?->id,
        ]);
        return response()->json($note->load('author'), 201);
    }
}
