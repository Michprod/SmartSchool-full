<?php

namespace App\Services;

use App\Models\Assessment;
use App\Models\ClassSubject;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentAverage;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class GradeCalculationService
{
    /**
     * Calculer la moyenne d'un élève dans une matière pour un trimestre
     * Méthode: Points-based Averaging (total des points obtenus / total des points possibles)
     */
    public function calculateSubjectAverage(
        int $studentId,
        int $subjectId,
        string $term,
        string $academicYear
    ): array {
        $assessments = Assessment::where('student_id', $studentId)
            ->where('subject_id', $subjectId)
            ->where('term', $term)
            ->where('academic_year', $academicYear)
            ->where('is_published', true)
            ->get();

        if ($assessments->isEmpty()) {
            return [
                'average' => null,
                'total_coefficient' => 0,
                'assessments_count' => 0,
            ];
        }

        $totalWeightedScore = 0;
        $totalWeightedMax = 0;
        $totalCoefficient = 0;

        foreach ($assessments as $assessment) {
            // Convertir la note sur 20
            $scoreOn20 = $assessment->score_on_20;
            $coefficient = $assessment->coefficient;

            // Pondération
            $totalWeightedScore += $scoreOn20 * $coefficient;
            $totalWeightedMax += 20 * $coefficient;
            $totalCoefficient += $coefficient;
        }

        // Calculer la moyenne
        $average = $totalWeightedMax > 0
            ? ($totalWeightedScore / $totalWeightedMax) * 20
            : 0;

        return [
            'average' => round($average, 2),
            'total_coefficient' => $totalCoefficient,
            'assessments_count' => $assessments->count(),
        ];
    }

    /**
     * Calculer la moyenne générale d'un élève pour un trimestre
     */
    public function calculateGeneralAverage(
        int $studentId,
        string $term,
        string $academicYear
    ): ?float {
        $student = Student::find($studentId);
        if (!$student || !$student->class_id) {
            return null;
        }

        // Récupérer les matières de la classe avec leurs coefficients
        $classSubjects = ClassSubject::where('class_id', $student->class_id)
            ->where('academic_year', $academicYear)
            ->where('is_active', true)
            ->with('subject')
            ->get();

        if ($classSubjects->isEmpty()) {
            return null;
        }

        $totalWeightedAverage = 0;
        $totalCoefficient = 0;

        foreach ($classSubjects as $classSubject) {
            $subjectAverage = $this->calculateSubjectAverage(
                $studentId,
                $classSubject->subject_id,
                $term,
                $academicYear
            );

            if ($subjectAverage['average'] !== null) {
                $totalWeightedAverage += $subjectAverage['average'] * $classSubject->coefficient;
                $totalCoefficient += $classSubject->coefficient;
            }
        }

        if ($totalCoefficient === 0) {
            return null;
        }

        return round($totalWeightedAverage / $totalCoefficient, 2);
    }

    /**
     * Calculer et sauvegarder les moyennes d'un élève
     */
    public function calculateAndSaveStudentAverages(
        int $studentId,
        string $term,
        string $academicYear
    ): array {
        $student = Student::find($studentId);
        if (!$student || !$student->class_id) {
            return ['error' => 'Student not found or not assigned to a class'];
        }

        $classId = $student->class_id;

        // Récupérer les matières de la classe
        $classSubjects = ClassSubject::where('class_id', $classId)
            ->where('academic_year', $academicYear)
            ->where('is_active', true)
            ->get();

        $subjectAverages = [];
        $generalAverage = null;

        // Calculer les moyennes par matière
        foreach ($classSubjects as $classSubject) {
            $calculation = $this->calculateSubjectAverage(
                $studentId,
                $classSubject->subject_id,
                $term,
                $academicYear
            );

            if ($calculation['average'] !== null) {
                // Sauvegarder la moyenne de la matière
                $average = StudentAverage::updateOrCreate(
                    [
                        'student_id' => $studentId,
                        'subject_id' => $classSubject->subject_id,
                        'term' => $term,
                        'academic_year' => $academicYear,
                    ],
                    [
                        'class_id' => $classId,
                        'average_score' => $calculation['average'],
                        'total_coefficient' => $calculation['total_coefficient'],
                        'assessments_count' => $calculation['assessments_count'],
                        'general_average' => null, // Sera mis à jour après
                        'appreciation' => StudentAverage::getAppreciation($calculation['average']),
                        'calculated_at' => now(),
                    ]
                );

                $subjectAverages[] = [
                    'subject' => $classSubject->subject->name,
                    'average' => $calculation['average'],
                    'coefficient' => $classSubject->coefficient,
                ];
            }
        }

        // Calculer et sauvegarder la moyenne générale
        $generalAverage = $this->calculateGeneralAverage($studentId, $term, $academicYear);

        if ($generalAverage !== null) {
            // Mettre à jour toutes les moyennes de l'élève avec la moyenne générale
            StudentAverage::where('student_id', $studentId)
                ->where('term', $term)
                ->where('academic_year', $academicYear)
                ->update(['general_average' => $generalAverage]);
        }

        return [
            'student_id' => $studentId,
            'term' => $term,
            'academic_year' => $academicYear,
            'subject_averages' => $subjectAverages,
            'general_average' => $generalAverage,
        ];
    }

    /**
     * Calculer les rangs dans la classe pour un trimestre
     */
    public function calculateClassRanks(int $classId, string $term, string $academicYear): void
    {
        // Récupérer toutes les moyennes générales de la classe
        $averages = StudentAverage::where('class_id', $classId)
            ->where('term', $term)
            ->where('academic_year', $academicYear)
            ->whereNotNull('general_average')
            ->select('student_id', 'general_average')
            ->distinct()
            ->orderBy('general_average', 'desc')
            ->get();

        $totalStudents = $averages->count();
        $rank = 1;
        $previousAverage = null;

        foreach ($averages as $index => $average) {
            // Gestion des ex-aequo
            if ($previousAverage !== null && $average->general_average < $previousAverage) {
                $rank = $index + 1;
            }

            // Mettre à jour le rang pour toutes les moyennes de cet élève
            StudentAverage::where('student_id', $average->student_id)
                ->where('term', $term)
                ->where('academic_year', $academicYear)
                ->update([
                    'class_rank' => $rank,
                    'total_students' => $totalStudents,
                ]);

            $previousAverage = $average->general_average;
        }
    }

    /**
     * Calculer toutes les moyennes d'une classe
     */
    public function calculateClassAverages(
        int $classId,
        string $term,
        string $academicYear
    ): array {
        $students = Student::where('class_id', $classId)->get();
        $results = [];

        foreach ($students as $student) {
            $result = $this->calculateAndSaveStudentAverages(
                $student->id,
                $term,
                $academicYear
            );
            $results[] = $result;
        }

        // Calculer les rangs
        $this->calculateClassRanks($classId, $term, $academicYear);

        return [
            'class_id' => $classId,
            'term' => $term,
            'academic_year' => $academicYear,
            'students_processed' => count($results),
            'results' => $results,
        ];
    }

    /**
     * Déterminer la décision du conseil de classe
     */
    public function determineDecision(float $generalAverage, int $failedSubjects = 0): string
    {
        if ($generalAverage >= 10 && $failedSubjects <= 2) {
            return 'pass';
        } elseif ($generalAverage >= 8 && $failedSubjects <= 3) {
            return 'conditional_pass';
        } elseif ($generalAverage < 8 || $failedSubjects > 3) {
            return 'fail';
        }

        return 'pass';
    }

    /**
     * Compter les matières en échec pour un élève
     */
    public function countFailedSubjects(
        int $studentId,
        string $term,
        string $academicYear,
        float $threshold = 10
    ): int {
        return StudentAverage::where('student_id', $studentId)
            ->where('term', $term)
            ->where('academic_year', $academicYear)
            ->where('average_score', '<', $threshold)
            ->count();
    }

    /**
     * Obtenir les statistiques de la classe
     */
    public function getClassStatistics(int $classId, string $term, string $academicYear): array
    {
        $averages = StudentAverage::where('class_id', $classId)
            ->where('term', $term)
            ->where('academic_year', $academicYear)
            ->whereNotNull('general_average')
            ->distinct('student_id')
            ->pluck('general_average');

        if ($averages->isEmpty()) {
            return [
                'count' => 0,
                'average' => null,
                'min' => null,
                'max' => null,
            ];
        }

        return [
            'count' => $averages->count(),
            'average' => round($averages->avg(), 2),
            'min' => round($averages->min(), 2),
            'max' => round($averages->max(), 2),
        ];
    }
}
