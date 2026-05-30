<?php

namespace Tests\Feature;

use App\Models\ClassSubject;
use App\Models\Role;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceUserDisciplineTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $accountant;
    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'Admin', 'slug' => 'admin', 'permissions' => ['*']]);
        Role::create(['name' => 'Accountant', 'slug' => 'accountant', 'permissions' => ['finance:*', 'students:read']]);
        Role::create(['name' => 'Teacher', 'slug' => 'teacher', 'permissions' => ['discipline:write', 'students:read', 'grades:*']]);

        $this->admin = User::create([
            'first_name' => 'Admin',
            'last_name' => 'Test',
            'email' => 'admin.finance@test',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->accountant = User::create([
            'first_name' => 'Compta',
            'last_name' => 'Test',
            'email' => 'accountant@test',
            'password' => bcrypt('password'),
            'role' => 'accountant',
            'is_active' => true,
        ]);

        $teacher = User::create([
            'first_name' => 'Teach',
            'last_name' => 'One',
            'email' => 'teach@test',
            'password' => bcrypt('password'),
            'role' => 'teacher',
            'is_active' => true,
        ]);

        $class = $this->createTestSchoolClass(['grade_code' => 'prim_6', 'teacher_id' => $teacher->id]);
        $subject = Subject::create(['name' => 'Math', 'code' => 'MATH', 'type' => 'core']);
        ClassSubject::create([
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'coefficient' => 2,
            'hours_per_week' => 4,
            'academic_year' => '2025-2026',
            'is_active' => true,
        ]);

        $this->student = Student::create([
            'matricule' => 'FIN-001',
            'student_number' => 'FIN-001',
            'first_name' => 'Elie',
            'last_name' => 'Kanku',
            'date_of_birth' => '2015-01-01',
            'gender' => 'M',
            'guardian_name' => 'Guardian',
            'guardian_phone' => '+243800000001',
            'class_id' => $class->id,
            'academic_year' => '2025-2026',
            'enrollment_date' => '2025-09-01',
            'status' => 'active',
            'is_active' => true,
        ]);
    }

    public function test_can_manage_finance_config_and_installments(): void
    {
        $feeType = $this->actingAs($this->accountant, 'sanctum')
            ->postJson('/api/finance/config/fee-types', ['code' => 'lab', 'label' => 'Frais laboratoire'])
            ->assertCreated()
            ->json('id');

        $plan = $this->actingAs($this->accountant, 'sanctum')
            ->postJson('/api/payment-plans', [
                'student_id' => $this->student->id,
                'fee_type_id' => $feeType,
                'academic_year' => '2025-2026',
                'currency' => 'USD',
                'total_amount' => 300,
                'installments' => [
                    ['amount_due' => 100, 'due_date' => now()->toDateString()],
                    ['amount_due' => 200, 'due_date' => now()->addMonth()->toDateString()],
                ],
            ])
            ->assertCreated()
            ->json();

        $firstInstallmentId = $plan['installments'][0]['id'];

        $this->actingAs($this->accountant, 'sanctum')
            ->postJson("/api/payment-installments/{$firstInstallmentId}/pay", [
                'amount' => 100,
                'payment_method' => 'cash',
            ])
            ->assertOk();
    }

    public function test_user_creation_accepts_professional_fields(): void
    {
        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/users', [
                'first_name' => 'Jean',
                'last_name' => 'Mokonzi',
                'email' => 'jean.user@test',
                'password' => 'password123',
                'role' => 'teacher',
                'has_professional_profile' => true,
                'workload_hours' => 24,
                'job_grade' => 'A2',
                'job_title' => 'Titulaire',
            ])
            ->assertCreated()
            ->assertJsonPath('workload_hours', 24)
            ->assertJsonPath('job_grade', 'A2')
            ->assertJsonPath('job_title', 'Titulaire');
    }

    public function test_can_create_disciplinary_case(): void
    {
        $teacher = User::where('role', 'teacher')->firstOrFail();
        $this->actingAs($teacher, 'sanctum')
            ->postJson('/api/discipline/cases', [
                'target_type' => 'student',
                'student_id' => $this->student->id,
                'category' => 'conduct',
                'severity' => 'medium',
                'title' => 'Conduite en classe',
                'description' => 'Perturbation répétée',
            ])
            ->assertCreated();
    }
}
