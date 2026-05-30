<?php

namespace Tests\Feature;

use App\Models\ClassSubject;
use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModuleIsolationApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $teacher;
    protected User $accountant;
    protected User $inventoryUser;
    protected User $secretary;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create([
            'name' => 'Administrateur',
            'slug' => 'admin',
            'description' => 'Accès total',
            'permissions' => ['*'],
        ]);
        Role::create([
            'name' => 'Enseignant',
            'slug' => 'teacher',
            'description' => 'Notes',
            'permissions' => ['students:read', 'students:write', 'grades:*'],
        ]);
        Role::create([
            'name' => 'Comptable',
            'slug' => 'accountant',
            'description' => 'Finance',
            'permissions' => ['finance:*', 'payments:*', 'students:read'],
        ]);
        Role::create([
            'name' => 'Gestion inventaire',
            'slug' => 'inventory_manager',
            'description' => 'Inventaire',
            'permissions' => ['inventory:read'],
        ]);
        Role::create([
            'name' => 'Secrétaire',
            'slug' => 'secretary',
            'description' => 'Admissions',
            'permissions' => ['students:*', 'admissions:*', 'communication:write'],
        ]);

        $this->admin = User::create([
            'first_name' => 'Admin',
            'last_name' => 'Root',
            'email' => 'admin@module.test',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'is_active' => true,
        ]);
        $this->teacher = User::create([
            'first_name' => 'Teacher',
            'last_name' => 'One',
            'email' => 'teacher@module.test',
            'password' => bcrypt('password'),
            'role' => 'teacher',
            'is_active' => true,
        ]);
        $this->accountant = User::create([
            'first_name' => 'Accountant',
            'last_name' => 'One',
            'email' => 'accountant@module.test',
            'password' => bcrypt('password'),
            'role' => 'accountant',
            'is_active' => true,
        ]);
        $this->inventoryUser = User::create([
            'first_name' => 'Inventory',
            'last_name' => 'One',
            'email' => 'inventory@module.test',
            'password' => bcrypt('password'),
            'role' => 'inventory_manager',
            'is_active' => true,
        ]);
        $this->secretary = User::create([
            'first_name' => 'Secretary',
            'last_name' => 'One',
            'email' => 'secretary@module.test',
            'password' => bcrypt('password'),
            'role' => 'secretary',
            'is_active' => true,
        ]);
    }

    public function test_settings_module_round_trip(): void
    {
        $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/settings')
            ->assertOk();

        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/settings', [
                'school_name' => 'SmartSchool Test',
                'timezone' => 'Africa/Kinshasa',
            ])
            ->assertOk()
            ->assertJsonPath('school_name', 'SmartSchool Test');
    }

    public function test_students_classes_subjects_modules_in_isolation(): void
    {
        $this->seed(\Database\Seeders\RdcEducationCatalogSeeder::class);
        $gradeLevel = \App\Models\GradeLevel::where('code', 'prim_1')->firstOrFail();

        $classResponse = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/classes', [
                'grade_level_id' => $gradeLevel->id,
                'section' => 'A',
                'academic_year' => '2025-2026',
                'capacity' => 35,
                'teacher_id' => $this->teacher->id,
            ])
            ->assertCreated();

        $classId = $classResponse->json('data.id') ?? $classResponse->json('id');

        $subjectResponse = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/subjects', [
                'name' => 'Mathématiques',
                'code' => 'MATH-01',
                'type' => 'core',
                'is_active' => true,
            ])
            ->assertCreated();
        $subjectId = $subjectResponse->json('id');

        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/students', [
                'matricule' => 'STU-6001',
                'student_number' => 'STU-6001',
                'first_name' => 'Eline',
                'last_name' => 'Kabila',
                'date_of_birth' => '2021-02-10',
                'gender' => 'F',
                'class_id' => $classId,
                'enrollment_date' => '2025-09-01',
                'guardian_name' => 'Jean Kabila',
                'guardian_phone' => '+243800000000',
                'status' => 'active',
            ])
            ->assertCreated();

        $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/students')
            ->assertOk();
        $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/classes')
            ->assertOk();
        $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/subjects')
            ->assertOk();

        // Requis pour module grades
        ClassSubject::create([
            'class_id' => $classId,
            'subject_id' => $subjectId,
            'teacher_id' => $this->teacher->id,
            'coefficient' => 1,
            'hours_per_week' => 2,
            'academic_year' => '2025-2026',
            'is_active' => true,
        ]);
    }

    public function test_admissions_acceptance_creates_student(): void
    {
        $schoolClass = $this->createTestSchoolClass([
            'grade_code' => 'mat_ps',
            'capacity' => 30,
            'teacher_id' => $this->teacher->id,
        ]);

        $store = $this->actingAs($this->secretary, 'sanctum')
            ->postJson('/api/admissions', [
                'student_first_name' => 'Nadia',
                'student_last_name' => 'Mbuyi',
                'student_date_of_birth' => '2020-03-01',
                'student_gender' => 'F',
                'parent_first_name' => 'Paul',
                'parent_last_name' => 'Mbuyi',
                'parent_email' => 'parent@admission.test',
                'parent_phone' => '+243811111111',
                'applied_class_id' => $schoolClass->id,
            ])
            ->assertCreated();

        $admissionId = $store->json('id');

        $this->actingAs($this->secretary, 'sanctum')
            ->putJson("/api/admissions/{$admissionId}", [
                'status' => 'accepted',
                'notes' => 'Accepté',
            ])
            ->assertOk();

        $this->assertDatabaseHas('students', [
            'first_name' => 'Nadia',
            'last_name' => 'Mbuyi',
        ]);
    }

    public function test_finance_module_payment_crud(): void
    {
        $class = $this->createTestSchoolClass([
            'grade_code' => 'prim_5',
            'capacity' => 40,
            'teacher_id' => $this->teacher->id,
        ]);
        $student = Student::create([
            'matricule' => 'STU-7001',
            'student_number' => 'STU-7001',
            'first_name' => 'Finance',
            'last_name' => 'Student',
            'date_of_birth' => '2018-01-01',
            'gender' => 'M',
            'guardian_name' => 'Parent',
            'guardian_phone' => '+243822222222',
            'class_id' => $class->id,
            'enrollment_date' => '2024-09-01',
            'status' => 'active',
            'is_active' => true,
        ]);

        $create = $this->actingAs($this->accountant, 'sanctum')
            ->postJson('/api/payments', [
                'student_id' => $student->id,
                'amount' => 120,
                'currency' => 'USD',
                'type' => 'tuition',
                'payment_method' => 'cash',
                'due_date' => now()->toDateString(),
                'status' => 'completed',
            ])
            ->assertCreated();

        $paymentId = $create->json('id');

        $this->actingAs($this->accountant, 'sanctum')
            ->getJson("/api/payments?student_id={$student->id}")
            ->assertOk();

        $this->actingAs($this->accountant, 'sanctum')
            ->putJson("/api/payments/{$paymentId}", ['status' => 'pending'])
            ->assertOk();
    }

    public function test_grades_module_teacher_class_endpoints(): void
    {
        $class = $this->createTestSchoolClass([
            'grade_code' => 'prim_4',
            'teacher_id' => $this->teacher->id,
        ]);
        $subject = Subject::create([
            'name' => 'Français',
            'code' => 'FR-01',
            'type' => 'core',
            'is_active' => true,
        ]);
        ClassSubject::create([
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'teacher_id' => $this->teacher->id,
            'coefficient' => 1,
            'hours_per_week' => 2,
            'academic_year' => '2025-2026',
            'is_active' => true,
        ]);
        Student::create([
            'matricule' => 'STU-8001',
            'student_number' => 'STU-8001',
            'first_name' => 'Grade',
            'last_name' => 'Student',
            'date_of_birth' => '2017-01-01',
            'gender' => 'M',
            'guardian_name' => 'Parent',
            'guardian_phone' => '+243833333333',
            'class_id' => $class->id,
            'enrollment_date' => '2024-09-01',
            'status' => 'active',
            'is_active' => true,
        ]);

        $this->actingAs($this->teacher, 'sanctum')
            ->getJson('/api/grades/my-classes')
            ->assertOk();

        $this->actingAs($this->teacher, 'sanctum')
            ->getJson("/api/grades/classes/{$class->id}/students")
            ->assertOk();
    }

    public function test_communication_events_inventory_and_reports_modules(): void
    {
        // Communication
        $announcement = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/announcements', [
                'title' => 'Test annonce',
                'message' => 'Contenu annonce',
                'type' => 'info',
                'channels' => ['push'],
                'recipients' => ['all'],
            ])
            ->assertCreated();
        $announcementId = $announcement->json('id');

        $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/announcements/{$announcementId}/read")
            ->assertOk();

        // Events
        $event = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/events', [
                'title' => 'Conseil de classe',
                'description' => 'Réunion trimestrielle',
                'date' => now()->addDay()->toDateTimeString(),
                'location' => 'Salle A',
                'organizer' => 'Direction',
            ])
            ->assertCreated();
        $eventId = $event->json('id');
        $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/events/{$eventId}")
            ->assertOk();

        // Inventory
        $item = $this->actingAs($this->inventoryUser, 'sanctum')
            ->postJson('/api/inventory', [
                'name' => 'Laptop',
                'category' => 'IT',
                'quantity' => 5,
                'location' => 'Magasin',
                'status' => 'in_stock',
            ])
            ->assertCreated();
        $itemId = $item->json('id');
        $this->actingAs($this->inventoryUser, 'sanctum')
            ->getJson("/api/inventory/{$itemId}")
            ->assertOk();

        // Reports
        $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/reports/stats')
            ->assertOk()
            ->assertJsonStructure([
                'totalStudents',
                'totalTeachers',
                'totalParents',
                'pendingApplications',
                'finance',
                'recentActivities',
            ]);
    }
}
