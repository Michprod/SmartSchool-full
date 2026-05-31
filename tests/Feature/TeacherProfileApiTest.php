<?php

namespace Tests\Feature;

use App\Models\ClassSubject;
use App\Models\Role;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeacherProfileApiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $teacher;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'Admin', 'slug' => 'admin', 'permissions' => ['*']]);
        Role::create(['name' => 'Teacher', 'slug' => 'teacher', 'permissions' => [
            'students:read', 'classes:*', 'grades:*', 'conduct:write', 'discipline:read',
        ]]);
        Role::create(['name' => 'Director', 'slug' => 'director', 'permissions' => ['teachers:read']]);

        $this->admin = User::create([
            'first_name' => 'Admin', 'last_name' => 'Test', 'email' => 'admin@teacher.test',
            'password' => bcrypt('password'), 'role' => 'admin', 'is_active' => true,
        ]);

        $this->teacher = User::create([
            'first_name' => 'Marie', 'last_name' => 'Prof', 'email' => 'marie@teacher.test',
            'password' => bcrypt('password'), 'role' => 'teacher', 'is_active' => true,
            'workload_hours' => 20, 'job_title' => 'Professeur', 'department' => 'Sciences',
        ]);

        $class = $this->createTestSchoolClass(['grade_code' => 'prim_6', 'teacher_id' => $this->teacher->id]);
        $subject = Subject::create(['name' => 'Mathématiques', 'code' => 'MATH', 'type' => 'core']);
        ClassSubject::create([
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'teacher_id' => $this->teacher->id,
            'coefficient' => 4,
            'hours_per_week' => 6,
            'academic_year' => '2025-2026',
            'is_active' => true,
        ]);
    }

    public function test_teacher_can_view_own_teaching_profile(): void
    {
        $this->actingAs($this->teacher, 'sanctum')
            ->getJson('/api/me/teaching-profile')
            ->assertOk()
            ->assertJsonPath('user.email', 'marie@teacher.test')
            ->assertJsonPath('workload.assigned_hours', 6)
            ->assertJsonPath('workload.contractual_hours', 20)
            ->assertJsonPath('principal_class.id', fn ($id) => $id !== null);
    }

    public function test_admin_can_list_teachers(): void
    {
        $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/teachers')
            ->assertOk()
            ->assertJsonStructure(['data' => [['user', 'workload', 'assignments']]]);
    }

    public function test_workload_summary_for_admin(): void
    {
        $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/teachers/workload-summary')
            ->assertOk()
            ->assertJsonStructure(['teachers' => [['id', 'assigned_hours', 'contractual_hours']]]);
    }
}
