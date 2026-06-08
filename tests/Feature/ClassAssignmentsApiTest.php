<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClassAssignmentsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_class_subjects_index_after_bootstrap(): void
    {
        $this->seed(\Database\Seeders\ProductionBootstrapSeeder::class);

        $admin = User::where('email', 'admin@smartschool.cd')->firstOrFail();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/class-subjects?academic_year=2025-2026&per_page=10');

        $response->assertOk();
        $data = $response->json('data') ?? $response->json();
        $this->assertIsArray($data);
        $this->assertNotEmpty($data);
        $this->assertArrayHasKey('teacher', $data[0]);
        $this->assertArrayHasKey('subject', $data[0]);
        $this->assertArrayHasKey('school_class', $data[0]);
    }

    public function test_secretary_can_list_class_subjects(): void
    {
        $this->seed(\Database\Seeders\ProductionBootstrapSeeder::class);

        $secretary = User::where('email', 'secretaire@smartschool.cd')->firstOrFail();

        $this->actingAs($secretary, 'sanctum')
            ->getJson('/api/class-subjects?academic_year=2025-2026')
            ->assertOk();
    }

    public function test_accountant_cannot_list_class_subjects(): void
    {
        $this->seed(\Database\Seeders\ProductionBootstrapSeeder::class);

        $accountant = User::where('email', 'comptable@smartschool.cd')->firstOrFail();

        $this->actingAs($accountant, 'sanctum')
            ->getJson('/api/class-subjects')
            ->assertForbidden();
    }

    public function test_setup_status_includes_class_assignments_check(): void
    {
        $this->seed(\Database\Seeders\ProductionBootstrapSeeder::class);

        $admin = User::where('email', 'admin@smartschool.cd')->firstOrFail();

        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/setup/status');

        $response->assertOk();
        $checks = collect($response->json('checks'));
        $assignmentCheck = $checks->firstWhere('key', 'class_assignments');
        $this->assertNotNull($assignmentCheck);
        $this->assertTrue($assignmentCheck['ok']);
    }
}
