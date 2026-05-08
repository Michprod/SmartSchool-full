<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Subject;
use App\Models\ClassSubject;
use App\Models\Assessment;
use App\Models\Student;
use App\Models\SchoolClass;
use App\Models\User;
use App\Services\GradeCalculationService;

class GradeSystemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Crée des données de test pour le système de notes
     */
    public function run(): void
    {
        $this->command->info('Creating grade system test data...');

        // 1. Créer les matières
        $subjects = $this->createSubjects();
        $this->command->info('Created ' . count($subjects) . ' subjects');

        // 2. Associer matières aux classes avec coefficients
        $classSubjects = $this->createClassSubjects($subjects);
        $this->command->info('Created class-subject associations');

        // 3. Créer des évaluations (notes)
        $assessments = $this->createAssessments();
        $this->command->info('Created ' . $assessments . ' assessments');

        // 4. Calculer les moyennes
        $this->command->info('Calculating averages...');
        $this->calculateAverages();

        $this->command->info('');
        $this->command->info('==============================================');
        $this->command->info('Grade system test data created successfully!');
        $this->command->info('==============================================');
        $this->command->info('');
        $this->command->info('Test Accounts:');
        $this->command->info('- Teacher (Math): jean.martin@school.com / password');
        $this->command->info('- Teacher (Français): sophie.bernard@school.com / password');
        $this->command->info('- Admin: admin@school.com / password');
        $this->command->info('');
    }

    /**
     * Créer les matières
     */
    private function createSubjects(): array
    {
        $subjectsData = [
            ['name' => 'Mathématiques', 'code' => 'MATH', 'type' => 'core'],
            ['name' => 'Français', 'code' => 'FRAN', 'type' => 'core'],
            ['name' => 'Histoire-Géographie', 'code' => 'HIST', 'type' => 'core'],
            ['name' => 'Sciences de la Vie et de la Terre', 'code' => 'SVT', 'type' => 'core'],
            ['name' => 'Physique-Chimie', 'code' => 'PHCH', 'type' => 'core'],
            ['name' => 'Anglais', 'code' => 'ANGL', 'type' => 'core'],
            ['name' => 'Éducation Physique et Sportive', 'code' => 'EPS', 'type' => 'core'],
            ['name' => 'Arts Plastiques', 'code' => 'ARTS', 'type' => 'elective'],
            ['name' => 'Musique', 'code' => 'MUSI', 'type' => 'elective'],
        ];

        $subjects = [];
        foreach ($subjectsData as $data) {
            $subjects[] = Subject::create($data);
        }

        return $subjects;
    }

    /**
     * Associer les matières aux classes avec coefficients
     */
    private function createClassSubjects(array $subjects): void
    {
        $classes = SchoolClass::all();
        $teachers = User::where('role', 'teacher')->get();

        if ($classes->isEmpty() || $teachers->isEmpty()) {
            $this->command->warn('No classes or teachers found. Run ClassTeacherRelationshipSeeder first.');
            return;
        }

        $coefficients = [
            'MATH' => 4,
            'FRAN' => 4,
            'HIST' => 2,
            'SVT' => 2,
            'PHCH' => 2,
            'ANGL' => 2,
            'EPS' => 1,
            'ARTS' => 1,
            'MUSI' => 1,
        ];

        $academicYear = '2025-2026';

        foreach ($classes as $index => $class) {
            // Assigner un professeur différent pour chaque matière
            foreach ($subjects as $subIndex => $subject) {
                $teacher = $teachers[($index + $subIndex) % $teachers->count()];

                ClassSubject::create([
                    'class_id' => $class->id,
                    'subject_id' => $subject->id,
                    'teacher_id' => $teacher->id,
                    'coefficient' => $coefficients[$subject->code] ?? 1,
                    'hours_per_week' => rand(2, 6),
                    'academic_year' => $academicYear,
                    'is_active' => true,
                ]);
            }
        }
    }

    /**
     * Créer des évaluations pour les élèves
     */
    private function createAssessments(): int
    {
        $students = Student::all();
        $classSubjects = ClassSubject::where('is_active', true)->get();
        $academicYear = '2025-2026';

        $count = 0;

        foreach ($students as $student) {
            // Pour chaque matière de la classe de l'élève
            $studentClassSubjects = $classSubjects->where('class_id', $student->class_id);

            foreach ($studentClassSubjects as $classSubject) {
                // Créer 2-4 évaluations par trimestre
                foreach (['T1'] as $term) { // T1 seulement pour le test
                    $numAssessments = rand(2, 4);

                    for ($i = 0; $i < $numAssessments; $i++) {
                        $types = ['interrogation', 'devoir', 'composition'];
                        $type = $types[array_rand($types)];

                        // Générer une note réaliste (entre 5 et 19)
                        $score = $this->generateRealisticScore($student->id, $classSubject->subject_id);

                        $maxScore = match ($type) {
                            'interrogation' => 10,
                            'devoir' => 20,
                            'composition' => 20,
                            default => 20,
                        };

                        $coefficient = match ($type) {
                            'interrogation' => 1,
                            'devoir' => 2,
                            'composition' => 3,
                            default => 1,
                        };

                        Assessment::create([
                            'student_id' => $student->id,
                            'subject_id' => $classSubject->subject_id,
                            'teacher_id' => $classSubject->teacher_id,
                            'class_id' => $student->class_id,
                            'type' => $type,
                            'term' => $term,
                            'academic_year' => $academicYear,
                            'score' => $score,
                            'max_score' => $maxScore,
                            'coefficient' => $coefficient,
                            'title' => $this->generateAssessmentTitle($type, $i + 1, $classSubject->subject->name),
                            'comment' => $this->generateComment($score, $maxScore),
                            'date' => $this->generateDate($term),
                            'is_published' => true,
                        ]);

                        $count++;
                    }
                }
            }
        }

        return $count;
    }

    /**
     * Générer une note réaliste
     */
    private function generateRealisticScore(int $studentId, int $subjectId): float
    {
        // Utiliser un algorithme pseudo-aléatoire basé sur l'ID pour des résultats cohérents
        $base = (($studentId * 7 + $subjectId * 13) % 100) / 100;
        $score = 8 + ($base * 11); // Entre 8 et 19

        // Ajouter un peu de variation
        $variation = (rand(-10, 10) / 10);
        $score = max(5, min(19.5, $score + $variation));

        return round($score, 2);
    }

    /**
     * Générer un titre pour l'évaluation
     */
    private function generateAssessmentTitle(string $type, int $number, string $subject): string
    {
        return match ($type) {
            'interrogation' => "Interrogation #{$number} - {$subject}",
            'devoir' => "Devoir #{$number} - {$subject}",
            'composition' => "Composition - {$subject}",
            default => "Évaluation #{$number} - {$subject}",
        };
    }

    /**
     * Générer un commentaire basé sur la note
     */
    private function generateComment(float $score, float $maxScore): ?string
    {
        $percentage = ($score / $maxScore) * 100;

        if ($percentage >= 90) {
            $comments = [
                'Excellent travail !',
                'Très bonne maîtrise du sujet.',
                'Parfait, continue ainsi !',
            ];
        } elseif ($percentage >= 80) {
            $comments = [
                'Bon travail.',
                'Bonne compréhension.',
                'Quelques petites erreurs à corriger.',
            ];
        } elseif ($percentage >= 60) {
            $comments = [
                'Résultat satisfaisant.',
                'Des progrès à faire.',
                'Peut mieux faire.',
            ];
        } else {
            $comments = [
                'Insuffisant. Révision nécessaire.',
                'Besoin d\'un soutien accru.',
                'Doit travailler davantage.',
            ];
        }

        return rand(0, 2) === 0 ? $comments[array_rand($comments)] : null;
    }

    /**
     * Générer une date réaliste pour le trimestre
     */
    private function generateDate(string $term): string
    {
        return match ($term) {
            'T1' => '2025-10-' . rand(1, 30),
            'T2' => '2026-01-' . rand(1, 30),
            'T3' => '2026-04-' . rand(1, 30),
            default => '2025-10-' . rand(1, 30),
        };
    }

    /**
     * Calculer les moyennes pour toutes les classes
     */
    private function calculateAverages(): void
    {
        $service = new GradeCalculationService();
        $classes = SchoolClass::all();
        $academicYear = '2025-2026';

        foreach ($classes as $class) {
            $this->command->info("Calculating averages for class: {$class->name}");
            $service->calculateClassAverages($class->id, 'T1', $academicYear);
        }
    }
}
