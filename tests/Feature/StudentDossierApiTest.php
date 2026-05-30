<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StudentDossierApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $teacher;
    protected Student $student;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create([
            'name' => 'Enseignant',
            'slug' => 'teacher',
            'description' => 'Gestion des classes et élèves',
            'permissions' => ['students:read', 'students:write', 'grades:*', 'finance:read'],
        ]);

        $this->teacher = User::create([
            'first_name' => 'Test',
            'last_name' => 'Teacher',
            'email' => 'teacher@student.test',
            'password' => bcrypt('password'),
            'role' => 'teacher',
            'is_active' => true,
        ]);

        $class = $this->createTestSchoolClass([
            'grade_code' => 'prim_6',
            'capacity' => 40,
            'teacher_id' => $this->teacher->id,
        ]);

        $this->student = Student::create([
            'matricule' => 'STU-0001',
            'student_number' => 'STU-0001',
            'first_name' => 'Eline',
            'last_name' => 'Kabila',
            'date_of_birth' => '2021-02-10',
            'gender' => 'F',
            'nationality' => 'Congolaise',
            'blood_group' => 'A+',
            'address' => '12 avenue de la paix',
            'city' => 'Kinshasa',
            'province' => 'Kinshasa',
            'guardian_name' => 'Jean Kabila',
            'guardian_phone' => '+243800000000',
            'guardian_email' => 'parent@example.test',
            'class_id' => $class->id,
            'academic_year' => '2025-2026',
            'enrollment_date' => '2025-09-01',
            'status' => 'active',
            'is_active' => true,
        ]);
    }

    public function test_student_show_returns_complete_resource_shape(): void
    {
        $response = $this->actingAs($this->teacher, 'sanctum')->getJson("/api/students/{$this->student->id}");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'id',
                'matricule',
                'student_number',
                'first_name',
                'last_name',
                'nationality',
                'blood_group',
                'guardian_name',
                'guardian_phone',
                'class_id',
                'school_class',
                'academic_year',
                'status',
            ],
        ]);
    }

    public function test_attendance_endpoints_allow_create_and_summary(): void
    {
        $this->actingAs($this->teacher, 'sanctum')
            ->postJson("/api/students/{$this->student->id}/attendance", [
                'attendance_date' => now()->toDateString(),
                'status' => 'present',
                'reason' => null,
            ])
            ->assertCreated();

        $this->actingAs($this->teacher, 'sanctum')
            ->getJson("/api/students/{$this->student->id}/attendance")
            ->assertOk()
            ->assertJsonStructure(['data']);

        $this->actingAs($this->teacher, 'sanctum')
            ->getJson("/api/students/{$this->student->id}/attendance/summary")
            ->assertOk()
            ->assertJsonPath('summary.present', 1)
            ->assertJsonPath('summary.attendance_rate', 100);
    }

    public function test_documents_endpoints_allow_upload_list_and_delete(): void
    {
        Storage::fake('public');

        $uploadResponse = $this->actingAs($this->teacher, 'sanctum')
            ->post("/api/students/{$this->student->id}/documents", [
                'type' => 'bulletin',
                'file' => UploadedFile::fake()->create('bulletin.pdf', 80, 'application/pdf'),
            ], ['Accept' => 'application/json']);

        $uploadResponse->assertCreated();
        $documentId = $uploadResponse->json('data.id');

        $this->actingAs($this->teacher, 'sanctum')
            ->getJson("/api/students/{$this->student->id}/documents")
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->actingAs($this->teacher, 'sanctum')
            ->deleteJson("/api/students/{$this->student->id}/documents/{$documentId}")
            ->assertNoContent();
    }
}
