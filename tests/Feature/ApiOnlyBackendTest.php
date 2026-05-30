<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiOnlyBackendTest extends TestCase
{
    use RefreshDatabase;

    public function test_root_returns_api_info_json(): void
    {
        $response = $this->getJson('/');

        $response->assertOk();
        $response->assertJsonPath('service', 'SmartSchool API');
        $response->assertJsonStructure(['frontend', 'health']);
    }

    public function test_dashboard_route_no_longer_exists(): void
    {
        $response = $this->get('/dashboard');

        $response->assertNotFound();
    }

    public function test_students_page_route_no_longer_exists(): void
    {
        $response = $this->get('/students');

        $response->assertNotFound();
    }
}
