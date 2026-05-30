<?php

namespace Tests;

use App\Models\EducationCycle;
use App\Models\GradeLevel;
use App\Models\SchoolClass;
use App\Models\StudyOption;
use Database\Seeders\RdcEducationCatalogSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function seedRdcCatalog(): void
    {
        if (EducationCycle::count() === 0) {
            $this->seed(RdcEducationCatalogSeeder::class);
        }
    }

    protected function createTestSchoolClass(array $overrides = []): SchoolClass
    {
        $this->seedRdcCatalog();

        $gradeLevel = GradeLevel::where('code', $overrides['grade_code'] ?? 'prim_1')->firstOrFail();

        $studyOptionId = null;
        if (! empty($overrides['option_code'])) {
            $studyOptionId = StudyOption::where('code', $overrides['option_code'])->value('id');
        }

        return SchoolClass::create([
            'grade_level_id' => $gradeLevel->id,
            'study_option_id' => $studyOptionId,
            'section' => $overrides['section'] ?? 'A',
            'academic_year' => $overrides['academic_year'] ?? '2025-2026',
            'capacity' => $overrides['capacity'] ?? 35,
            'teacher_id' => $overrides['teacher_id'] ?? null,
        ]);
    }
}
