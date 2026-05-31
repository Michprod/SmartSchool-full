<?php

namespace Tests\Feature;

use App\Models\ClassSubject;
use App\Models\ConductGrade;
use App\Models\EvaluationSession;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudentAverage;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeacherModuleIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $homeroomTeacher;
    private User $subjectTeacher;
    private $class;
    private Subject $subject;
    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'Admin', 'slug' => 'admin', 'permissions' => ['*']]);
        Role::create(['name' => 'Teacher', 'slug' => 'teacher', 'permissions' => [
            'students:read', 'classes:*', 'grades:*', 'conduct:write', 'discipline:write',
        ]]);

        $this->admin = User::create([
            'first_name' => 'Admin', 'last_name' => 'Test', 'email' => 'admin@mod.test',
            'password' => bcrypt('password'), 'role' => 'admin', 'is_active' => true,
        ]);

        $this->homeroomTeacher = User::create([
            'first_name' => 'Titulaire', 'last_name' => 'Classe', 'email' => 'titulaire@mod.test',
            'password' => bcrypt('password'), 'role' => 'teacher', 'is_active' => true,
        ]);

        $this->subjectTeacher = User::create([
            'first_name' => 'Prof', 'last_name' => 'Matiere', 'email' => 'matiere@mod.test',
            'password' => bcrypt('password'), 'role' => 'teacher', 'is_active' => true,
        ]);

        $this->class = $this->createTestSchoolClass([
            'grade_code' => 'prim_6',
            'teacher_id' => $this->homeroomTeacher->id,
        ]);

        $this->subject = Subject::create(['name' => 'Math', 'code' => 'MATH', 'type' => 'core']);

        ClassSubject::create([
            'class_id' => $this->class->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->subjectTeacher->id,
            'academic_year' => '2025-2026',
            'is_active' => true,
            'hours_per_week' => 4,
        ]);

        $this->student = Student::create([
            'first_name' => 'Paul', 'last_name' => 'Eleve', 'matricule' => 'EL001',
            'student_number' => 'EL001', 'date_of_birth' => '2014-06-01', 'gender' => 'M',
            'guardian_name' => 'Parent', 'guardian_phone' => '+243800000002',
            'class_id' => $this->class->id, 'academic_year' => '2025-2026',
            'enrollment_date' => '2025-09-01', 'is_active' => true,
        ]);
    }

    public function test_evaluation_session_and_grid(): void
    {
        $session = $this->actingAs($this->subjectTeacher, 'sanctum')
            ->postJson('/api/grades/evaluation-sessions', [
                'class_id' => $this->class->id,
                'subject_id' => $this->subject->id,
                'type' => 'interro',
                'term' => 'T1',
                'academic_year' => '2025-2026',
                'title' => 'Interro 1',
                'date' => '2025-09-15',
                'max_score' => 10,
            ])
            ->assertCreated()
            ->json('id');

        $this->actingAs($this->subjectTeacher, 'sanctum')
            ->postJson("/api/grades/evaluation-sessions/{$session}/grades", [
                'grades' => [['student_id' => $this->student->id, 'score' => 8]],
            ])
            ->assertCreated();

        $this->actingAs($this->subjectTeacher, 'sanctum')
            ->getJson('/api/grades/grid?'.http_build_query([
                'class_id' => $this->class->id,
                'subject_id' => $this->subject->id,
                'term' => 'T1',
                'academic_year' => '2025-2026',
                'evaluation_session_id' => $session,
            ]))
            ->assertOk()
            ->assertJsonPath('students.0.assessment.score', '8.00');
    }

    public function test_homeroom_teacher_can_save_conduct(): void
    {
        $this->actingAs($this->homeroomTeacher, 'sanctum')
            ->postJson("/api/classes/{$this->class->id}/conduct/bulk", [
                'term' => 'T1',
                'academic_year' => '2025-2026',
                'grades' => [[
                    'student_id' => $this->student->id,
                    'conduct_score' => 18,
                    'appreciation' => 'Bon comportement en classe.',
                ]],
            ])
            ->assertCreated();

        $this->assertDatabaseHas('conduct_grades', [
            'student_id' => $this->student->id,
            'appreciation' => 'Bon comportement en classe.',
        ]);
    }

    public function test_subject_teacher_cannot_save_conduct(): void
    {
        $this->actingAs($this->subjectTeacher, 'sanctum')
            ->postJson("/api/classes/{$this->class->id}/conduct/bulk", [
                'term' => 'T1',
                'academic_year' => '2025-2026',
                'grades' => [['student_id' => $this->student->id, 'conduct_score' => 10]],
            ])
            ->assertForbidden();
    }

    public function test_report_card_includes_conduct_appreciation(): void
    {
        ConductGrade::create([
            'student_id' => $this->student->id,
            'class_id' => $this->class->id,
            'term' => 'T1',
            'academic_year' => '2025-2026',
            'conduct_score' => 17,
            'appreciation' => 'Appréciation conduite T1',
            'recorded_by' => $this->homeroomTeacher->id,
        ]);

        StudentAverage::create([
            'student_id' => $this->student->id,
            'subject_id' => $this->subject->id,
            'class_id' => $this->class->id,
            'term' => 'T1',
            'academic_year' => '2025-2026',
            'average_score' => 14,
            'general_average' => 14,
            'class_rank' => 1,
            'total_students' => 1,
            'calculated_at' => now(),
        ]);

        $this->actingAs($this->homeroomTeacher, 'sanctum')
            ->postJson("/api/grades/students/{$this->student->id}/report-card", [
                'term' => 'T1',
                'academic_year' => '2025-2026',
            ])
            ->assertOk()
            ->assertJsonPath('data.behavior_recommendations', 'Appréciation conduite T1');
    }

    public function test_timetable_returns_schedule_slots(): void
    {
        ClassSubject::where('class_id', $this->class->id)->update([
            'schedule' => [
                'monday' => [['start' => '08:00', 'end' => '10:00', 'room' => 'A1']],
            ],
        ]);

        $this->actingAs($this->subjectTeacher, 'sanctum')
            ->getJson('/api/me/timetable')
            ->assertOk()
            ->assertJsonPath('slots.0.day', 'monday')
            ->assertJsonPath('slots.0.start', '08:00');
    }
}
