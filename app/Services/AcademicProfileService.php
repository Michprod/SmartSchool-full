<?php

namespace App\Services;

use App\Models\Assessment;
use App\Models\ReportCard;
use App\Models\Student;
use App\Models\StudentAverage;
use Illuminate\Support\Collection;

class AcademicProfileService
{
    public function __construct(
        protected AcademicPeriodService $periods
    ) {}

    /**
     * Évolution multi-périodes (moyenne générale, rang, bulletin).
     *
     * @return array<string, mixed>
     */
    public function studentEvolution(Student $student, string $academicYear): array
    {
        $scheme = $this->periods->schemeForStudent($student);
        $periodCodes = $this->periods->validPeriodCodes($scheme);

        $timeline = [];
        foreach ($periodCodes as $code) {
            $averages = StudentAverage::query()
                ->where('student_id', $student->id)
                ->where('term', $code)
                ->where('academic_year', $academicYear)
                ->with('subject')
                ->get();

            $first = $averages->first();
            $reportCard = ReportCard::query()
                ->where('student_id', $student->id)
                ->where('term', $code)
                ->where('academic_year', $academicYear)
                ->first();

            $timeline[] = [
                'term' => $code,
                'term_label' => $this->periods->periodLabel($code, $scheme),
                'general_average' => $first?->general_average,
                'class_rank' => $first?->class_rank,
                'total_students' => $first?->total_students,
                'rank_display' => $first && $first->class_rank
                    ? "{$first->class_rank}/{$first->total_students}"
                    : null,
                'subject_averages' => $averages->map(fn ($row) => [
                    'subject_id' => $row->subject_id,
                    'subject' => $row->subject?->name,
                    'average' => $row->average_score,
                    'appreciation' => $row->appreciation,
                ])->values(),
                'report_card' => $reportCard ? [
                    'decision' => $reportCard->decision,
                    'decision_label' => $reportCard->decision_label,
                    'is_published' => $reportCard->is_published,
                ] : null,
            ];
        }

        $assessmentsByPeriod = $this->assessmentsSummaryByPeriod($student->id, $academicYear, $periodCodes);

        return [
            'student_id' => $student->id,
            'academic_year' => $academicYear,
            'period_scheme' => $scheme,
            'period_scheme_label' => $scheme === AcademicPeriodService::SCHEME_SEMESTRE ? 'Semestre' : 'Trimestre',
            'periods' => $this->periods->periodsForScheme($scheme),
            'timeline' => $timeline,
            'assessments_by_period' => $assessmentsByPeriod,
        ];
    }

    /**
     * Profil académique détaillé pour une période.
     *
     * @return array<string, mixed>
     */
    public function studentAcademicProfile(Student $student, string $term, string $academicYear, bool $parentsView = false): array
    {
        $scheme = $this->periods->schemeForStudent($student);

        $averagesQuery = StudentAverage::query()
            ->where('student_id', $student->id)
            ->where('term', $term)
            ->where('academic_year', $academicYear)
            ->with('subject');

        $averages = $averagesQuery->get();
        $first = $averages->first();

        $assessmentsQuery = Assessment::query()
            ->where('student_id', $student->id)
            ->where('term', $term)
            ->where('academic_year', $academicYear)
            ->with(['subject', 'teacher']);

        if ($parentsView) {
            $assessmentsQuery->where('is_published', true);
        }

        $assessments = $assessmentsQuery->orderBy('date')->get();

        $bySubject = $this->groupAssessmentsBySubject($assessments);
        $byType = $this->groupAssessmentsByType($assessments);

        $reportCard = ReportCard::query()
            ->where('student_id', $student->id)
            ->where('term', $term)
            ->where('academic_year', $academicYear)
            ->first();

        return [
            'student_id' => $student->id,
            'term' => $term,
            'term_label' => $this->periods->periodLabel($term, $scheme),
            'academic_year' => $academicYear,
            'period_scheme' => $scheme,
            'general_average' => $first?->general_average,
            'class_rank' => $first?->class_rank,
            'total_students' => $first?->total_students,
            'rank_display' => $first && $first->class_rank
                ? "{$first->class_rank}/{$first->total_students}"
                : null,
            'subject_averages' => $averages,
            'assessments_by_subject' => $bySubject,
            'assessments_by_type' => $byType,
            'assessment_type_labels' => $this->periods->assessmentTypeLabels(),
            'report_card' => $reportCard,
        ];
    }

