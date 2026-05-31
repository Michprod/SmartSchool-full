<?php

namespace Database\Seeders;

use App\Models\GradeLevel;
use App\Models\SchoolClass;
use App\Models\StudyOption;
use App\Models\User;
use App\Support\ClassNameBuilder;
use Illuminate\Database\Seeder;

class SchoolClassesSeeder extends Seeder
{
    public function run(): void
    {
        $teachers = User::where('role', 'teacher')->get();
        $academicYear = '2025-2026';

        $definitions = [
            ['level' => 'mat_ps', 'sections' => ['A', 'B'], 'capacity' => 25],
            ['level' => 'mat_ms', 'sections' => ['A'], 'capacity' => 25],
            ['level' => 'mat_gs', 'sections' => ['A'], 'capacity' => 25],
            ['level' => 'prim_1', 'sections' => ['A', 'B'], 'capacity' => 35],
            ['level' => 'prim_2', 'sections' => ['A'], 'capacity' => 35],
            ['level' => 'prim_3', 'sections' => ['A'], 'capacity' => 35],
            ['level' => 'prim_4', 'sections' => ['A'], 'capacity' => 35],
            ['level' => 'prim_5', 'sections' => ['A'], 'capacity' => 35],
            ['level' => 'prim_6', 'sections' => ['A', 'B'], 'capacity' => 35],
            ['level' => 'cteb_7', 'sections' => ['A', 'B'], 'capacity' => 40],
            ['level' => 'cteb_8', 'sections' => ['A'], 'capacity' => 40],
            ['level' => 'hum_1', 'option' => 'opt_elec', 'sections' => ['A', 'B'], 'capacity' => 40],
            ['level' => 'hum_1', 'option' => 'opt_lit', 'sections' => ['A'], 'capacity' => 40],
            ['level' => 'hum_2', 'option' => 'opt_elec', 'sections' => ['A'], 'capacity' => 40],
            ['level' => 'hum_3', 'option' => 'opt_sci_mp', 'sections' => ['A'], 'capacity' => 40],
            ['level' => 'hum_4', 'option' => 'opt_com', 'sections' => ['A'], 'capacity' => 40],
        ];

        $i = 0;
        foreach ($definitions as $def) {
            $gradeLevel = GradeLevel::with('educationCycle')->where('code', $def['level'])->first();
            if (! $gradeLevel) {
                continue;
            }

            $studyOption = null;
            $studyOptionId = null;
            if (! empty($def['option'])) {
                $studyOption = StudyOption::where('code', $def['option'])->first();
                $studyOptionId = $studyOption?->id;
            }

            foreach ($def['sections'] as $section) {
                $displayName = ClassNameBuilder::build($gradeLevel, $studyOption, $section);

                SchoolClass::updateOrCreate(
                    [
                        'grade_level_id' => $gradeLevel->id,
                        'study_option_id' => $studyOptionId,
                        'section' => $section,
                        'academic_year' => $academicYear,
                    ],
                    [
                        'name' => $displayName,
                        'display_name' => $displayName,
                        'level' => $gradeLevel->educationCycle?->name,
                        'capacity' => $def['capacity'],
                        'teacher_id' => $teachers->isNotEmpty()
                            ? $teachers->get($i % $teachers->count())?->id
                            : null,
                    ]
                );
                $i++;
            }
        }

        $this->command?->info('✅ ' . SchoolClass::count() . ' classes (salles) créées avec nomenclature RDC.');
    }
}
