<?php

namespace Tests\Feature;

use App\Models\ClassSubject;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeacherBootstrapFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_kasongo_has_classes_assignments_and_students_after_bootstrap(): void
    {
        $this->seed(\Database\Seeders\ProductionBootstrapSeeder::class);

        $kasongo = User::where('email', 'prof.kasongo@smartschool.cd')->firstOrFail();

        $this->assertGreaterThan(
            0,
            ClassSubject::where('teacher_id', $kasongo->id)->where('is_active', true)->count()
        );

        $myClasses = $this->actingAs($kasongo, 'sanctum')
            ->getJson('/api/grades/my-classes?academic_year=2025-2026');

        $myClasses->assertOk();
        $data = $myClasses->json();
        $this->assertIsArray($data);
        $this->assertNotEmpty($data);

        $hasAnglais = collect($data)->contains(function (array $row) {
            return collect($row['subjects'] ?? [])->contains(
                fn ($s) => ($s['subject']['code'] ?? null) === 'ANGL'
            );
        });
        $this->assertTrue($hasAnglais, 'Kasongo should teach ANGL in at least one class');

        $principalClass = SchoolClass::where('teacher_id', $kasongo->id)
            ->where('academic_year', '2025-2026')
            ->first();
        $this->assertNotNull($principalClass);

        $classesIndex = $this->actingAs($kasongo, 'sanctum')
            ->getJson('/api/classes?academic_year=2025-2026&per_page=100');

        $classesIndex->assertOk();
        $listed = $classesIndex->json('data') ?? $classesIndex->json();
        $this->assertIsArray($listed);
        $this->assertNotEmpty($listed);

        foreach ($listed as $classRow) {
            $classId = $classRow['id'] ?? null;
            $isAccessible = in_array($classId, $kasongo->accessibleClassIds('2025-2026'), true);
            $this->assertTrue($isAccessible);
        }

        $studentsInPrincipal = Student::where('class_id', $principalClass->id)->count();
        $this->assertGreaterThan(0, $studentsInPrincipal);

        $schoolYear = $this->actingAs($kasongo, 'sanctum')->getJson('/api/grades/school-year');
        $schoolYear->assertOk();
        $schoolYear->assertJsonPath('academic_year', '2025-2026');
    }

    public function test_homeroom_teacher_in_my_classes_without_subject_assignment(): void
    {
        $this->seed(\Database\Seeders\ProductionBootstrapSeeder::class);

        $teacher = User::factory()->create([
            'role' => 'teacher',
            'is_active' => true,
        ]);

        $class = SchoolClass::where('academic_year', '2025-2026')->firstOrFail();
        $class->update(['teacher_id' => $teacher->id]);

        ClassSubject::where('class_id', $class->id)
            ->where('teacher_id', $teacher->id)
            ->delete();

        $response = $this->actingAs($teacher, 'sanctum')
            ->getJson('/api/grades/my-classes?academic_year=2025-2026');

        $response->assertOk();
        $ids = collect($response->json())->pluck('class.id');
        $this->assertTrue($ids->contains($class->id));
    }
}
