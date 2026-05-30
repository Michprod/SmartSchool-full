<?php

namespace Database\Seeders;

use App\Models\GradeLevel;
use App\Models\SchoolClass;
use App\Models\StudyOption;
use Illuminate\Database\Seeder;

class MigrateLegacySchoolClassesSeeder extends Seeder
{
    /**
     * Map legacy class names to grade_level codes (+ optional study option).
     */
    protected array $nameMap = [
        '1ère Maternelle' => ['mat_ps'],
        '2ème Maternelle' => ['mat_ms'],
        '3ème Maternelle' => ['mat_gs'],
        '1ère Primaire' => ['prim_1'],
        '2ème Primaire' => ['prim_2'],
        '3ème Primaire' => ['prim_3'],
        '4ème Primaire' => ['prim_4'],
        '5ème Primaire' => ['prim_5'],
        '6ème Primaire' => ['prim_6'],
        '7ème Éducation de Base' => ['cteb_7'],
        '8ème Éducation de Base' => ['cteb_8'],
        '1ère Humanités' => ['hum_1'],
        '2ème Humanités' => ['hum_2'],
        '3ème Humanités' => ['hum_3'],
        '4ème Humanités' => ['hum_4'],
        '1ère année des Humanités' => ['hum_1'],
        '2ème année des Humanités' => ['hum_2'],
        '3ème année des Humanités' => ['hum_3'],
        '4ème année des Humanités' => ['hum_4'],
    ];

    public function run(): void
    {
        $defaultOption = StudyOption::where('code', 'opt_lit')->first();

        foreach (SchoolClass::whereNull('grade_level_id')->get() as $class) {
            $baseName = preg_replace('/\s+[A-Z]$/', '', $class->name) ?: $class->name;
            $section = 'A';
            if (preg_match('/\s+([A-Z])$/', $class->name, $m)) {
                $section = $m[1];
                $baseName = trim(preg_replace('/\s+[A-Z]$/', '', $class->name));
            }

            $codes = $this->nameMap[$baseName] ?? $this->nameMap[$class->name] ?? null;
            if (! $codes) {
                $codes = $this->guessFromLevel($class->level, $class->name);
            }

            if (! $codes) {
                continue;
            }

            $gradeLevel = GradeLevel::where('code', $codes[0])->first();
            if (! $gradeLevel) {
                continue;
            }

            $studyOptionId = null;
            if ($gradeLevel->educationCycle?->code === 'humanites' && $defaultOption) {
                $studyOptionId = $defaultOption->id;
            }

            $class->update([
                'grade_level_id' => $gradeLevel->id,
                'study_option_id' => $studyOptionId,
                'section' => $section,
                'academic_year' => $class->academic_year ?: '2025-2026',
            ]);
        }

        $this->command?->info('✅ Classes legacy migrées vers le référentiel RDC.');
    }

    protected function guessFromLevel(?string $level, string $name): ?array
    {
        return match ($level) {
            'Maternelle' => ['mat_ps'],
            'Primaire' => ['prim_1'],
            'Éducation de Base' => str_contains($name, '8') ? ['cteb_8'] : ['cteb_7'],
            'Humanités' => ['hum_1'],
            default => null,
        };
    }
}
