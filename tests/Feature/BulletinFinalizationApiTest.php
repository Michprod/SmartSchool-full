<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\ClassSubject;
use App\Models\EvaluationSession;
use App\Models\ReportCard;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudentAverage;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BulletinFinalizationApiTest extends TestCase
{
    use RefreshDatabase;

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
            'students:read', 'classes:*', 'grades:*', 'conduct:write', 'discipline:write', 'grades:read',
        ]]);

        $this->homeroomTeacher = User::create([
            'first_name' => 'Titulaire', 'last_name' => 'Classe', 'email' => 'titulaire@bulletin.test',
            'password' => bcrypt('password'), 'role' => 'teacher', 'is_active' => true,
        ]);

        $this->subjectTeacher = User::create([
            'first_name' => 'Prof', 'last_name' => 'Matiere', 'email' => 'matiere@bulletin.test',
            'password' => bcrypt('password'), 'role' => 'teacher', 'is_active' => true,
        ]);

        $this->class = $this->createTestSchoolClass([
            'grade_code' => 'prim_6',
            'teacher_id' => $this->homeroomTeacher->id,
        ]);

        $this->subject = Subject::create(['name' => 'Math', 'code' => 'MATH', 'type' => 'core', 'is_active' => true]);

        ClassSubject::create([
            'class_id' => $this->class->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->subjectTeacher->id,
            'coefficient' => 4,
            'hours_per_week' => 4,
            'academic_year' => '2025-2026',
            'is_active' => true,
        ]);

        $this->student = Student::create([
            'first_name' => 'Paul', 'last_name' => 'Eleve', 'matricule' => 'EL-BUL-001',
            'student_number' => 'EL-BUL-001', 'date_of_birth' => '2014-06-01', 'gender' => 'M',
            'guardian_name' => 'Parent', 'guardian_phone' => '+243800000002',
            'class_id' => $this->class->id, 'academic_year' => '2025-2026',
            'enrollment_date' => '2025-09-01', 'is_active' => true,
        ]);
    }

    private function seedPublishedGrades(): void
    {
        $session = EvaluationSession::create([
            'class_id' => $this->class->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->subjectTeacher->id,
            'type' => 'interro',
            'term' => 'T1',
            'academic_year' => '2025-2026',
            'title' => 'Interro T1',
            'date' => '2025-10-01',
            'max_score' => 10,
            'coefficient' => 1,
            'is_published' => true,
            'published_at' => now(),
        ]);

        Assessment::create([
            'student_id' => $this->student->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->subjectTeacher->id,
            'class_id' => $this->class->id,
            'evaluation_session_id' => $session->id,
            'type' => 'interro',
            'term' => 'T1',
            'academic_year' => '2025-2026',
            'score' => 14,
            'max_score' => 20,
            'coefficient' => 1,
            'title' => 'Interro T1',
            'date' => '2025-10-01',
            'is_published' => true,
        ]);
    }

    public function test_subject_teacher_can_calculate_class_averages(): void
    {
        $this->seedPublishedGrades();

        $this->actingAs($this->subjectTeacher, 'sanctum')
            ->postJson("/api/grades/classes/{$this->class->id}/calculate", [
                'term' => 'T1',
                'academic_year' => '2025-2026',
            ])
            ->assertOk();

        $this->assertDatabaseHas('student_averages', [
            'student_id' => $this->student->id,
            'term' => 'T1',
            'academic_year' => '2025-2026',
        ]);
    }

    public function test_subject_teacher_cannot_generate_class_report_cards(): void
    {
        $this->seedPublishedGrades();

        $this->actingAs($this->subjectTeacher, 'sanctum')
            ->postJson("/api/grades/classes/{$this->class->id}/calculate", [
                'term' => 'T1',
                'academic_year' => '2025-2026',
            ])
            ->assertOk();

        $this->actingAs($this->subjectTeacher, 'sanctum')
            ->postJson("/api/grades/classes/{$this->class->id}/report-cards", [
                'term' => 'T1',
                'academic_year' => '2025-2026',
            ])
            ->assertForbidden();
    }

    public function test_homeroom_teacher_generates_and_publishes_report_cards(): void
    {
        $this->seedPublishedGrades();

        $this->actingAs($this->subjectTeacher, 'sanctum')
            ->postJson("/api/grades/classes/{$this->class->id}/calculate", [
                'term' => 'T1',
                'academic_year' => '2025-2026',
            ])
            ->assertOk();

        $generate = $this->actingAs($this->homeroomTeacher, 'sanctum')
            ->postJson("/api/grades/classes/{$this->class->id}/report-cards", [
                'term' => 'T1',
                'academic_year' => '2025-2026',
            ]);

        $generate->assertOk();
        $this->assertSame(1, $generate->json('generated_count'));

        $publish = $this->actingAs($this->homeroomTeacher, 'sanctum')
            ->postJson("/api/grades/classes/{$this->class->id}/report-cards/publish", [
                'term' => 'T1',
                'academic_year' => '2025-2026',
            ]);

        $publish->assertOk();
        $this->assertSame(1, $publish->json('published_count'));

        $this->assertTrue(
            ReportCard::where('student_id', $this->student->id)
                ->where('term', 'T1')
                ->where('is_published', true)
                ->exists()
        );
    }

    public function test_class_bulletin_not_empty_after_calculation(): void
    {
        $this->seedPublishedGrades();

        $this->actingAs($this->subjectTeacher, 'sanctum')
            ->postJson("/api/grades/classes/{$this->class->id}/calculate", [
                'term' => 'T1',
                'academic_year' => '2025-2026',
            ])
            ->assertOk();

        $response = $this->actingAs($this->homeroomTeacher, 'sanctum')
            ->getJson("/api/grades/classes/{$this->class->id}/bulletin?term=T1&academic_year=2025-2026");

        $response->assertOk();
        $students = $response->json('students');
        $this->assertIsArray($students);
        $this->assertNotEmpty($students);
        $this->assertArrayHasKey('general_average', $students[0]);
    }

    public function test_academic_profile_shows_data_after_publication(): void
    {
        $this->seedPublishedGrades();

        $this->actingAs($this->subjectTeacher, 'sanctum')
            ->postJson("/api/grades/classes/{$this->class->id}/calculate", [
                'term' => 'T1',
                'academic_year' => '2025-2026',
            ])
            ->assertOk();

        $this->actingAs($this->homeroomTeacher, 'sanctum')
            ->postJson("/api/grades/classes/{$this->class->id}/report-cards", [
                'term' => 'T1',
                'academic_year' => '2025-2026',
            ])
            ->assertOk();

        $this->actingAs($this->homeroomTeacher, 'sanctum')
            ->postJson("/api/grades/classes/{$this->class->id}/report-cards/publish", [
                'term' => 'T1',
                'academic_year' => '2025-2026',
            ])
            ->assertOk();

        $profile = $this->actingAs($this->homeroomTeacher, 'sanctum')
            ->getJson("/api/grades/students/{$this->student->id}/academic-profile?term=T1&academic_year=2025-2026");

        $profile->assertOk();
        $this->assertNotNull($profile->json('general_average'));
        $this->assertNotEmpty($profile->json('assessments_by_type'));
        $this->assertTrue($profile->json('report_card.is_published'));
    }

    public function test_homeroom_teacher_can_download_report_card_pdf(): void
    {
        $this->seedPublishedGrades();

        $this->actingAs($this->subjectTeacher, 'sanctum')
            ->postJson("/api/grades/classes/{$this->class->id}/calculate", [
                'term' => 'T1',
                'academic_year' => '2025-2026',
            ])
            ->assertOk();

        $this->actingAs($this->homeroomTeacher, 'sanctum')
            ->postJson("/api/grades/classes/{$this->class->id}/report-cards", [
                'term' => 'T1',
                'academic_year' => '2025-2026',
            ])
            ->assertOk();

        $response = $this->actingAs($this->homeroomTeacher, 'sanctum')
            ->get("/api/grades/students/{$this->student->id}/report-card/pdf?term=T1&academic_year=2025-2026");

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
        $this->assertStringContainsString('%%EOF', $response->getContent());

        $this->assertDatabaseHas('report_cards', [
            'student_id' => $this->student->id,
            'term' => 'T1',
            'academic_year' => '2025-2026',
        ]);

        $reportCard = ReportCard::where('student_id', $this->student->id)->first();
        $this->assertNotNull($reportCard->pdf_path);
    }

    public function test_subject_teacher_can_download_report_card_pdf_for_their_class(): void
    {
        $this->seedPublishedGrades();

        $this->actingAs($this->subjectTeacher, 'sanctum')
            ->postJson("/api/grades/classes/{$this->class->id}/calculate", [
                'term' => 'T1',
                'academic_year' => '2025-2026',
            ])
            ->assertOk();

        $this->actingAs($this->homeroomTeacher, 'sanctum')
            ->postJson("/api/grades/classes/{$this->class->id}/report-cards", [
                'term' => 'T1',
                'academic_year' => '2025-2026',
            ])
            ->assertOk();

        $response = $this->actingAs($this->subjectTeacher, 'sanctum')
            ->get("/api/grades/students/{$this->student->id}/report-card/pdf?term=T1&academic_year=2025-2026");

        $response->assertOk();
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_report_card_pdf_returns_not_found_before_generation(): void
    {
        $this->seedPublishedGrades();

        $this->actingAs($this->subjectTeacher, 'sanctum')
            ->postJson("/api/grades/classes/{$this->class->id}/calculate", [
                'term' => 'T1',
                'academic_year' => '2025-2026',
            ])
            ->assertOk();

        $this->actingAs($this->homeroomTeacher, 'sanctum')
            ->get("/api/grades/students/{$this->student->id}/report-card/pdf?term=T1&academic_year=2025-2026")
            ->assertNotFound();
    }
}
