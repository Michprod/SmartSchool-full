<?php

namespace Tests\Feature;

use App\Models\Personnel;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PersonnelApiTest extends TestCase
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

        Role::create([
            'name' => 'Secrétaire',
            'slug' => 'secretary',
            'permissions' => ['personnel:*', 'users:read'],
        ]);
    }

    public function test_admin_can_create_personnel_with_auto_user(): void
    {
        $admin = User::create([
            'first_name' => 'Admin',
            'last_name' => 'Test',
            'email' => 'admin@test.cd',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/personnel', [
            'staff_type' => 'teacher',
            'first_name' => 'Paul',
            'last_name' => 'Kabila',
            'email' => 'paul.kabila@test.cd',
            'password' => 'password123',
            'department' => 'Sciences',
            'job_title' => 'Prof Maths',
            'workload_hours' => 20,
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.staff_type', 'teacher');
        $response->assertJsonPath('data.user.email', 'paul.kabila@test.cd');

        $this->assertDatabaseHas('personnel', ['first_name' => 'Paul', 'staff_type' => 'teacher']);
        $this->assertDatabaseHas('users', ['email' => 'paul.kabila@test.cd', 'role' => 'teacher']);
    }

    public function test_duplicate_email_rejected(): void
    {
        $admin = User::create([
            'first_name' => 'Admin',
            'last_name' => 'Test',
            'email' => 'admin2@test.cd',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        User::create([
            'first_name' => 'Exist',
            'last_name' => 'User',
            'email' => 'dup@test.cd',
            'password' => bcrypt('password'),
            'role' => 'teacher',
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'sanctum')->postJson('/api/personnel', [
            'staff_type' => 'teacher',
            'first_name' => 'New',
            'last_name' => 'Teacher',
            'email' => 'dup@test.cd',
            'password' => 'password123',
        ])->assertUnprocessable();
    }

    public function test_teaching_profile_for_teacher_personnel(): void
    {
        $admin = User::create([
            'first_name' => 'Admin',
            'last_name' => 'Test',
            'email' => 'admin3@test.cd',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        $create = $this->actingAs($admin, 'sanctum')->postJson('/api/personnel', [
            'staff_type' => 'teacher',
            'first_name' => 'Marie',
            'last_name' => 'Tshisekedi',
            'email' => 'marie@test.cd',
            'password' => 'password123',
            'workload_hours' => 18,
        ]);

        $personnelId = $create->json('data.id');

        $this->actingAs($admin, 'sanctum')
            ->getJson("/api/personnel/{$personnelId}/teaching-profile")
            ->assertOk()
            ->assertJsonStructure(['user', 'assignments', 'workload']);
    }

    public function test_user_store_rejects_teacher_role(): void
    {
        $admin = User::create([
            'first_name' => 'Admin',
            'last_name' => 'Test',
            'email' => 'admin4@test.cd',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'sanctum')->postJson('/api/users', [
            'first_name' => 'Bad',
            'last_name' => 'Teacher',
            'email' => 'bad@test.cd',
            'password' => 'password123',
            'role' => 'teacher',
        ])->assertStatus(422);
    }
}
