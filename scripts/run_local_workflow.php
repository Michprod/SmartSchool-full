<?php

/**
 * Exécute le flux métier complet en local (sans UI).
 * Usage: php scripts/run_local_workflow.php
 */

use App\Models\Assessment;
use App\Models\ClassSubject;
use App\Models\EvaluationSession;
use App\Models\FeeType;
use App\Models\GradeLevel;
use App\Models\Payment;
use App\Models\PersonnelConfigItem;
use App\Models\Personnel;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use App\Services\GradeCalculationService;
use App\Services\PersonnelService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$academicYear = '2025-2026';
$term = 'T1';

echo "=== SmartSchool — flux local complet ===\n\n";

echo "1) Bootstrap base (ProductionBootstrapSeeder)...\n";
Artisan::call('migrate:fresh', [
    '--force' => true,
    '--seeder' => 'Database\\Seeders\\ProductionBootstrapSeeder',
]);
echo trim(Artisan::output())."\n";

$admin = User::where('email', 'admin@smartschool.cd')->firstOrFail();
echo "   Admin OK: {$admin->email}\n";

$setupOk = DB::table('settings')->where('key', 'school_settings')->exists()
    && PersonnelConfigItem::where('is_active', true)->count() >= 3
    && Subject::count() >= 1
    && FeeType::where('is_active', true)->count() >= 1;
echo "   Référentiels: ".($setupOk ? 'OK' : 'KO')."\n\n";

echo "2) Personnel enseignant dédié au flux...\n";
/** @var PersonnelService $personnelService */
$personnelService = app(PersonnelService::class);
$teacherEmail = 'flux.teacher@smartschool.cd';
$teacherUser = User::where('email', $teacherEmail)->first();
if (! $teacherUser) {
    $teacher = $personnelService->createWithUser([
        'staff_type' => 'teacher',
        'first_name' => 'Flux',
        'last_name' => 'Teacher',
        'email' => $teacherEmail,
        'password' => 'password',
        'department' => 'Sciences',
        'job_title' => 'Professeur de Mathématiques',
        'workload_hours' => 20,
        'is_active' => true,
    ]);
    $teacherUser = $teacher->user;
}
echo "   Enseignant: {$teacherUser->email} (id {$teacherUser->id})\n\n";

echo "3) Classe Primaire 6...\n";
$gradeLevel = GradeLevel::where('code', 'prim_6')->firstOrFail();
$class = SchoolClass::firstOrCreate(
    [
        'grade_level_id' => $gradeLevel->id,
        'section' => 'FLUX',
        'academic_year' => $academicYear,
        'study_option_id' => null,
    ],
    [
        'name' => '6ème Primaire FLUX',
        'display_name' => '6ème Primaire FLUX',
        'level' => $gradeLevel->educationCycle?->name,
        'capacity' => 35,
        'teacher_id' => $teacherUser->id,
    ]
);
$class->update(['teacher_id' => $teacherUser->id]);
echo "   Classe: {$class->display_name} (id {$class->id})\n\n";

echo "4) Affectation matière MATH...\n";
$math = Subject::where('code', 'MATH')->firstOrFail();
$classSubject = ClassSubject::firstOrCreate(
    [
        'class_id' => $class->id,
        'subject_id' => $math->id,
        'academic_year' => $academicYear,
    ],
    [
        'teacher_id' => $teacherUser->id,
        'coefficient' => 4,
        'hours_per_week' => 5,
        'is_active' => true,
    ]
);
$classSubject->update(['teacher_id' => $teacherUser->id, 'is_active' => true]);
echo "   ClassSubject id {$classSubject->id}\n\n";

echo "5) Élève...\n";
$student = Student::updateOrCreate(
    ['matricule' => 'FLUX-001'],
    [
        'student_number' => 'FLUX-001',
        'first_name' => 'Amina',
        'last_name' => 'FluxTest',
        'date_of_birth' => '2014-03-15',
        'gender' => 'F',
        'guardian_name' => 'Parent Flux',
        'guardian_phone' => '+243800000099',
        'guardian_email' => 'parent.flux@test.cd',
        'class_id' => $class->id,
        'academic_year' => $academicYear,
        'enrollment_date' => '2025-09-01',
        'status' => 'active',
        'is_active' => true,
    ]
);
echo "   Élève: {$student->first_name} {$student->last_name} (id {$student->id})\n\n";

echo "6) Session d'évaluation + note...\n";
$session = EvaluationSession::firstOrCreate(
    [
        'class_id' => $class->id,
        'subject_id' => $math->id,
        'term' => $term,
        'academic_year' => $academicYear,
        'title' => 'Interro flux local',
    ],
    [
        'teacher_id' => $teacherUser->id,
        'type' => 'interrogation',
        'date' => '2025-10-15',
        'max_score' => 10,
        'coefficient' => 1,
        'is_published' => true,
    ]
);
$session->update(['is_published' => true]);

Assessment::updateOrCreate(
    [
        'student_id' => $student->id,
        'evaluation_session_id' => $session->id,
    ],
    [
        'subject_id' => $math->id,
        'teacher_id' => $teacherUser->id,
        'class_id' => $class->id,
        'type' => 'interrogation',
        'term' => $term,
        'academic_year' => $academicYear,
        'score' => 8,
        'max_score' => 10,
        'coefficient' => 1,
        'title' => $session->title,
        'date' => $session->date,
        'is_published' => true,
    ]
);
echo "   Note: 8/10 (interrogation T1)\n\n";

echo "7) Calcul moyennes...\n";
$gradeService = app(GradeCalculationService::class);
$gradeService->calculateAndSaveStudentAverages($student->id, $term, $academicYear);
$gradeService->calculateClassRanks($class->id, $term, $academicYear);
$avg = \App\Models\StudentAverage::where('student_id', $student->id)
    ->where('term', $term)
    ->where('academic_year', $academicYear)
    ->whereNotNull('general_average')
    ->value('general_average');
echo "   Moyenne générale: ".($avg ?? 'N/A')."\n\n";

echo "8) Paiement frais scolaires...\n";
$feeType = FeeType::where('code', 'tuition')->firstOrFail();
Payment::create([
    'student_id' => $student->id,
    'type' => $feeType->code,
    'amount' => 500,
    'currency' => 'USD',
    'status' => 'completed',
    'payment_method' => 'cash',
    'due_date' => now()->toDateString(),
    'paid_at' => now(),
    'description' => 'Frais T1 — flux local',
]);
echo "   Paiement tuition 500 USD enregistré\n\n";

echo "9) Bulletin classe...\n";
$bulletinStudents = Student::where('class_id', $class->id)->count();
$averagesCount = \App\Models\StudentAverage::where('class_id', $class->id)
    ->where('term', $term)
    ->where('academic_year', $academicYear)
    ->whereNotNull('general_average')
    ->count();
echo "   Élèves classe: {$bulletinStudents} | Moyennes calculées: {$averagesCount}\n\n";

echo "=== TERMINÉ ===\n";
echo "Connexion UI: admin@smartschool.cd / password\n";
echo "Enseignant flux: {$teacherEmail} / password\n";
echo "Classe id: {$class->id} | Élève matricule: FLUX-001\n";
echo "Vérifier dans l'UI:\n";
echo "  - /configuration (checks verts)\n";
echo "  - /personnel (Flux Teacher)\n";
echo "  - /classes (6ème Primaire FLUX)\n";
echo "  - /students (Amina FluxTest)\n";
echo "  - /grades > Bulletin\n";
echo "  - /finance (paiement tuition)\n";
