<?php

namespace Tests\Feature;

use App\Models\ClassSubject;
use App\Models\Payment;
use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentAverage;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GroupIntegrationApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $teacher;
    protected User $accountant;
    protected User $secretary;
    protected User $inventoryManager;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'Administrateur', 'slug' => 'admin', 'description' => 'All', 'permissions' => ['*']]);
        Role::create(['name' => 'Teacher', 'slug' => 'teacher', 'description' => 'Grades', 'permissions' => ['students:read', 'students:write', 'grades:*']]);
        Role::create(['name' => 'Accountant', 'slug' => 'accountant', 'description' => 'Finance', 'permissions' => ['finance:*', 'payments:*', 'students:read']]);
        Role::create(['name' => 'Secretary', 'slug' => 'secretary', 'description' => 'Admissions', 'permissions' => ['students:*', 'admissions:*', 'communication:write']]);
        Role::create(['name' => 'Inventory', 'slug' => 'inventory_manager', 'description' => 'Inventory', 'permissions' => ['inventory:read', 'settings:read', 'settings:write']]);

        $this->admin = User::create([
            'first_name' => 'Admin',
            'last_name' => 'User',
            'email' => 'admin@integration.test',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'is_active' => true,
        ]);
        $this->teacher = User::create([
            'first_name' => 'Teacher',
            'last_name' => 'User',
            'email' => 'teacher@integration.test',
            'password' => bcrypt('password'),
            'role' => 'teacher',
            'is_active' => true,
        ]);
        $this->accountant = User::create([
            'first_name' => 'Accountant',
            'last_name' => 'User',
            'email' => 'accountant@integration.test',
            'password' => bcrypt('password'),
            'role' => 'accountant',
            'is_active' => true,
        ]);
        $this->secretary = User::create([
            'first_name' => 'Secretary',
            'last_name' => 'User',
            'email' => 'secretary@integration.test',
            'password' => bcrypt('password'),
            'role' => 'secretary',
            'is_active' => true,
        ]);
        $this->inventoryManager = User::create([
            'first_name' => 'Inventory',
            'last_name' => 'Manager',
            'email' => 'inventory@integration.test',
            'password' => bcrypt('password'),
            'role' => 'inventory_manager',
            'is_active' => true,
        ]);
    }

    public function test_group_a_students_admissions_classes_flow(): void
    {
        $schoolClass = $this->createTestSchoolClass([
            'grade_code' => 'prim_2',
            'teacher_id' => $this->teacher->id,
        ]);

        $admission = $this->actingAs($this->secretary, 'sanctum')
            ->postJson('/api/admissions', [
                'student_first_name' => 'Aline',
                'student_last_name' => 'Lukusa',
                'student_date_of_birth' => '2019-01-01',
                'student_gender' => 'F',
                'parent_first_name' => 'Paul',
                'parent_last_name' => 'Lukusa',
                'parent_email' => 'paul@groupa.test',
                'parent_phone' => '+243811111111',
                'applied_class_id' => $schoolClass->id,
            ])
            ->assertCreated();

        $admissionId = $admission->json('id');

        $this->actingAs($this->secretary, 'sanctum')
            ->putJson("/api/admissions/{$admissionId}", ['status' => 'accepted'])
            ->assertOk();

        $student = Student::where('first_name', 'Aline')->where('last_name', 'Lukusa')->first();
        $this->assertNotNull($student);

        $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/students/{$student->id}")
            ->assertOk()
            ->assertJsonPath('data.first_name', 'Aline');
    }

    public function test_group_b_students_finance_reports_flow(): void
    {
        $class = $this->createTestSchoolClass([
            'grade_code' => 'prim_3',
            'teacher_id' => $this->teacher->id,
        ]);
        $student = Student::create([
            'matricule' => 'STU-GB-001',
            'student_number' => 'STU-GB-001',
            'first_name' => 'Merveille',
            'last_name' => 'Banza',
            'date_of_birth' => '2018-02-10',
            'gender' => 'F',
            'guardian_name' => 'Parent Banza',
            'guardian_phone' => '+243822222222',
            'class_id' => $class->id,
            'enrollment_date' => '2024-09-01',
            'status' => 'active',
            'is_active' => true,
        ]);

        $this->actingAs($this->accountant, 'sanctum')
            ->postJson('/api/payments', [
                'student_id' => $student->id,
                'amount' => 150,
                'currency' => 'USD',
                'type' => 'tuition',
                'payment_method' => 'cash',
                'due_date' => now()->toDateString(),
                'status' => 'completed',
            ])
            ->assertCreated();

        $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/reports/stats')
            ->assertOk()
            ->assertJsonPath('finance.totalRevenue.usd', 150);
    }

    public function test_group_c_students_grades_reports_flow(): void
    {
        $class = $this->createTestSchoolClass([
            'grade_code' => 'prim_4',
            'teacher_id' => $this->teacher->id,
        ]);
        $subject = Subject::create([
            'name' => 'Sciences',
            'code' => 'SCI-01',
            'type' => 'core',
            'is_active' => true,
        ]);
        $student = Student::create([
            'matricule' => 'STU-GC-001',
            'student_number' => 'STU-GC-001',
            'first_name' => 'Junior',
            'last_name' => 'Mafuta',
            'date_of_birth' => '2017-05-03',
            'gender' => 'M',
            'guardian_name' => 'Parent Mafuta',
            'guardian_phone' => '+243833333333',
            'class_id' => $class->id,
            'enrollment_date' => '2024-09-01',
            'status' => 'active',
            'is_active' => true,
            'academic_year' => '2025-2026',
        ]);

        ClassSubject::create([
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'teacher_id' => $this->teacher->id,
            'coefficient' => 2,
            'hours_per_week' => 3,
            'academic_year' => '2025-2026',
            'is_active' => true,
        ]);

        StudentAverage::create([
            'student_id' => $student->id,
            'subject_id' => $subject->id,
            'class_id' => $class->id,
            'term' => 'T1',
            'academic_year' => '2025-2026',
            'average_score' => 14.5,
            'total_coefficient' => 2,
            'assessments_count' => 3,
            'general_average' => 14.5,
            'class_rank' => 1,
            'total_students' => 1,
            'appreciation' => 'Bien',
            'calculated_at' => now(),
        ]);

        $this->actingAs($this->teacher, 'sanctum')
            ->getJson("/api/grades/students/{$student->id}/averages?term=T1&academic_year=2025-2026")
            ->assertOk()
            ->assertJsonPath('general_average', '14.50');
    }

    public function test_group_d_communication_events_inventory_settings_flow(): void
    {
        $announcement = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/announcements', [
                'title' => 'Annonce intégration',
                'message' => 'Test groupe D',
                'type' => 'info',
                'channels' => ['push'],
                'recipients' => ['all'],
            ])
            ->assertCreated();

        $announcementId = $announcement->json('id');
        $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/announcements/{$announcementId}/read")
            ->assertOk();

        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/events', [
                'title' => 'Réunion intégration',
                'description' => 'Coordination modules',
                'date' => now()->addDays(2)->toDateTimeString(),
                'location' => 'Salle B',
                'organizer' => 'Direction',
            ])
            ->assertCreated();

        $item = $this->actingAs($this->inventoryManager, 'sanctum')
            ->postJson('/api/inventory', [
                'name' => 'Projecteur',
                'category' => 'Pédagogique',
                'quantity' => 2,
                'location' => 'Magasin 1',
                'status' => 'in_stock',
            ])
            ->assertCreated();

        $this->actingAs($this->inventoryManager, 'sanctum')
            ->getJson('/api/settings')
            ->assertOk();

        $this->actingAs($this->inventoryManager, 'sanctum')
            ->postJson('/api/settings', [
                'integration_marker' => 'ok',
                'item_id' => $item->json('id'),
            ])
            ->assertOk()
            ->assertJsonPath('integration_marker', 'ok');
    }
}
