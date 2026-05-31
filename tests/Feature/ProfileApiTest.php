<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\RdcProvince;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create([
            'name' => 'Administrateur',
            'slug' => 'admin',
            'permissions' => ['*'],
            'description' => 'Accès complet au système',
        ]);

        Role::create([
            'name' => 'Enseignant',
            'slug' => 'teacher',
            'permissions' => ['grades:read', 'grades:write'],
            'description' => 'Saisie des notes',
        ]);
    }

    public function test_get_user_returns_enriched_profile_fields(): void
    {
        $user = User::create([
            'first_name' => 'Marie',
            'last_name' => 'Dupont',
            'email' => 'marie@test.cd',
            'password' => bcrypt('password'),
            'role' => 'teacher',
            'phone' => '+243900000001',
            'birth_date' => '1990-05-15',
            'address' => '12 Av. Test',
            'city' => 'Kinshasa',
            'province' => 'Kinshasa',
            'bio' => 'Enseignante de mathématiques',
            'department' => 'Sciences',
            'job_title' => 'Professeur',
            'workload_hours' => 24,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/user');

        $response->assertOk();
        $response->assertJsonPath('data.first_name', 'Marie');
        $response->assertJsonPath('data.birth_date', '1990-05-15');
        $response->assertJsonPath('data.address', '12 Av. Test');
        $response->assertJsonPath('data.city', 'Kinshasa');
        $response->assertJsonPath('data.province', 'Kinshasa');
        $response->assertJsonPath('data.bio', 'Enseignante de mathématiques');
        $response->assertJsonPath('data.department', 'Sciences');
        $response->assertJsonPath('data.workload_hours', 24);
        $response->assertJsonPath('data.is_active', true);
        $response->assertJsonStructure([
            'data' => [
                'id', 'email', 'role', 'last_login', 'created_at',
                'all_permissions', 'role_info' => ['slug', 'name'],
            ],
        ]);
    }

    public function test_user_can_self_update_address_fields(): void
    {
        $province = RdcProvince::create(['name' => 'Kinshasa', 'code' => 'KN']);

        $user = User::create([
            'first_name' => 'Jean',
            'last_name' => 'Test',
            'email' => 'jean@test.cd',
            'password' => bcrypt('password'),
            'role' => 'teacher',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user, 'sanctum')->putJson("/api/users/{$user->id}", [
            'address' => '45 Bd Lumumba',
            'province_id' => $province->id,
            'quartier' => 'Gombe',
            'bio' => 'Nouvelle bio',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.address', '45 Bd Lumumba');
        $response->assertJsonPath('data.province', 'Kinshasa');
        $response->assertJsonPath('data.quartier', 'Gombe');
        $response->assertJsonPath('data.bio', 'Nouvelle bio');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'address' => '45 Bd Lumumba',
            'province_id' => $province->id,
            'quartier' => 'Gombe',
        ]);
    }

    public function test_user_cannot_change_own_role(): void
    {
        $user = User::create([
            'first_name' => 'Jean',
            'last_name' => 'Test',
            'email' => 'jean2@test.cd',
            'password' => bcrypt('password'),
            'role' => 'teacher',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user, 'sanctum')->putJson("/api/users/{$user->id}", [
            'role' => 'admin',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseHas('users', ['id' => $user->id, 'role' => 'teacher']);
    }

    public function test_password_change_requires_current_password(): void
    {
        $user = User::create([
            'first_name' => 'Jean',
            'last_name' => 'Test',
            'email' => 'pwd@test.cd',
            'password' => bcrypt('old-password'),
            'role' => 'teacher',
            'is_active' => true,
        ]);

        $this->actingAs($user, 'sanctum')->putJson('/api/me/password', [
            'current_password' => 'wrong-password',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertUnprocessable();

        $this->actingAs($user, 'sanctum')->putJson('/api/me/password', [
            'current_password' => 'old-password',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertOk()
            ->assertJsonPath('message', 'Mot de passe modifié avec succès.');

        $user->refresh();
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('new-password-123', $user->password));
    }
}
