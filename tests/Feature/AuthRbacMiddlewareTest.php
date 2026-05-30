<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthRbacMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create([
            'name' => 'Reader',
            'slug' => 'reader',
            'description' => 'Read only',
            'permissions' => ['users:read'],
        ]);

        Role::create([
            'name' => 'Writer',
            'slug' => 'writer',
            'description' => 'Write only',
            'permissions' => ['users:write'],
        ]);
    }

    public function test_guest_cannot_access_protected_user_route(): void
    {
        $this->getJson('/api/users')
            ->assertStatus(401);
    }

    public function test_read_only_user_can_read_but_cannot_write_users(): void
    {
        $reader = User::create([
            'first_name' => 'Read',
            'last_name' => 'Only',
            'email' => 'reader@example.test',
            'password' => bcrypt('password'),
            'role' => 'reader',
            'is_active' => true,
        ]);

        $this->actingAs($reader, 'sanctum')
            ->getJson('/api/users')
            ->assertOk();

        $this->actingAs($reader, 'sanctum')
            ->postJson('/api/users', [
                'first_name' => 'No',
                'last_name' => 'Write',
                'email' => 'no-write@example.test',
                'password' => 'password123',
                'role' => 'reader',
            ])
            ->assertStatus(403);
    }
}
