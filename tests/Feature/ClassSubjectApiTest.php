<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\ClassSubject;
use App\Models\Role;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClassSubjectApiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $teacher;
    private User $otherTeacher;
    private $class;
    private Subject $subject;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'Admin', 'slug' => 'admin', 'permissions' => ['*']]);
        Role::create(['name' => 'Teacher', 'slug' => 'teacher', 'permissions' => ['classes:read', 'grades:*']]);

        $this->admin = User::create([
            'first_name' => 'Admin', 'last_name' => 'Test', 'email' => 'admin@cs.test',
            'password' => bcrypt('password'), 'role' => 'admin', 'is_active' => true,
        ]);
        $this->teacher = User::create([
            'first_name' => 'Prof', 'last_name' => 'A', 'email' => 'profa@cs.test',
            'password' => bcrypt('password'), 'role' => 'teacher', 'is_active' => true,
            'workload_hours' => 18,
        ]);
        $this->otherTeacher = User::create([
            'first_name' => 'Prof', 'last_name' => 'B', 'email' => 'profb@cs.test',
            'password' => bcrypt('password'), 'role' => 'teacher', 'is_active' => true,
        ]);

        $this->class = $this->createTestSchoolClass(['grade_code' => 'prim_5']);
        $this->subject = Subject::create(['name' => 'Français', 'code' => 'FR', 'type' => 'core']);
    }

    public function test_admin_can_create_class_subject_assignment(): void
    {
        $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/classes/{$this->class->id}/subjects", [
                'subject_id' => $this->subject->id,
                'teacher_id' => $this->teacher->id,
                'coefficient' => 3,
                'hours_per_week' => 5,
                'academic_year' => '2025-2026',
            ])
            ->assertCreated()
            ->assertJsonPath('data.subject.name', 'Français');
    }

    public function test_duplicate_assignment_rejected(): void
    {
        ClassSubject::create([
            'class_id' => $this->class->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->teacher->id,
            'academic_year' => '2025-2026',
            'is_active' => true,
        ]);

        $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/classes/{$this->class->id}/subjects", [
                'subject_id' => $this->subject->id,
                'teacher_id' => $this->otherTeacher->id,
                'academic_year' => '2025-2026',
            ])
            ->assertStatus(422);
    }

    public function test_teacher_cannot_create_assignment(): void
    {
        $this->actingAs($this->teacher, 'sanctum')
            ->postJson("/api/classes/{$this->class->id}/subjects", [
                'subject_id' => $this->subject->id,
                'teacher_id' => $this->teacher->id,
                'academic_year' => '2025-2026',
            ])
            ->assertForbidden();
    }

    public function test_deactivate_when_grades_exist(): void
    {
        $assignment = ClassSubject::create([
            'class_id' => $this->class->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->teacher->id,
            'academic_year' => '2025-2026',
            'is_active' => true,
        ]);

        $student = Student::create([
            'first_name' => 'Jean', 'last_name' => 'Dupont', 'matricule' => 'ST001',
            'student_number' => 'ST001', 'date_of_birth' => '2014-01-01', 'gender' => 'M',
            'guardian_name' => 'Parent', 'guardian_phone' => '+243800000001',
            'class_id' => $this->class->id, 'academic_year' => '2025-2026',
            'enrollment_date' => '2025-09-01', 'is_active' => true,
        ]);

        Assessment::create([
            'student_id' => $student->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->teacher->id,
            'class_id' => $this->class->id,
            'type' => 'devoir',
            'term' => 'T1',
            'academic_year' => '2025-2026',
            'score' => 15,
            'max_score' => 20,
            'date' => now(),
        ]);

        $this->actingAs($this->admin, 'sanctum')
            ->deleteJson("/api/class-subjects/{$assignment->id}")
            ->assertOk();

        $this->assertFalse($assignment->fresh()->is_active);
    }
}
