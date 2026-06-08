<?php

namespace App\Services;

use App\Models\SchoolClass;
use App\Models\Subject;
use Illuminate\Support\Collection;

/**
 * Matières autorisées selon le cycle RDC, le niveau et l'option (Humanités).
 */
class SubjectEligibilityService
{
    /** Matières communes Humanités (tronc). */
    private const HUMANITES_BASE = ['MATH', 'FRAN', 'ANGL', 'HIST', 'EPS', 'SVT', 'PHCH'];

    /** Matières par cycle (codes subject). */
    private const BY_CYCLE = [
        'maternel' => ['EPS', 'ARTS', 'MUSI'],
        'primaire' => ['MATH', 'FRAN', 'HIST', 'ANGL', 'EPS', 'ARTS', 'MUSI'],
        'cteb' => ['MATH', 'FRAN', 'HIST', 'ANGL', 'SVT', 'EPS'],
    ];

    /** Renfort par option Humanités. */
    private const BY_OPTION = [
        'opt_sci_mp' => ['MATH', 'PHCH', 'SVT'],
        'opt_sci_cb' => ['PHCH', 'SVT'],
        'opt_lit' => ['FRAN', 'HIST', 'ANGL'],
        'opt_com' => ['MATH', 'FRAN', 'ANGL', 'HIST'],
        'opt_ped' => ['FRAN', 'HIST', 'EPS'],
        'opt_elec' => ['MATH', 'PHCH'],
        'opt_meca' => ['MATH', 'PHCH'],
        'opt_const' => ['MATH', 'PHCH'],
        'opt_nut' => ['SVT'],
    ];

    /**
     * @return Collection<int, Subject>
     */
    public function forClass(SchoolClass $class): Collection
    {
        $class->loadMissing(['gradeLevel.educationCycle', 'studyOption']);

        $codes = $this->allowedCodes($class);

        if ($codes === null) {
            return Subject::query()->where('is_active', true)->orderBy('name')->get();
        }

        if ($codes === []) {
            return collect();
        }

        return Subject::query()
            ->where('is_active', true)
            ->whereIn('code', $codes)
            ->orderBy('name')
            ->get();
    }

    /**
     * @return array<string>|null null = toutes les matières actives
     */
    public function allowedCodes(SchoolClass $class): ?array
    {
        $cycle = $class->gradeLevel?->educationCycle?->code;
        $option = $class->studyOption?->code;

        if ($cycle === 'humanites') {
            $codes = self::HUMANITES_BASE;
            if ($option && isset(self::BY_OPTION[$option])) {
                $codes = array_values(array_unique(array_merge($codes, self::BY_OPTION[$option])));
            }

            return $codes;
        }

        if ($cycle && isset(self::BY_CYCLE[$cycle])) {
            return self::BY_CYCLE[$cycle];
        }

        return null;
    }
}
