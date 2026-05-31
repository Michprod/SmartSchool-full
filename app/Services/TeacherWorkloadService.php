<?php

namespace App\Services;

use App\Models\ClassSubject;
use App\Models\User;

class TeacherWorkloadService
{
    public function computeAssignedHours(User $teacher, ?string $academicYear = null): int
    {
        $query = ClassSubject::query()
            ->where('teacher_id', $teacher->id)
            ->where('is_active', true);

        if ($academicYear) {
            $query->where('academic_year', $academicYear);
        }

        return (int) $query->sum('hours_per_week');
    }

    public function computeCourseCount(User $teacher, ?string $academicYear = null): int
    {
        $query = ClassSubject::query()
            ->where('teacher_id', $teacher->id)
            ->where('is_active', true);

        if ($academicYear) {
            $query->where('academic_year', $academicYear);
        }

        return $query->count();
    }

    public function computeClassCount(User $teacher, ?string $academicYear = null): int
    {
        $query = ClassSubject::query()
            ->where('teacher_id', $teacher->id)
            ->where('is_active', true);

        if ($academicYear) {
            $query->where('academic_year', $academicYear);
        }

        return (int) $query->distinct('class_id')->count('class_id');
    }

    public function isOverloaded(User $teacher, ?string $academicYear = null): bool
    {
        if (! $teacher->workload_hours) {
            return false;
        }

        return $this->computeAssignedHours($teacher, $academicYear) > $teacher->workload_hours;
    }

    /**
     * @return array<string, mixed>
     */
    public function summary(User $teacher, ?string $academicYear = null): array
    {
        $assigned = $this->computeAssignedHours($teacher, $academicYear);
        $contractual = $teacher->workload_hours;

        return [
            'assigned_hours' => $assigned,
            'contractual_hours' => $contractual,
            'remaining_hours' => $contractual !== null ? max(0, $contractual - $assigned) : null,
            'course_count' => $this->computeCourseCount($teacher, $academicYear),
            'class_count' => $this->computeClassCount($teacher, $academicYear),
            'is_overloaded' => $this->isOverloaded($teacher, $academicYear),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function allTeachersSummary(?string $academicYear = null): array
    {
        return User::query()
            ->where('role', 'teacher')
            ->where('is_active', true)
            ->orderBy('last_name')
            ->get()
            ->map(function (User $teacher) use ($academicYear) {
                return [
                    'id' => $teacher->id,
                    'first_name' => $teacher->first_name,
                    'last_name' => $teacher->last_name,
                    'email' => $teacher->email,
                    'department' => $teacher->department,
                    'job_title' => $teacher->job_title,
                    ...$this->summary($teacher, $academicYear),
                ];
            })
            ->all();
    }
}
