<?php

namespace Tests\Feature;

use App\Models\EducationCycle;
use App\Models\FeeType;
use App\Models\Personnel;
use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\User;
use Database\Seeders\RdcEducationCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SetupStatusApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create([
            'name' => 'Administrateur',
            'slug' => 'admin',
            'permissions' => ['*'],
        ]);
    }

    public function test_setup_status_returns_checks(): void
    {
        $admin = User::create([
            'first_name' => 'Admin',
            'last_name' => 'Test',
            'email' => 'admin@test.cd',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/setup/status');

        $response->assertOk();
        $response->assertJsonStructure(['ready', 'checks' => [['key', 'label', 'ok']]]);
        $response->assertJsonPath('ready', false);
    }

    public function test_setup_ready_when_prerequisites_met(): void
    {
        $this->seed(RdcEducationCatalogSeeder::class);

        $admin = User::create([
            'first_name' => 'Admin',
            'last_name' => 'Test',
            'email' => 'admin2@test.cd',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        $teacherUser = User::create([
            'first_name' => 'T',
            'last_name' => 'Eacher',
            'email' => 't@test.cd',
            'password' => bcrypt('password'),
            'role' => 'teacher',
            'is_active' => true,
        ]);

        Personnel::create([
            'user_id' => $teacherUser->id,
            'staff_number' => 'STF-00001',
            'staff_type' => 'teacher',
            'first_name' => 'T',
            'last_name' => 'Eacher',
            'is_active' => true,
        ]);

        FeeType::create(['code' => 'TUITION', 'label' => 'Scolarité', 'is_active' => true]);
        Subject::create(['code' => 'MATH', 'name' => 'Math', 'type' => 'core']);
        $this->createTestSchoolClass();

        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/setup/status');
        $response->assertOk();
        $this->assertTrue(collect($response->json('checks'))->firstWhere('key', 'personnel')['ok']);
        $this->assertTrue(collect($response->json('checks'))->firstWhere('key', 'subjects')['ok']);
    }
}
