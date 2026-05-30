<?php

namespace Tests\Feature\Auth;

use App\Support\FrontendUrl;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_redirects_to_frontend(): void
    {
        $response = $this->get('/register');

        $response->assertRedirect(FrontendUrl::to('register'));
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(FrontendUrl::to('dashboard'));
    }
}
