<?php

namespace Tests\Feature;

use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubjectEligibilityApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\ProductionBootstrapSeeder::class);
    }

    public function test_maternelle_excludes_mathematics(): void
    {
        $admin = User::where('email', 'admin@smartschool.cd')->firstOrFail();
        $class = SchoolClass::query()
            ->whereHas('gradeLevel', fn ($q) => $q->where('code', 'mat_ps'))
            ->firstOrFail();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson("/api/classes/{$class->id}/available-subjects");

        $response->assertOk();
        $codes = collect($response->json())->pluck('code');
        $this->assertFalse($codes->contains('MATH'));
        $this->assertTrue($codes->contains('EPS'));
    }

    public function test_primaire_includes_mathematics(): void
    {
        $admin = User::where('email', 'admin@smartschool.cd')->firstOrFail();
        $class = SchoolClass::query()
            ->whereHas('gradeLevel', fn ($q) => $q->where('code', 'prim_6'))
            ->firstOrFail();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson("/api/classes/{$class->id}/available-subjects");

        $response->assertOk();
        $codes = collect($response->json())->pluck('code');
        $this->assertTrue($codes->contains('MATH'));
        $this->assertTrue($codes->contains('FRAN'));
    }

    public function test_cannot_assign_math_to_maternelle(): void
    {
        $admin = User::where('email', 'admin@smartschool.cd')->firstOrFail();
        $teacher = User::where('role', 'teacher')->firstOrFail();
        $class = SchoolClass::query()
            ->whereHas('gradeLevel', fn ($q) => $q->where('code', 'mat_ps'))
            ->firstOrFail();
        $math = Subject::where('code', 'MATH')->firstOrFail();

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/classes/{$class->id}/subjects", [
                'subject_id' => $math->id,
                'teacher_id' => $teacher->id,
                'academic_year' => '2025-2026',
            ])
            ->assertStatus(422);
    }

    public function test_teachers_and_classes_lists_not_empty_after_bootstrap(): void
    {
        $admin = User::where('email', 'admin@smartschool.cd')->firstOrFail();

        $teachers = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/teachers?per_page=50')
            ->assertOk()
            ->json('data');

        $classes = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/classes?per_page=50')
            ->assertOk()
            ->json('data');

        $this->assertNotEmpty($teachers);
        $this->assertNotEmpty($classes);
        $this->assertArrayHasKey('user', $teachers[0]);
    }
}
