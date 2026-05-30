<?php

namespace App\Services;

use App\Models\SchoolClass;
use App\Models\Student;

class AcademicPeriodService
{
    /** Primaire et maternel : trimestres */
    public const SCHEME_TRIMESTRE = 'trimestre';

    /** CTEB et Humanités : semestres */
    public const SCHEME_SEMESTRE = 'semestre';

    public function schemeForClass(?SchoolClass $class): string
    {
        if (! $class) {
            return self::SCHEME_TRIMESTRE;
        }

        $class->loadMissing('gradeLevel.educationCycle');
        $cycleCode = $class->gradeLevel?->educationCycle?->code;

        return in_array($cycleCode, ['cteb', 'humanites'], true)
            ? self::SCHEME_SEMESTRE
            : self::SCHEME_TRIMESTRE;
    }

    public function schemeForStudent(Student $student): string
    {
        $student->loadMissing('schoolClass.gradeLevel.educationCycle');

        return $this->schemeForClass($student->schoolClass);
    }

    /**
     * @return array<int, array{code: string, label: string}>
     */
    public function periodsForScheme(string $scheme): array
    {
        if ($scheme === self::SCHEME_SEMESTRE) {
            return [
                ['code' => 'S1', 'label' => '1er Semestre'],
                ['code' => 'S2', 'label' => '2ème Semestre'],
            ];
        }

        return [
            ['code' => 'T1', 'label' => '1er Trimestre'],
            ['code' => 'T2', 'label' => '2ème Trimestre'],
            ['code' => 'T3', 'label' => '3ème Trimestre'],
        ];
    }

    /**
     * Codes stockés en base (T1–T3 pour trimestre ; S1/S2 mappés en T1/T2 en interne si besoin).
     * Pour compatibilité existante, semestres utilisent S1, S2 — migration étend les enums.
     *
     * @return list<string>
     */
    public function validPeriodCodes(string $scheme): array
    {
        return array_column($this->periodsForScheme($scheme), 'code');
    }

    /**
     * Tous les codes de période acceptés en base.
     *
     * @return list<string>
     */
    public function allValidPeriodCodes(): array
    {
        return ['T1', 'T2', 'T3', 'S1', 'S2'];
    }

    public function isValidPeriodCode(string $term): bool
    {
        return in_array($term, $this->allValidPeriodCodes(), true);
    }

    public function isValidPeriodForStudent(Student $student, string $term): bool
    {
        return in_array($term, $this->validPeriodCodes($this->schemeForStudent($student)), true);
    }

    /**
     * Règle de validation Laravel pour les périodes.
     */
    public function periodValidationRule(): string
    {
        return 'in:'.implode(',', $this->allValidPeriodCodes());
    }

    /**
     * Règle de validation Laravel pour les types d'évaluation.
     */
    public function assessmentTypeValidationRule(): string
    {
        return 'in:'.implode(',', $this->validAssessmentTypes());
    }

    public function periodLabel(string $code, string $scheme): string
    {
        foreach ($this->periodsForScheme($scheme) as $period) {
            if ($period['code'] === $code) {
                return $period['label'];
            }
        }

        return $code;
    }

    /**
     * Types d'évaluation avec libellés FR.
     *
     * @return array<string, string>
     */
    public function assessmentTypeLabels(): array
    {
        return [
            'interrogation' => 'Interrogation',
            'devoir' => 'Devoir',
            'travail_hebdomadaire' => 'Travail hebdomadaire',
            'composition' => 'Composition',
            'examen' => 'Examen',
            'concours' => 'Jeu / Concours',
            'projet' => 'Projet',
            'participation' => 'Participation',
        ];
    }

    /**
     * @return list<string>
     */
    public function validAssessmentTypes(): array
    {
        return array_keys($this->assessmentTypeLabels());
    }
}
