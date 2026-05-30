<?php

namespace App\Support;

use App\Models\GradeLevel;
use App\Models\StudyOption;

class ClassNameBuilder
{
    public static function build(GradeLevel $gradeLevel, ?StudyOption $studyOption, string $section): string
    {
        $cycleCode = $gradeLevel->educationCycle?->code ?? '';
        $normalizedSection = strtoupper(trim($section));

        if ($cycleCode === 'humanites' && $studyOption) {
            return trim(sprintf(
                '%s %s %s',
                $gradeLevel->official_name,
                $studyOption->name,
                $normalizedSection
            ));
        }

        return trim(sprintf('%s %s', $gradeLevel->official_name, $normalizedSection));
    }
}
