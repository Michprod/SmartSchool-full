<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\EvaluationSession;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EvaluationSessionController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'class_id' => 'nullable|exists:school_classes,id',
            'subject_id' => 'nullable|exists:subjects,id',
            'term' => 'nullable|string|max:3',
            'academic_year' => 'nullable|string',
        ]);

        $query = EvaluationSession::query()->with(['schoolClass', 'subject', 'teacher']);

        if (! $user->hasRole('admin')) {
            $query->where('teacher_id', $user->id);
        }

        foreach (['class_id', 'subject_id', 'term', 'academic_year'] as $field) {
            if (! empty($validated[$field])) {
                $query->where($field, $validated[$field]);
            }
        }

        return response()->json($query->orderByDesc('date')->paginate(20));
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'class_id' => 'required|exists:school_classes,id',
            'subject_id' => 'required|exists:subjects,id',
            'type' => 'required|string|max:32',
            'term' => 'required|string|max:3',
            'academic_year' => 'required|string',
            'title' => 'required|string|max:255',
            'date' => 'required|date',
            'max_score' => 'required|numeric|min:1',
            'coefficient' => 'nullable|numeric|min:0',
        ]);

        if (! $user->canGrade($validated['class_id'], $validated['subject_id'])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $session = EvaluationSession::create([
            ...$validated,
            'teacher_id' => $user->id,
            'coefficient' => $validated['coefficient'] ?? 1,
            'is_published' => false,
        ]);

        return response()->json($session->load(['schoolClass', 'subject']), 201);
    }

    public function show(int $id)
    {
        $session = EvaluationSession::with(['schoolClass', 'subject', 'assessments.student'])->findOrFail($id);
        $user = Auth::user();

        if (! $user->hasRole('admin') && $session->teacher_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json($session);
    }

    public function storeGrades(Request $request, int $id)
    {
        $session = EvaluationSession::findOrFail($id);
        $user = Auth::user();

        if (! $user->canGrade($session->class_id, $session->subject_id)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'grades' => 'required|array',
            'grades.*.student_id' => 'required|exists:students,id',
            'grades.*.score' => 'required|numeric|min:0',
            'grades.*.comment' => 'nullable|string',
        ]);

        $created = [];

        DB::transaction(function () use ($validated, $session, $user, &$created) {
            foreach ($validated['grades'] as $grade) {
                $student = Student::findOrFail($grade['student_id']);
                if ($student->class_id != $session->class_id) {
                    continue;
                }

                $created[] = Assessment::updateOrCreate(
                    [
                        'student_id' => $grade['student_id'],
                        'evaluation_session_id' => $session->id,
                    ],
                    [
                        'subject_id' => $session->subject_id,
                        'teacher_id' => $user->id,
                        'class_id' => $session->class_id,
                        'type' => $session->type,
                        'term' => $session->term,
                        'academic_year' => $session->academic_year,
                        'score' => $grade['score'],
                        'max_score' => $session->max_score,
                        'coefficient' => $session->coefficient,
                        'title' => $session->title,
                        'comment' => $grade['comment'] ?? null,
                        'date' => $session->date,
                        'is_published' => $session->is_published,
                    ]
                );
            }
        });

        return response()->json([
            'message' => count($created).' grades saved',
            'data' => $created,
        ], 201);
    }

    public function publish(int $id)
    {
        $session = EvaluationSession::findOrFail($id);
        $user = Auth::user();

        if (! $user->canGrade($session->class_id, $session->subject_id)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $session->publish();

        return response()->json(['message' => 'Session published', 'data' => $session->fresh()]);
    }
}
