<?php

namespace Tests\Feature\Api;

use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class TransactionApiTest extends TestCase
{
    use RefreshDatabase;

    protected string $auth;

    protected function setUp(): void
    {
        parent::setUp();
        User::factory()->create([
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
        ]);
        $this->auth = base64_encode('admin@example.com:password');
    }

    #[Test]
    public function it_creates_transaction_successfully()
    {
        $account1 = Account::factory()->create();
        $account2 = Account::factory()->create();

        $payload = [
            'date' => '2026-07-03',
            'description' => 'Test transaction',
            'journalEntries' => [
                ['account_id' => $account1->id, 'amount' => 100.50, 'type' => 'credit'],
                ['account_id' => $account2->id, 'amount' => 100.50, 'type' => 'debit'],
            ]
        ];

        $response = $this->withHeader('Authorization', 'Basic ' . $this->auth)
            ->postJson('/api/transactions', $payload);

        $response->assertStatus(201);
        $response->assertJson(['message' => 'Transaction created successfully']);
        $this->assertDatabaseHas('transactions', ['description' => 'Test transaction']);
        $this->assertDatabaseCount('journal_entries', 2);
    }

    #[Test]
    public function it_returns_422_when_debit_not_equal_credit()
    {
        $account1 = Account::factory()->create();
        $account2 = Account::factory()->create();

        $payload = [
            'date' => '2026-07-03',
            'description' => 'Invalid',
            'journalEntries' => [
                ['account_id' => $account1->id, 'amount' => 100, 'type' => 'debit'],
                ['account_id' => $account2->id, 'amount' => 50, 'type' => 'credit'],
            ]
        ];

        $response = $this->withHeader('Authorization', 'Basic ' . $this->auth)
            ->postJson('/api/transactions', $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['journalEntries']);
    }

    #[Test]
    public function it_requires_at_least_two_entries()
    {
        $account = Account::factory()->create();

        $payload = [
            'date' => '2026-07-03',
            'description' => 'One entry',
            'journalEntries' => [
                ['account_id' => $account->id, 'amount' => 100, 'type' => 'debit'],
            ]
        ];

        $response = $this->withHeader('Authorization', 'Basic ' . $this->auth)
            ->postJson('/api/transactions', $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['journalEntries']);
    }

    #[Test]
    public function it_validates_account_exists_and_is_active()
    {
        $account = Account::factory()->create(['is_active' => false]);

        $payload = [
            'date' => '2026-07-03',
            'description' => 'Test',
            'journalEntries' => [
                ['account_id' => $account->id, 'amount' => 100, 'type' => 'debit'],
                ['account_id' => $account->id, 'amount' => 100, 'type' => 'credit'],
            ]
        ];

        $response = $this->withHeader('Authorization', 'Basic ' . $this->auth)
            ->postJson('/api/transactions', $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['journalEntries.0.account_id']);
    }

    #[Test]
    public function it_validates_required_fields()
    {
        $response = $this->withHeader('Authorization', 'Basic ' . $this->auth)
            ->postJson('/api/transactions', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['date', 'journalEntries']);
    }
}