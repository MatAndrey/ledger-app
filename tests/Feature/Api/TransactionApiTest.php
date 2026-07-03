<?php

namespace Tests\Feature\Api;

use App\Models\Account;
use App\Models\User;
use App\Models\Transaction;
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
            'is_posted' => false,
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

    #[Test]
    public function it_can_view_a_transaction()
    {
        $transaction = Transaction::factory()
            ->hasJournalEntries(2)
            ->create();

        $response = $this->withHeader('Authorization', 'Basic ' . $this->auth)
            ->getJson("/api/transactions/{$transaction->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'id' => $transaction->id,
            'date' => $transaction->date,
            'description' => $transaction->description
        ]);
    }

    #[Test]
    public function it_can_update_non_posted_transaction()
    {
        $transaction = Transaction::factory()
            ->hasJournalEntries(2)
            ->create(['is_posted' => false]);

        $account = Account::factory()->create();

        $payload = [
            'date' => '2026-07-03',
            'is_posted' => true,
            'description' => 'Updated description',
            'journalEntries' => [
                ['account_id' => $account->id, 'amount' => 200, 'type' => 'debit'],
                ['account_id' => $account->id, 'amount' => 200, 'type' => 'credit'],
            ],
        ];

        $response = $this->withHeader('Authorization', 'Basic ' . $this->auth)
            ->putJson("/api/transactions/{$transaction->id}", $payload);

        $response->assertStatus(200);
        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'description' => 'Updated description',
        ]);
        $this->assertDatabaseCount('journal_entries', 2);
    }

    #[Test]
    public function it_cannot_update_posted_transaction()
    {
        $transaction = Transaction::factory()
            ->hasJournalEntries(2)
            ->create(['is_posted' => true]);

        $account1 = Account::factory()->create();
        $account2 = Account::factory()->create();

        $payload = [
            'date' => '2026-07-03',
            'is_posted' => true,
            'description' => 'Should fail',
            'journalEntries' => [
                ['account_id' => $account1->id, 'amount' => 200, 'type' => 'debit'],
                ['account_id' => $account2->id, 'amount' => 200, 'type' => 'credit'],
            ]
        ];

        $response = $this->withHeader('Authorization', 'Basic ' . $this->auth)
            ->putJson("/api/transactions/{$transaction->id}", $payload);

        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'Cannot update posted transaction',
        ]);
    }

    #[Test]
    public function it_can_delete_non_posted_transaction()
    {
        $transaction = Transaction::factory()
            ->hasJournalEntries(2)
            ->create(['is_posted' => false]);

        $response = $this->withHeader('Authorization', 'Basic ' . $this->auth)
            ->deleteJson("/api/transactions/{$transaction->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('transactions', ['id' => $transaction->id]);
        $this->assertDatabaseMissing('journal_entries', ['transaction_id' => $transaction->id]);
    }

    /** @test */
    public function it_cannot_delete_posted_transaction()
    {
        $transaction = Transaction::factory()
            ->hasJournalEntries(2)
            ->create(['is_posted' => true]);

        $response = $this->withHeader('Authorization', 'Basic ' . $this->auth)
            ->deleteJson("/api/transactions/{$transaction->id}");

        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'Cannot delete posted transaction',
        ]);
        $this->assertDatabaseHas('transactions', ['id' => $transaction->id]);
    }

    #[Test]
    public function it_validates_balance_when_updating_transaction()
    {
        $transaction = Transaction::factory()
            ->hasJournalEntries(2)
            ->create(['is_posted' => false]);

        $account = Account::factory()->create();

        $payload = [
            'journalEntries' => [
                ['account_id' => $account->id, 'amount' => 100, 'type' => 'debit'],
                ['account_id' => $account->id, 'amount' => 50, 'type' => 'credit'],
            ]
        ];

        $response = $this->withHeader('Authorization', 'Basic ' . $this->auth)
            ->putJson("/api/transactions/{$transaction->id}", $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['journalEntries']);
    }

    #[Test]
    public function it_returns_404_when_transaction_not_found()
    {
        $response = $this->withHeader('Authorization', 'Basic ' . $this->auth)
            ->getJson('/api/transactions/999');

        $response->assertStatus(404);
    }

    #[Test]
    public function it_returns_list_of_transactions()
    {
        Transaction::factory()->count(3)->create();

        $response = $this->withHeader('Authorization', 'Basic ' . $this->auth)
            ->getJson('/api/transactions');

        $response->assertStatus(200);
        $response->assertJsonCount(3);
    }
}