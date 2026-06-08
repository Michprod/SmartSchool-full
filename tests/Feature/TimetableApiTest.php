<?php

namespace Tests\Feature;

use App\Models\ClassSubject;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimetableApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\ProductionBootstrapSeeder::class);
    }

    public function test_evaluation_sessions_route_not_captured_by_grade_show(): void
    {
        $kasongo = User::where('email', 'prof.kasongo@smartschool.cd')->firstOrFail();

        $this->actingAs($kasongo, 'sanctum')
            ->getJson('/api/grades/evaluation-sessions?academic_year=2025-2026')
            ->assertOk();
    }

    public function test_kasongo_teaching_profile_and_timetable_after_bootstrap(): void
    {
        $kasongo = User::where('email', 'prof.kasongo@smartschool.cd')->firstOrFail();

        $profile = $this->actingAs($kasongo, 'sanctum')
            ->getJson('/api/me/teaching-profile?academic_year=2025-2026');

        $profile->assertOk();
        $this->assertNotEmpty($profile->json('assignments'));
        $this->assertGreaterThan(0, $profile->json('workload.class_count'));

        $timetable = $this->actingAs($kasongo, 'sanctum')
            ->getJson('/api/me/timetable?academic_year=2025-2026');

        $timetable->assertOk();
        $this->assertNotEmpty($timetable->json('slots'));
    }

    public function test_class_timetable_returns_slots(): void
    {
        $admin = User::where('email', 'admin@smartschool.cd')->firstOrFail();
        $class = SchoolClass::query()
            ->whereHas('gradeLevel', fn ($q) => $q->where('code', 'prim_6'))
            ->where('section', 'A')
            ->firstOrFail();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson("/api/classes/{$class->id}/timetable?academic_year=2025-2026");

        $response->assertOk();
        $this->assertNotEmpty($response->json('slots'));
        $this->assertArrayHasKey('class_name', $response->json());
    }

    public function test_update_schedule_validates_format(): void
    {
        $admin = User::where('email', 'admin@smartschool.cd')->firstOrFail();
        $assignment = ClassSubject::where('is_active', true)->firstOrFail();

        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/class-subjects/{$assignment->id}/schedule", [
                'schedule' => [
                    'monday' => [['start' => '09:00', 'end' => '08:00', 'room' => 'A1']],
                ],
            ])
            ->assertStatus(422);
    }

    public function test_update_schedule_persists_and_returns_warnings_key(): void
    {
        $admin = User::where('email', 'admin@smartschool.cd')->firstOrFail();
        $assignment = ClassSubject::where('is_active', true)->firstOrFail();

        $response = $this->actingAs($admin, 'sanctum')
            ->putJson("/api/class-subjects/{$assignment->id}/schedule", [
                'schedule' => [
                    'tuesday' => [['start' => '10:00', 'end' => '11:00', 'room' => 'Labo 2']],
                ],
            ]);

        $response->assertOk();
        $response->assertJsonStructure(['data', 'warnings']);
        $this->assertIsArray($response->json('warnings'));

        $assignment->refresh();
        $this->assertEquals('10:00', $assignment->schedule['tuesday'][0]['start']);
    }

    public function test_detect_teacher_overlap_conflict(): void
    {
        $admin = User::where('email', 'admin@smartschool.cd')->firstOrFail();
        $teacher = User::where('role', 'teacher')->firstOrFail();

        $assignments = ClassSubject::query()
            ->where('teacher_id', $teacher->id)
            ->where('academic_year', '2025-2026')
            ->where('is_active', true)
            ->limit(2)
            ->get();

        if ($assignments->count() < 2) {
            $this->markTestSkipped('Need at least 2 assignments for the same teacher.');
        }

        $overlap = [
            'monday' => [['start' => '08:30', 'end' => '09:30', 'room' => 'Salle X']],
        ];

        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/class-subjects/{$assignments[0]->id}/schedule", ['schedule' => $overlap])
            ->assertOk();

        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/class-subjects/{$assignments[1]->id}/schedule", ['schedule' => $overlap])
            ->assertOk();

        $conflicts = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/timetable/conflicts?academic_year=2025-2026');

        $conflicts->assertOk();
        $types = collect($conflicts->json('conflicts'))->pluck('type');
        $this->assertTrue($types->contains('teacher_overlap'));
    }

    public function test_timetable_conflicts_endpoint_for_admin(): void
    {
        $admin = User::where('email', 'admin@smartschool.cd')->firstOrFail();

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/timetable/conflicts?academic_year=2025-2026')
            ->assertOk()
            ->assertJsonStructure(['academic_year', 'conflicts']);
    }
}
