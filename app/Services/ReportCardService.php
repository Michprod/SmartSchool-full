<?php

namespace App\Services;

use App\Models\ConductGrade;
use App\Models\ReportCard;
use App\Models\Student;
use App\Models\StudentAverage;
use App\Models\User;

class ReportCardService
{
    public function __construct(
        protected GradeCalculationService $calculator,
        protected ReportCardPdfService $pdfService
    ) {}

    /**
     * @param  array<string, mixed>  $options
     * @return array{ok: bool, report_card?: ReportCard, error?: string}
     */
    public function generateForStudent(
        Student $student,
        User $generatedBy,
        string $term,
        string $academicYear,
        array $options = []
    ): array {
        $averages = StudentAverage::query()
            ->where('student_id', $student->id)
            ->where('term', $term)
            ->where('academic_year', $academicYear)
            ->get();

        if ($averages->isEmpty()) {
            return ['ok' => false, 'error' => 'No averages found'];
        }

        $first = $averages->first();
        $generalAverage = $first->general_average;
        $failedSubjects = $this->calculator->countFailedSubjects(
            $student->id,
            $term,
            $academicYear
        );
        $decision = $this->calculator->determineDecision((float) $generalAverage, $failedSubjects);

        $conductGrade = ConductGrade::query()
            ->where('student_id', $student->id)
            ->where('term', $term)
            ->where('academic_year', $academicYear)
            ->first();

        $behaviorRecommendations = $options['behavior_recommendations']
            ?? $conductGrade?->appreciation;

        $reportCard = ReportCard::updateOrCreate(
            [
                'student_id' => $student->id,
                'term' => $term,
                'academic_year' => $academicYear,
            ],
            [
                'class_id' => $student->class_id,
                'general_average' => $generalAverage,
                'class_rank' => $first->class_rank,
                'total_students' => $first->total_students,
                'decision' => $decision,
                'class_council_observation' => $options['class_council_observation'] ?? null,
                'work_recommendations' => $options['work_recommendations'] ?? null,
                'behavior_recommendations' => $behaviorRecommendations,
                'generated_by' => $generatedBy->id,
                'generated_at' => now(),
                'is_published' => false,
            ]
        );

        $this->pdfService->generateAndStore($reportCard, $student);

        return ['ok' => true, 'report_card' => $reportCard->fresh()];
    }

    /**
     * @return array{generated_count: int, errors: array<int, array{student_id: int, message: string}>}
     */
    public function generateForClass(
        int $classId,
        User $generatedBy,
        string $term,
        string $academicYear
    ): array {
        $studentIds = StudentAverage::query()
            ->where('class_id', $classId)
            ->where('term', $term)
            ->where('academic_year', $academicYear)
            ->whereNotNull('general_average')
            ->distinct()
            ->pluck('student_id');

        $generated = 0;
        $errors = [];

        foreach ($studentIds as $studentId) {
            $student = Student::find($studentId);
            if (! $student || (int) $student->class_id !== $classId) {
                continue;
            }

            $result = $this->generateForStudent($student, $generatedBy, $term, $academicYear);
            if ($result['ok']) {
                $generated++;
            } else {
                $errors[] = [
                    'student_id' => (int) $studentId,
                    'message' => $result['error'] ?? 'Unknown error',
                ];
            }
        }

        return ['generated_count' => $generated, 'errors' => $errors];
    }

    /**
     * @return array{published_count: int, errors: array<int, array{student_id: int, message: string}>}
     */
    public function publishForClass(int $classId, string $term, string $academicYear): array
    {
        $reportCards = ReportCard::query()
            ->where('class_id', $classId)
            ->where('term', $term)
            ->where('academic_year', $academicYear)
            ->where('is_published', false)
            ->get();

        $published = 0;
        $errors = [];

        foreach ($reportCards as $reportCard) {
            if ($reportCard->general_average === null) {
                $errors[] = [
                    'student_id' => $reportCard->student_id,
                    'message' => 'Bulletin sans moyenne générale',
                ];

                continue;
            }

            $reportCard->publish();
            $published++;
        }

        return ['published_count' => $published, 'errors' => $errors];
    }
}
