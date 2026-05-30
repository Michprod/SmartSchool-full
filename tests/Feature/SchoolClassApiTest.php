<?php

namespace Tests\Feature;

use App\Models\GradeLevel;
use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\StudyOption;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchoolClassApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $accountant;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'Admin', 'slug' => 'admin', 'description' => 'All', 'permissions' => ['*']]);
        Role::create(['name' => 'Comptable', 'slug' => 'accountant', 'description' => 'Finance', 'permissions' => ['finance:*']]);

        $this->admin = User::create([
            'first_name' => 'Admin', 'last_name' => 'Test', 'email' => 'admin@class.test',
            'password' => bcrypt('password'), 'role' => 'admin', 'is_active' => true,
        ]);
        $this->accountant = User::create([
            'first_name' => 'Comptable', 'last_name' => 'Test', 'email' => 'accountant@class.test',
            'password' => bcrypt('password'), 'role' => 'accountant', 'is_active' => true,
        ]);

        $this->seedRdcCatalog();
    }

    public function test_catalog_lists_cycles_levels_and_options(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/classes/catalog')
            ->assertOk()
            ->assertJsonStructure(['cycles' => [['code', 'name', 'grade_levels', 'study_options']]]);

        $firstLevel = $response->json('cycles.0.grade_levels.0');
        $this->assertArrayNotHasKey('legacy_name', $firstLevel);
    }

    public function test_create_class_with_section(): void
    {
        $gradeLevel = GradeLevel::where('code', 'cteb_7')->firstOrFail();

        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/classes', [
                'grade_level_id' => $gradeLevel->id,
                'section' => 'A',
                'academic_year' => '2025-2026',
                'capacity' => 40,
            ])
            ->assertCreated()
            ->assertJsonPath('data.display_name', '7ème année Éducation de Base A');
    }

    public function test_humanites_class_requires_option(): void
    {
        $gradeLevel = GradeLevel::where('code', 'hum_1')->firstOrFail();

        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/classes', [
                'grade_level_id' => $gradeLevel->id,
                'section' => 'A',
                'academic_year' => '2025-2026',
                'capacity' => 40,
            ])
            ->assertStatus(422);
    }

    public function test_create_humanites_class_with_option(): void
    {
        $gradeLevel = GradeLevel::where('code', 'hum_1')->firstOrFail();
        $option = StudyOption::where('code', 'opt_elec')->firstOrFail();

        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/classes', [
                'grade_level_id' => $gradeLevel->id,
                'study_option_id' => $option->id,
                'section' => 'B',
                'academic_year' => '2025-2026',
                'capacity' => 40,
            ])
            ->assertCreated()
            ->assertJsonPath('data.display_name', '1ère année des Humanités Électricité B');
    }

    public function test_duplicate_section_rejected(): void
    {
        $class = $this->createTestSchoolClass(['grade_code' => 'prim_2']);

        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/classes', [
                'grade_level_id' => $class->grade_level_id,
                'section' => $class->section,
                'academic_year' => $class->academic_year,
                'capacity' => 30,
            ])
            ->assertStatus(422);
    }

    public function test_accountant_cannot_create_class(): void
    {
        $gradeLevel = GradeLevel::where('code', 'prim_1')->firstOrFail();

        $this->actingAs($this->accountant, 'sanctum')
            ->postJson('/api/classes', [
                'grade_level_id' => $gradeLevel->id,
                'section' => 'C',
                'academic_year' => '2025-2026',
                'capacity' => 30,
            ])
            ->assertForbidden();
    }

    public function test_locations_cascade(): void
    {
        $this->seed(\Database\Seeders\RdcGeoSeeder::class);

        $provinces = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/locations/provinces')
            ->assertOk()
            ->json();

        $this->assertNotEmpty($provinces);

        $kinshasa = collect($provinces)->firstWhere('name', 'Kinshasa');
        $cities = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/locations/cities?province_id='.$kinshasa['id'])
            ->assertOk()
            ->json();

        $this->assertNotEmpty($cities);

        $cityId = $cities[0]['id'];
        $communes = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/locations/communes?city_id='.$cityId)
            ->assertOk()
            ->json();

        $this->assertNotEmpty($communes);
    }
}
