<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ClassSubjectResource;
use App\Models\Assessment;
use App\Models\ClassSubject;
use App\Models\SchoolClass;
use App\Models\User;
use App\Services\TeacherWorkloadService;
use Illuminate\Http\Request;

class ClassSubjectController extends Controller
{
    public function __construct(
        protected TeacherWorkloadService $workload
    ) {}

    public function index(SchoolClass $class)
    {
        $subjects = ClassSubject::query()
            ->where('class_id', $class->id)
            ->with(['subject', 'teacher', 'schoolClass'])
            ->orderBy('academic_year', 'desc')
            ->get();

        return ClassSubjectResource::collection($subjects);
    }

    public function store(Request $request, SchoolClass $class)
    {
        $validated = $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'teacher_id' => 'required|exists:users,id',
            'coefficient' => 'nullable|integer|min:1|max:20',
            'hours_per_week' => 'nullable|integer|min:1|max:40',
            'academic_year' => 'required|string|max:20',
            'is_active' => 'sometimes|boolean',
            'schedule' => 'nullable|array',
        ]);

        $teacher = User::findOrFail($validated['teacher_id']);
        if (! $teacher->hasAnyRole(['teacher', 'admin'])) {
            return response()->json(['message' => 'Selected user is not a teacher'], 422);
        }

        $existing = ClassSubject::where('class_id', $class->id)
            ->where('subject_id', $validated['subject_id'])
            ->where('academic_year', $validated['academic_year'])
            ->first();

        if ($existing) {
            return response()->json(['message' => 'Subject already assigned for this class and year'], 422);
        }

        $assignment = ClassSubject::create([
            'class_id' => $class->id,
            'subject_id' => $validated['subject_id'],
            'teacher_id' => $validated['teacher_id'],
            'coefficient' => $validated['coefficient'] ?? 1,
            'hours_per_week' => $validated['hours_per_week'] ?? 2,
            'academic_year' => $validated['academic_year'],
            'is_active' => $validated['is_active'] ?? true,
            'schedule' => $validated['schedule'] ?? null,
        ]);

        $warnings = [];
        if ($this->workload->isOverloaded($teacher, $validated['academic_year'])) {
            $warnings[] = 'Teacher exceeds contractual workload hours';
        }

        return response()->json([
            'data' => new ClassSubjectResource($assignment->load(['subject', 'teacher', 'schoolClass'])),
            'warnings' => $warnings,
        ], 201);
    }

    public function update(Request $request, ClassSubject $classSubject)
    {
        $validated = $request->validate([
            'teacher_id' => 'sometimes|exists:users,id',
            'coefficient' => 'sometimes|integer|min:1|max:20',
            'hours_per_week' => 'sometimes|integer|min:1|max:40',
            'is_active' => 'sometimes|boolean',
            'schedule' => 'nullable|array',
        ]);

        if (isset($validated['teacher_id'])) {
            $teacher = User::findOrFail($validated['teacher_id']);
            if (! $teacher->hasAnyRole(['teacher', 'admin'])) {
                return response()->json(['message' => 'Selected user is not a teacher'], 422);
            }
        }

        $classSubject->update($validated);

        return new ClassSubjectResource($classSubject->fresh()->load(['subject', 'teacher', 'schoolClass']));
    }

    public function updateSchedule(Request $request, ClassSubject $classSubject)
    {
        $validated = $request->validate([
            'schedule' => 'required|array',
        ]);

        $classSubject->update(['schedule' => $validated['schedule']]);

        return new ClassSubjectResource($classSubject->fresh()->load(['subject', 'teacher', 'schoolClass']));
    }

    public function destroy(ClassSubject $classSubject)
    {
        $hasGrades = Assessment::where('class_id', $classSubject->class_id)
            ->where('subject_id', $classSubject->subject_id)
            ->exists();

        if ($hasGrades) {
            $classSubject->update(['is_active' => false]);

            return response()->json(['message' => 'Assignment deactivated (grades exist)']);
        }

        $classSubject->delete();

        return response()->json(null, 204);
    }
}
