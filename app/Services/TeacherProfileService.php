<?php

namespace App\Services;

use App\Models\ClassSubject;
use App\Models\User;

class TeacherProfileService
{
    public function __construct(
        protected TeacherWorkloadService $workload
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function build(User $teacher, ?string $academicYear = null): array
    {
        $assignmentsQuery = ClassSubject::query()
            ->where('teacher_id', $teacher->id)
            ->with(['schoolClass.gradeLevel.educationCycle', 'subject'])
            ->orderBy('academic_year', 'desc');

        if ($academicYear) {
            $assignmentsQuery->where('academic_year', $academicYear);
        }

        $assignments = $assignmentsQuery->get();

        $principalClass = $teacher->principalClass()
            ->with(['gradeLevel.educationCycle'])
            ->first();

        return [
            'user' => [
                'id' => $teacher->id,
                'first_name' => $teacher->first_name,
                'last_name' => $teacher->last_name,
                'email' => $teacher->email,
                'phone' => $teacher->phone,
                'department' => $teacher->department,
                'job_title' => $teacher->job_title,
                'job_grade' => $teacher->job_grade,
                'has_professional_profile' => $teacher->has_professional_profile,
                'workload_hours' => $teacher->workload_hours,
                'bio' => $teacher->bio,
                'is_active' => $teacher->is_active,
            ],
            'principal_class' => $principalClass ? [
                'id' => $principalClass->id,
                'display_name' => $principalClass->display_name ?? $principalClass->name,
                'academic_year' => $principalClass->academic_year,
            ] : null,
            'assignments' => $assignments->map(fn (ClassSubject $cs) => [
                'id' => $cs->id,
                'class_id' => $cs->class_id,
                'class_name' => $cs->schoolClass?->display_name ?? $cs->schoolClass?->name,
                'subject_id' => $cs->subject_id,
                'subject_name' => $cs->subject?->name,
                'subject_code' => $cs->subject?->code,
                'coefficient' => $cs->coefficient,
                'hours_per_week' => $cs->hours_per_week,
                'academic_year' => $cs->academic_year,
                'is_active' => $cs->is_active,
                'schedule' => $cs->schedule,
            ])->values(),
            'workload' => $this->workload->summary($teacher, $academicYear),
            'subjects_by_name' => $teacher->subjectsWithClasses()
                ->map(fn ($group, $name) => [
                    'subject' => $name,
                    'classes' => $group->map(fn ($cs) => [
                        'class_id' => $cs->class_id,
                        'class_name' => $cs->schoolClass?->display_name ?? $cs->schoolClass?->name,
                        'academic_year' => $cs->academic_year,
                    ])->values(),
                ])
                ->values(),
        ];
    }
}
