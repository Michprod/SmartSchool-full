<?php

namespace Tests\Feature;

use App\Models\Personnel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductionBootstrapFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_production_bootstrap_endpoints_and_personnel_creation(): void
    {
        $this->seed(\Database\Seeders\ProductionBootstrapSeeder::class);

        $admin = User::where('email', 'admin@smartschool.cd')->firstOrFail();
        $secretary = User::where('role', 'secretary')->firstOrFail();

        // 1) SetupStatus doit être prêt après bootstrap minimal.
        $setupRes = $this->actingAs($admin, 'sanctum')->getJson('/api/setup/status');
        $setupRes->assertOk();
        $setupRes->assertJsonPath('ready', true);

        // 2) Le référentiel RH doit être non vide pour un profil qui n'a pas `settings:read`.
        $deptRes = $this->actingAs($secretary, 'sanctum')->getJson('/api/config/personnel-ref?type=department');
        $deptRes->assertOk();
        $departments = $deptRes->json();
        $this->assertIsArray($departments);
        $this->assertNotEmpty($departments);
        $departmentLabel = $departments[0]['label'];

        $gradeRes = $this->actingAs($secretary, 'sanctum')->getJson('/api/config/personnel-ref?type=job_grade');
        $gradeRes->assertOk();
        $grades = $gradeRes->json();
        $this->assertIsArray($grades);
        $this->assertNotEmpty($grades);
        $jobGradeLabel = $grades[0]['label'];

        $contractRes = $this->actingAs($secretary, 'sanctum')->getJson('/api/config/personnel-ref?type=contract_type');
        $contractRes->assertOk();
        $contracts = $contractRes->json();
        $this->assertIsArray($contracts);
        $this->assertNotEmpty($contracts);
        $contractTypeLabel = $contracts[0]['label'];

        // 3) Flux Personnel: création via API en utilisant des valeurs issues du référentiel.
        $email = 'tmp.teacher.'.uniqid().'@smartschool.cd';
        $createRes = $this->actingAs($secretary, 'sanctum')->postJson('/api/personnel', [
            'staff_type' => 'teacher',
            'first_name' => 'Test',
            'last_name' => 'Personnel',
            'email' => $email,
            'password' => 'password',
            'department' => $departmentLabel,
            'job_grade' => $jobGradeLabel,
            'contract_type' => $contractTypeLabel,
            'is_active' => true,
        ]);
        $createRes->assertCreated();

        $this->assertDatabaseHas('personnel', [
            'staff_type' => 'teacher',
            'department' => $departmentLabel,
        ]);
        $this->assertDatabaseHas('users', [
            'email' => $email,
        ]);

        $this->assertGreaterThan(0, \App\Models\ClassSubject::count());

        $kasongo = User::where('email', 'prof.kasongo@smartschool.cd')->firstOrFail();
        $myClasses = $this->actingAs($kasongo, 'sanctum')
            ->getJson('/api/grades/my-classes?academic_year=2025-2026');
        $myClasses->assertOk();
        $this->assertNotEmpty($myClasses->json());
    }
}

