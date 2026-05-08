<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;

class ClassTeacherRelationshipSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Exemple de données pour démontrer les relations classe-professeur-élève
     */
    public function run(): void
    {
        // Créer des professeurs
        $teachers = [
            ['first_name' => 'Marie', 'last_name' => 'Dubois', 'email' => 'marie.dubois@school.com', 'role' => 'teacher'],
            ['first_name' => 'Jean', 'last_name' => 'Martin', 'email' => 'jean.martin@school.com', 'role' => 'teacher'],
            ['first_name' => 'Sophie', 'last_name' => 'Bernard', 'email' => 'sophie.bernard@school.com', 'role' => 'teacher'],
            ['first_name' => 'Pierre', 'last_name' => 'Petit', 'email' => 'pierre.petit@school.com', 'role' => 'teacher'],
            ['first_name' => 'Claire', 'last_name' => 'Robert', 'email' => 'claire.robert@school.com', 'role' => 'teacher'],
        ];

        $teacherModels = [];
        foreach ($teachers as $teacherData) {
            $teacherData['password'] = bcrypt('password');
            $teacherModels[] = User::create($teacherData);
        }

        [$marie, $jean, $sophie, $pierre, $claire] = $teacherModels;

        // Créer des classes avec leurs professeurs titulaires
        $classes = [
            [
                'name' => '6ème A',
                'level' => '6ème',
                'capacity' => 30,
                'principal_teacher' => $marie,
                'subject_teachers' => [
                    ['teacher' => $jean, 'subject' => 'Mathématiques'],
                    ['teacher' => $sophie, 'subject' => 'Français'],
                    ['teacher' => $pierre, 'subject' => 'Histoire-Géographie'],
                ]
            ],
            [
                'name' => '5ème B',
                'level' => '5ème',
                'capacity' => 28,
                'principal_teacher' => $jean,
                'subject_teachers' => [
                    ['teacher' => $marie, 'subject' => 'Mathématiques'],
                    ['teacher' => $claire, 'subject' => 'Français'],
                    ['teacher' => $pierre, 'subject' => 'SVT'],
                ]
            ],
            [
                'name' => '4ème A',
                'level' => '4ème',
                'capacity' => 32,
                'principal_teacher' => $sophie,
                'subject_teachers' => [
                    ['teacher' => $jean, 'subject' => 'Mathématiques'],
                    ['teacher' => $marie, 'subject' => 'Physique-Chimie'],
                    ['teacher' => $pierre, 'subject' => 'Histoire-Géographie'],
                    ['teacher' => $claire, 'subject' => 'Anglais'],
                ]
            ],
        ];

        foreach ($classes as $classData) {
            $class = SchoolClass::create([
                'name' => $classData['name'],
                'level' => $classData['level'],
                'capacity' => $classData['capacity'],
                'teacher_id' => $classData['principal_teacher']->id,
            ]);

            // Attacher les professeurs de matières
            foreach ($classData['subject_teachers'] as $subjectTeacher) {
                $class->subjectTeachers()->attach($subjectTeacher['teacher']->id, [
                    'subject' => $subjectTeacher['subject'],
                    'academic_year' => '2025-2026',
                    'schedule' => json_encode([
                        'monday' => ['08:00-10:00'],
                        'wednesday' => ['14:00-16:00'],
                    ]),
                    'is_active' => true,
                ]);
            }

            // Créer quelques élèves pour chaque classe
            for ($i = 1; $i <= 5; $i++) {
                Student::create([
                    'matricule' => 'MAT-' . $class->id . '-' . str_pad($i, 3, '0', STR_PAD_LEFT),
                    'student_number' => 'STU-' . $class->id . '-' . str_pad($i, 3, '0', STR_PAD_LEFT),
                    'first_name' => 'Élève' . $i,
                    'last_name' => 'Test-' . $class->name,
                    'date_of_birth' => now()->subYears(12 + rand(0, 3)),
                    'gender' => ['M', 'F'][rand(0, 1)],
                    'class_id' => $class->id,
                    'guardian_name' => 'Parent ' . $i,
                    'guardian_phone' => '012345678' . $i,
                    'enrollment_date' => now(),
                ]);
            }
        }

        $this->command->info('Données de test créées avec succès !');
        $this->command->info('');
        $this->command->info('Résumé des relations créées:');
        $this->command->info('- 5 professeurs créés');
        $this->command->info('- 3 classes avec professeurs titulaires et matières');
        $this->command->info('- 15 élèves répartis dans les classes');
        $this->command->info('');
        $this->command->info('Exemples de requêtes à tester:');
        $this->command->info('- User::find(1)->principalClass (classe dont Marie est titulaire)');
        $this->command->info('- User::find(1)->teachingClasses (classes où Marie enseigne)');
        $this->command->info('- SchoolClass::find(1)->subjectTeachers (professeurs de matières)');
        $this->command->info('- Student::find(1)->allClassTeachers (tous les professeurs de l\'élève)');
    }
}
