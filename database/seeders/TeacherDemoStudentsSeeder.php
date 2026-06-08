<?php

namespace Database\Seeders;

use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Élèves démo dans les classes titulaires pour effectifs visibles (parcours enseignant).
 */
class TeacherDemoStudentsSeeder extends Seeder
{
    private const ACADEMIC_YEAR = '2025-2026';

    /** @var array<int, array{first_name: string, last_name: string, gender: string}> */
    private const DEMO_NAMES = [
        ['first_name' => 'Amina', 'last_name' => 'Kabila', 'gender' => 'F'],
        ['first_name' => 'Patrick', 'last_name' => 'Mbuyi', 'gender' => 'M'],
        ['first_name' => 'Grace', 'last_name' => 'Tshilombo', 'gender' => 'F'],
        ['first_name' => 'David', 'last_name' => 'Kalala', 'gender' => 'M'],
        ['first_name' => 'Sarah', 'last_name' => 'Mutombo', 'gender' => 'F'],
    ];

    public function run(): void
    {
        $teachers = User::where('role', 'teacher')->where('is_active', true)->get();
        $matriculeCounter = 1;
        $total = 0;

        foreach ($teachers as $teacher) {
            $class = SchoolClass::where('teacher_id', $teacher->id)
                ->where('academic_year', self::ACADEMIC_YEAR)
                ->first();

            if (! $class) {
                continue;
            }

            foreach (self::DEMO_NAMES as $index => $name) {
                $matricule = sprintf('DEMO-%03d', $matriculeCounter++);

                Student::updateOrCreate(
                    ['matricule' => $matricule],
                    [
                        'student_number' => $matricule,
                        'first_name' => $name['first_name'],
                        'last_name' => $name['last_name'],
                        'date_of_birth' => '2014-0'.(($index % 9) + 1).'-15',
                        'gender' => $name['gender'],
                        'guardian_name' => 'Parent '.$name['last_name'],
                        'guardian_phone' => '+243800'.str_pad((string) $matriculeCounter, 6, '0'),
                        'class_id' => $class->id,
                        'academic_year' => self::ACADEMIC_YEAR,
                        'enrollment_date' => '2025-09-01',
                        'status' => 'active',
                        'is_active' => true,
                    ]
                );

                $total++;
            }
        }

        $this->command?->info("✅ TeacherDemoStudentsSeeder: {$total} élèves démo (classes titulaires).");
    }
}