    /**
     * Bulletin de classe : classement complet + statistiques.
     *
     * @return array<string, mixed>
     */
    public function classBulletin(int $classId, string $term, string $academicYear, GradeCalculationService $calculator): array
    {
        $students = Student::query()
            ->where('class_id', $classId)
            ->where('is_active', true)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        $ranking = [];
        foreach ($students as $student) {
            $averages = StudentAverage::query()
                ->where('student_id', $student->id)
                ->where('term', $term)
                ->where('academic_year', $academicYear)
                ->with('subject')
                ->get();

            if ($averages->isEmpty()) {
                continue;
            }

            $first = $averages->first();
            $ranking[] = [
                'student' => $student,
                'general_average' => $first->general_average,
                'class_rank' => $first->class_rank,
                'total_students' => $first->total_students,
                'rank_display' => $first->class_rank
                    ? "{$first->class_rank}/{$first->total_students}"
                    : null,
                'subject_averages' => $averages,
                'report_card' => ReportCard::query()
                    ->where('student_id', $student->id)
                    ->where('term', $term)
                    ->where('academic_year', $academicYear)
                    ->first(),
            ];
        }

        usort($ranking, fn ($a, $b) => ($b['general_average'] ?? 0) <=> ($a['general_average'] ?? 0));

        return [
            'class_id' => $classId,
            'term' => $term,
            'academic_year' => $academicYear,
            'statistics' => $calculator->getClassStatistics($classId, $term, $academicYear),
            'students' => $ranking,
            'students_count' => count($ranking),
        ];
    }

    /**
     * @param  list<string>  $periodCodes
     * @return array<string, array<string, mixed>>
     */
    private function assessmentsSummaryByPeriod(int $studentId, string $academicYear, array $periodCodes): array
    {
        $summary = [];
        foreach ($periodCodes as $code) {
            $assessments = Assessment::query()
                ->where('student_id', $studentId)
                ->where('term', $code)
                ->where('academic_year', $academicYear)
                ->where('is_published', true)
                ->get();

            $summary[$code] = [
                'total' => $assessments->count(),
                'by_type' => $assessments->groupBy('type')->map->count(),
            ];
        }

        return $summary;
    }

    /**
     * @param  Collection<int, Assessment>  $assessments
     * @return list<array<string, mixed>>
     */
    private function groupAssessmentsBySubject(Collection $assessments): array
    {
        return $assessments
            ->groupBy('subject_id')
            ->map(function ($items, $subjectId) {
                $subject = $items->first()->subject;

                return [
                    'subject_id' => (int) $subjectId,
                    'subject' => $subject?->name,
                    'assessments' => $items->map(fn ($a) => $this->formatAssessment($a))->values(),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, Assessment>  $assessments
     * @return list<array<string, mixed>>
     */
    private function groupAssessmentsByType(Collection $assessments): array
    {
        $labels = $this->periods->assessmentTypeLabels();

        return $assessments
            ->groupBy('type')
            ->map(function ($items, $type) use ($labels) {
                return [
                    'type' => $type,
                    'type_label' => $labels[$type] ?? $type,
                    'count' => $items->count(),
                    'assessments' => $items->map(fn ($a) => $this->formatAssessment($a))->values(),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function formatAssessment(Assessment $a): array
    {
        return [
            'id' => $a->id,
            'type' => $a->type,
            'type_label' => $this->periods->assessmentTypeLabels()[$a->type] ?? $a->type,
            'subject' => $a->subject?->name,
            'score' => $a->score,
            'max_score' => $a->max_score,
            'score_on_20' => round($a->score_on_20, 2),
            'coefficient' => $a->coefficient,
            'title' => $a->title,
            'comment' => $a->comment,
            'date' => $a->date?->format('Y-m-d'),
            'is_published' => $a->is_published,
        ];
    }
}
