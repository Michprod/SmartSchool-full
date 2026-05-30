<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\StudentAttendanceResource;
use App\Models\Student;
use App\Models\StudentAttendance;
use Illuminate\Http\Request;

class StudentAttendanceController extends Controller
{
    public function index(Request $request, string $studentId)
    {
        Student::findOrFail($studentId);

        $validated = $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date',
            'status' => 'nullable|in:present,late,absent,excused',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = StudentAttendance::where('student_id', $studentId)
            ->with('recorder')
            ->orderByDesc('attendance_date');

        if (!empty($validated['from'])) {
            $query->whereDate('attendance_date', '>=', $validated['from']);
        }
        if (!empty($validated['to'])) {
            $query->whereDate('attendance_date', '<=', $validated['to']);
        }
        if (!empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        return StudentAttendanceResource::collection(
            $query->paginate($validated['per_page'] ?? 30)
        );
    }

    public function summary(Request $request, string $studentId)
    {
        Student::findOrFail($studentId);

        $validated = $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date',
        ]);

        $query = StudentAttendance::where('student_id', $studentId);
        if (!empty($validated['from'])) {
            $query->whereDate('attendance_date', '>=', $validated['from']);
        }
        if (!empty($validated['to'])) {
            $query->whereDate('attendance_date', '<=', $validated['to']);
        }

        $total = (clone $query)->count();
        $present = (clone $query)->where('status', 'present')->count();
        $late = (clone $query)->where('status', 'late')->count();
        $absent = (clone $query)->where('status', 'absent')->count();
        $excused = (clone $query)->where('status', 'excused')->count();
        $attendanceRate = $total > 0 ? round((($present + $late) / $total) * 100, 2) : 0;

        return response()->json([
            'student_id' => (int) $studentId,
            'period' => [
                'from' => $validated['from'] ?? null,
                'to' => $validated['to'] ?? null,
            ],
            'summary' => [
                'total_days' => $total,
                'present' => $present,
                'late' => $late,
                'absent' => $absent,
                'excused' => $excused,
                'attendance_rate' => $attendanceRate,
            ],
        ]);
    }

    public function store(Request $request, string $studentId)
    {
        Student::findOrFail($studentId);

        $validated = $request->validate([
            'attendance_date' => 'required|date',
            'status' => 'required|in:present,late,absent,excused',
            'reason' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $attendance = StudentAttendance::updateOrCreate(
            [
                'student_id' => $studentId,
                'attendance_date' => $validated['attendance_date'],
            ],
            [
                'status' => $validated['status'],
                'reason' => $validated['reason'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'recorded_by' => $request->user()?->id,
            ]
        );

        return (new StudentAttendanceResource($attendance->load('recorder')))
            ->response()
            ->setStatusCode(201);
    }

    public function update(Request $request, string $studentId, string $attendanceId)
    {
        Student::findOrFail($studentId);
        $attendance = StudentAttendance::where('student_id', $studentId)->findOrFail($attendanceId);

        $validated = $request->validate([
            'attendance_date' => 'sometimes|date',
            'status' => 'sometimes|in:present,late,absent,excused',
            'reason' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        if (!empty($validated)) {
            $attendance->update(array_merge($validated, ['recorded_by' => $request->user()?->id]));
        }

        return new StudentAttendanceResource($attendance->fresh()->load('recorder'));
    }

    public function destroy(string $studentId, string $attendanceId)
    {
        Student::findOrFail($studentId);
        $attendance = StudentAttendance::where('student_id', $studentId)->findOrFail($attendanceId);
        $attendance->delete();

        return response()->json(null, 204);
    }
}
