<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Support\FrontendUrl;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_redirects_to_frontend(): void
    {
        $response = $this->get('/login');

        $response->assertRedirect(FrontendUrl::to('login'));
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->postJson('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertOk();
        $response->assertJsonPath('data.email', $user->email);
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/logout');

        $this->assertGuest();
        $response->assertOk();
        $response->assertJsonPath('message', 'Logged out.');
    }
}
