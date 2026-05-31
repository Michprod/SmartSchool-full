<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ConductGrade;
use App\Models\SchoolClass;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ConductGradeController extends Controller
{
    public function index(Request $request, SchoolClass $class)
    {
        $user = Auth::user();

        if (! $this->canManageConduct($user, $class)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'term' => 'required|string|max:3',
            'academic_year' => 'required|string',
        ]);

        $students = Student::where('class_id', $class->id)
            ->where('is_active', true)
            ->orderBy('last_name')
            ->get();

        $grades = ConductGrade::where('class_id', $class->id)
            ->where('term', $validated['term'])
            ->where('academic_year', $validated['academic_year'])
            ->get()
            ->keyBy('student_id');

        $rows = $students->map(function (Student $student) use ($grades) {
            $grade = $grades->get($student->id);

            return [
                'student' => [
                    'id' => $student->id,
                    'first_name' => $student->first_name,
                    'last_name' => $student->last_name,
                    'matricule' => $student->matricule,
                ],
                'conduct_score' => $grade?->conduct_score,
                'appreciation' => $grade?->appreciation,
                'recorded_by' => $grade?->recorded_by,
            ];
        });

        return response()->json([
            'class' => $class,
            'term' => $validated['term'],
            'academic_year' => $validated['academic_year'],
            'students' => $rows,
        ]);
    }

    public function bulkStore(Request $request, SchoolClass $class)
    {
        $user = Auth::user();

        if (! $this->canManageConduct($user, $class)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'term' => 'required|string|max:3',
            'academic_year' => 'required|string',
            'grades' => 'required|array',
            'grades.*.student_id' => 'required|exists:students,id',
            'grades.*.conduct_score' => 'nullable|numeric|min:0|max:20',
            'grades.*.appreciation' => 'nullable|string|max:500',
        ]);

        $saved = [];

        DB::transaction(function () use ($validated, $class, $user, &$saved) {
            foreach ($validated['grades'] as $row) {
                $student = Student::findOrFail($row['student_id']);
                if ($student->class_id != $class->id) {
                    continue;
                }

                $saved[] = ConductGrade::updateOrCreate(
                    [
                        'student_id' => $row['student_id'],
                        'term' => $validated['term'],
                        'academic_year' => $validated['academic_year'],
                    ],
                    [
                        'class_id' => $class->id,
                        'conduct_score' => $row['conduct_score'] ?? null,
                        'appreciation' => $row['appreciation'] ?? null,
                        'recorded_by' => $user->id,
                    ]
                );
            }
        });

        return response()->json([
            'message' => count($saved).' conduct grades saved',
            'data' => $saved,
        ], 201);
    }

    protected function canManageConduct($user, SchoolClass $class): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->hasPermission('conduct:write') && $class->teacher_id == $user->id) {
            return true;
        }

        return false;
    }
}
