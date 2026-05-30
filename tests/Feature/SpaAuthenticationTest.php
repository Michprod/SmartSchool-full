<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpaAuthenticationTest extends TestCase
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

    public function test_login_returns_json_user_for_spa(): void
    {
        $user = User::create([
            'first_name' => 'Admin',
            'last_name' => 'Test',
            'email' => 'admin@smartschool.cd',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        $response = $this->postJson('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.email', $user->email);
        $response->assertJsonPath('data.role', 'admin');
    }

    public function test_api_user_returns_user_resource(): void
    {
        $user = User::create([
            'first_name' => 'Admin',
            'last_name' => 'Test',
            'email' => 'admin@smartschool.cd',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/user');

        $response->assertOk();
        $response->assertJsonPath('data.all_permissions', ['*']);
    }
}
