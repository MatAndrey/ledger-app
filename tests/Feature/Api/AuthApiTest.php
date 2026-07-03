<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        User::factory()->create([
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
        ]);
    }

    #[Test]
    public function it_returns_401_if_not_authenticated()
    {
        $response = $this->getJson('/api/accounts');
        $response->assertStatus(401);
        $response->assertJson(['message' => 'Unauthenticated. Please provide valid credentials.']);
    }

    #[Test]
    public function it_authenticates_with_valid_credentials()
    {
        $auth = base64_encode('admin@example.com:password');
        $response = $this->withHeader('Authorization', 'Basic ' . $auth)
            ->getJson('/api/accounts');

        $response->assertStatus(200);
    }
}