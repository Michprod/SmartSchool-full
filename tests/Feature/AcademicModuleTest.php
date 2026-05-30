<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\ClassSubject;
use App\Models\Payment;
use App\Models\ReportCard;
use App\Models\Role;
use App\Models\Setting;
use App\Models\Student;
use App\Models\StudentAverage;
use App\Models\Subject;
use App\Models\User;
use App\Services\GradeCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcademicModuleTest extends TestCase
{
    use RefreshDatabase;

    protected User $teacher;

    protected User $parent;

    protected Student $student;

    protected Student $otherStudent;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create([
            'name' => 'Enseignant',
            'slug' => 'teacher',
            'permissions' => ['grades:*', 'students:read'],
        ]);
        Role::create([
            'name' => 'Parent',
            'slug' => 'parent',
            'permissions' => ['bulletins:read_own', 'students:read_own', 'payments:read_own'],
        ]);

        $this->teacher = User::create([
            'first_name' => 'Prof',
            'last_name' => 'Notes',
            'email' => 'teacher.academic@test',
            'password' => bcrypt('password'),
            'role' => 'teacher',
            'is_active' => true,
        ]);

        $this->parent = User::create([
            'first_name' => 'Parent',
            'last_name' => 'Test',
            'email' => 'parent.academic@test',
            'password' => bcrypt('password'),
            'role' => 'parent',
            'is_active' => true,
        ]);

        $class = $this->createTestSchoolClass([
            'grade_code' => 'prim_6',
            'teacher_id' => $this->teacher->id,
        ]);

        $this->student = Student::create([
            'matricule' => 'AC-001',
            'student_number' => 'AC-001',
            'first_name' => 'Amina',
            'last_name' => 'Lukau',
            'date_of_birth' => '2014-01-01',
            'gender' => 'F',
            'guardian_name' => 'Parent Lukau',
            'guardian_phone' => '+243800000001',
            'guardian_email' => 'parent.academic@test',
            'class_id' => $class->id,
            'academic_year' => '2025-2026',
            'enrollment_date' => '2025-09-01',
            'status' => 'active',
            'is_active' => true,
            'parent_ids' => [$this->parent->id],
        ]);

        $this->otherStudent = Student::create([
            'matricule' => 'AC-002',
            'student_number' => 'AC-002',
            'first_name' => 'Bob',
            'last_name' => 'Other',
            'date_of_birth' => '2014-06-01',
            'gender' => 'M',
            'guardian_name' => 'Autre Parent',
            'guardian_phone' => '+243800000002',
            'guardian_email' => 'other@test.com',
            'class_id' => $class->id,
            'academic_year' => '2025-2026',
            'enrollment_date' => '2025-09-01',
            'status' => 'active',
            'is_active' => true,
            'parent_ids' => [],
        ]);

        $subject = Subject::create(['name' => 'Mathématiques', 'code' => 'MATH', 'type' => 'core']);

        ClassSubject::create([
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'teacher_id' => $this->teacher->id,
            'coefficient' => 4,
            'hours_per_week' => 5,
            'academic_year' => '2025-2026',
            'is_active' => true,
        ]);

        Assessment::create([
            'student_id' => $this->student->id,
            'subject_id' => $subject->id,
            'teacher_id' => $this->teacher->id,
            'class_id' => $class->id,
            'type' => 'travail_hebdomadaire',
            'term' => 'T1',
            'academic_year' => '2025-2026',
            'score' => 16,
            'max_score' => 20,
            'coefficient' => 1,
            'date' => '2025-10-01',
            'is_published' => true,
        ]);

        app(GradeCalculationService::class)->calculateAndSaveStudentAverages(
            $this->student->id,
            'T1',
            '2025-2026'
        );
        app(GradeCalculationService::class)->calculateClassRanks($class->id, 'T1', '2025-2026');

        ReportCard::create([
            'student_id' => $this->student->id,
            'class_id' => $class->id,
            'term' => 'T1',
            'academic_year' => '2025-2026',
            'general_average' => 14.5,
            'class_rank' => 1,
            'total_students' => 2,
            'decision' => 'pass',
            'generated_by' => $this->teacher->id,
            'generated_at' => now(),
            'is_published' => true,
            'published_at' => now(),
        ]);
    }

    public function test_catalog_returns_trimestre_for_primary_class(): void
    {
        $class = $this->student->schoolClass;

        $response = $this->actingAs($this->teacher, 'sanctum')
            ->getJson('/api/grades/catalog?class_id='.$class->id);

        $response->assertOk();
        $response->assertJsonPath('period_scheme', 'trimestre');
        $response->assertJsonCount(3, 'periods');
    }

    public function test_student_evolution_endpoint(): void
    {
        $response = $this->actingAs($this->teacher, 'sanctum')
            ->getJson("/api/grades/students/{$this->student->id}/evolution?academic_year=2025-2026");

        $response->assertOk();
        $response->assertJsonStructure(['timeline', 'period_scheme', 'assessments_by_period']);
    }

    public function test_parent_sees_only_linked_children(): void
    {
        $response = $this->actingAs($this->parent, 'sanctum')
            ->getJson('/api/parent/children');

        $response->assertOk();
        $response->assertJsonCount(1, 'children');
        $response->assertJsonPath('children.0.id', $this->student->id);
    }

    public function test_parent_cannot_view_other_child_bulletin(): void
    {
        ReportCard::create([
            'student_id' => $this->otherStudent->id,
            'class_id' => $this->otherStudent->class_id,
            'term' => 'T1',
            'academic_year' => '2025-2026',
            'general_average' => 12,
            'class_rank' => 2,
            'total_students' => 2,
            'decision' => 'pass',
            'generated_by' => $this->teacher->id,
            'generated_at' => now(),
            'is_published' => true,
            'published_at' => now(),
        ]);

        $response = $this->actingAs($this->parent, 'sanctum')
            ->getJson("/api/parent/children/{$this->otherStudent->id}/report-card?term=T1&academic_year=2025-2026");

        $response->assertForbidden();
    }

    public function test_parent_blocked_when_unpaid_tuition(): void
    {
        Payment::create([
            'student_id' => $this->student->id,
            'type' => 'tuition',
            'amount' => 500,
            'currency' => 'USD',
            'status' => 'pending',
            'payment_method' => 'cash',
            'due_date' => now()->subDays(5)->toDateString(),
            'description' => 'Frais T1',
        ]);

        Setting::create([
            'key' => 'school_settings',
            'value' => ['block_bulletin_unpaid' => true],
        ]);

        $response = $this->actingAs($this->parent, 'sanctum')
            ->getJson("/api/parent/children/{$this->student->id}/report-card?term=T1&academic_year=2025-2026");

        $response->assertForbidden();
        $response->assertJsonFragment(['message' => 'Accès au bulletin suspendu : frais scolaires impayés. Veuillez régulariser votre situation à la comptabilité.']);
    }

    public function test_parent_can_view_published_bulletin_when_paid(): void
    {
        $response = $this->actingAs($this->parent, 'sanctum')
            ->getJson("/api/parent/children/{$this->student->id}/report-card?term=T1&academic_year=2025-2026");

        $response->assertOk();
        $response->assertJsonPath('rank_display', '1/2');
    }

    public function test_class_bulletin_returns_ranking(): void
    {
        $classId = $this->student->class_id;

        $response = $this->actingAs($this->teacher, 'sanctum')
            ->getJson("/api/grades/classes/{$classId}/bulletin?term=T1&academic_year=2025-2026");

        $response->assertOk();
        $response->assertJsonStructure(['students', 'statistics', 'term_label']);
    }

    public function test_semester_scheme_for_cteb_class(): void
    {
        $ctebClass = $this->createTestSchoolClass(['grade_code' => 'cteb_7']);

        $response = $this->actingAs($this->teacher, 'sanctum')
            ->getJson('/api/grades/catalog?class_id='.$ctebClass->id);

        $response->assertOk();
        $response->assertJsonPath('period_scheme', 'semestre');
        $response->assertJsonCount(2, 'periods');
    }
}
