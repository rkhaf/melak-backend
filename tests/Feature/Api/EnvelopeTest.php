<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnvelopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_request_returns_401_envelope(): void
    {
        $response = $this->postJson('/api/logout');

        $response->assertStatus(401)
            ->assertJson(['success' => false, 'message' => 'Tidak terautentikasi. Sertakan token yang valid.']);
    }

    public function test_missing_api_route_returns_404_envelope(): void
    {
        $response = $this->getJson('/api/tidak-ada');

        $response->assertStatus(404)
            ->assertJson(['success' => false]);
    }

    public function test_validation_error_returns_422_envelope(): void
    {
        $response = $this->postJson('/api/login', []);

        $response->assertStatus(422)
            ->assertJson(['success' => false, 'message' => 'Validasi gagal']);
        $this->assertArrayHasKey('errors', $response->json());
    }
}
