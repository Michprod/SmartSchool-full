<?php

namespace Database\Seeders;

use App\Models\ClassSubject;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Affectations matières ↔ professeurs pour le parcours enseignant (bootstrap prod).
 */
class ClassAssignmentsSeeder extends Seeder
{
    private const ACADEMIC_YEAR = '2025-2026';

    /** @var array<string, string> email enseignant => code matière */
    private const TEACHER_SUBJECTS = [
        'prof.kabongo@smartschool.cd' => 'MATH',
        'prof.mwamba@smartschool.cd' => 'FRAN',
        'prof.lumumba@smartschool.cd' => 'SVT',
        'prof.kasongo@smartschool.cd' => 'ANGL',
        'prof.ngalula@smartschool.cd' => 'HIST',
    ];

    /** Classes cibles : code niveau + section */
    private const TARGET_CLASSES = [
        ['level' => 'prim_1', 'section' => 'A'],
        ['level' => 'prim_1', 'section' => 'B'],
        ['level' => 'prim_6', 'section' => 'A'],
        ['level' => 'prim_6', 'section' => 'B'],
        ['level' => 'cteb_7', 'section' => 'A'],
        ['level' => 'cteb_7', 'section' => 'B'],
        ['level' => 'cteb_8', 'section' => 'A'],
        ['level' => 'hum_1', 'section' => 'A', 'option' => 'opt_elec'],
    ];

    public function run(): void
    {
        $subjects = Subject::whereIn('code', array_values(self::TEACHER_SUBJECTS))
            ->get()
            ->keyBy('code');

        if ($subjects->isEmpty()) {
            $this->command?->warn('ClassAssignmentsSeeder: aucune matière trouvée.');

            return;
        }

        $kasongo = User::where('email', 'prof.kasongo@smartschool.cd')->first();
        $prim6A = $this->findClass('prim_6', 'A');

        if ($kasongo && $prim6A && $prim6A->teacher_id !== $kasongo->id) {
            $prim6A->update(['teacher_id' => $kasongo->id]);
        }

        $created = 0;

        foreach (self::TARGET_CLASSES as $def) {
            $class = $this->findClass(
                $def['level'],
                $def['section'],
                $def['option'] ?? null
            );

            if (! $class) {
                continue;
            }

            foreach (self::TEACHER_SUBJECTS as $email => $subjectCode) {
                $teacher = User::where('email', $email)->first();
                $subject = $subjects->get($subjectCode);

                if (! $teacher || ! $subject) {
                    continue;
                }

                ClassSubject::firstOrCreate(
                    [
                        'class_id' => $class->id,
                        'subject_id' => $subject->id,
                        'academic_year' => self::ACADEMIC_YEAR,
                    ],
                    [
                        'teacher_id' => $teacher->id,
                        'coefficient' => 3,
                        'hours_per_week' => 4,
                        'is_active' => true,
                        'schedule' => [
                            'monday' => [['start' => '08:00', 'end' => '09:00', 'room' => 'Salle '.$class->section]],
                        ],
                    ]
                );

                $created++;
            }
        }

        $this->command?->info('✅ ClassAssignmentsSeeder: '.$created.' affectations matière (idempotent).');
    }

    private function findClass(string $levelCode, string $section, ?string $optionCode = null): ?SchoolClass
    {
        $query = SchoolClass::query()
            ->where('academic_year', self::ACADEMIC_YEAR)
            ->where('section', $section)
            ->whereHas('gradeLevel', fn ($q) => $q->where('code', $levelCode));

        if ($optionCode) {
            $query->whereHas('studyOption', fn ($q) => $q->where('code', $optionCode));
        } else {
            $query->whereNull('study_option_id');
        }

        return $query->first();
    }
}
