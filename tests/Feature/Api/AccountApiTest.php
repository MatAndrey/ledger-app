<?php

namespace Tests\Feature\Api;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\Transaction;
use App\Models\User;
use App\Enums\AccountTypes;
use App\Enums\JournalEntryTypes;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class AccountApiTest extends TestCase
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
    public function it_returns_list_of_accounts()
    {
        Account::factory()->count(3)->create();

        $response = $this->withHeader('Authorization', 'Basic ' . $this->auth)
            ->getJson('/api/accounts');

        $response->assertStatus(200);
        $response->assertJsonCount(3);
    }

    #[Test]
    public function it_returns_balance_for_account()
    {
        $account = Account::factory()->create(['type' => AccountTypes::Asset]);

        $this->createJournalEntries($account, 500, 200);

        $response = $this->withHeader('Authorization', 'Basic ' . $this->auth)
            ->getJson("/api/accounts/{$account->id}/balance");

        $response->assertStatus(200);
        $response->assertJson([
            'account_id' => $account->id,
            'balance' => 300,
        ]);
    }

    #[Test]
    public function it_returns_balance_as_of_specific_date()
    {
        $account = Account::factory()->create(['type' => AccountTypes::Asset]);
        $date = Carbon::parse('2026-07-01');

        $this->createJournalEntries($account, 100, 50, $date->copy()->subDay());
        $this->createJournalEntries($account, 200, 100, $date->copy()->addDay());

        $response = $this->withHeader('Authorization', 'Basic ' . $this->auth)
            ->getJson("/api/accounts/{$account->id}/balance?asOf={$date->toDateString()}");

        $response->assertStatus(200);
        $response->assertJson([
            'account_id' => $account->id,
            'balance' => 50,
        ]);
    }

    #[Test]
    public function it_returns_trial_balance_as_json()
    {
        $start = Carbon::parse('2026-07-01');
        $end = Carbon::parse('2026-07-31');

        $account1 = Account::factory()->create(['code' => 1, 'name' => 'Cash', 'type' => AccountTypes::Asset]);
        $account2 = Account::factory()->create(['code' => 2, 'name' => 'Revenue', 'type' => AccountTypes::Revenue]);

        $this->createJournalEntries($account1, 100, 30, $start->copy()->addDay());
        $this->createJournalEntries($account2, 50, 0, $start->copy()->addDay());
        $this->createJournalEntries($account1, 200, 50, $start->copy()->subDay());

        $response = $this->withHeader('Authorization', 'Basic ' . $this->auth)
            ->getJson("/api/accounts/trial-balance?start={$start->toDateString()}&end={$end->toDateString()}");

        $response->assertStatus(200);
        $response->assertJsonCount(3);

        $data = $response->json();
        $total = end($data);
        $this->assertTrue($total['is_total']);
        $this->assertEquals(200, $total['opening_debit']);
    }

    #[Test]
    public function it_can_create_an_account()
    {
        $payload = [
            'name' => 'Test Account',
            'code' => 100,
            'type' => 'asset',
            'is_active' => true,
        ];

        $response = $this->withHeader('Authorization', 'Basic ' . $this->auth)
            ->postJson('/api/accounts', $payload);

        $response->assertStatus(201);
        $this->assertDatabaseHas('accounts', [
            'name' => 'Test Account',
            'code' => 100,
            'type' => 'asset',
            'is_active' => true,
        ]);
    }

    #[Test]
    public function it_can_view_an_account()
    {
        $account = Account::factory()->create();

        $response = $this->withHeader('Authorization', 'Basic ' . $this->auth)
            ->getJson("/api/accounts/{$account->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'id' => $account->id,
            'name' => $account->name,
            'code' => $account->code,
            'type' => $account->type->value,
            'is_active' => $account->is_active,
        ]);
    }

    #[Test]
    public function it_can_update_an_account()
    {
        $account = Account::factory()->create();

        $payload = [
            'name' => 'Updated Account',
            'code' => 200,
            'type' => 'liability',
            'is_active' => false,
        ];

        $response = $this->withHeader('Authorization', 'Basic ' . $this->auth)
            ->putJson("/api/accounts/{$account->id}", $payload);

        $response->assertStatus(200);
        $this->assertDatabaseHas('accounts', [
            'id' => $account->id,
            'name' => 'Updated Account',
            'code' => 200,
            'type' => 'liability',
            'is_active' => false,
        ]);
    }

    #[Test]
    public function it_can_delete_an_account()
    {
        $account = Account::factory()->create();

        $response = $this->withHeader('Authorization', 'Basic ' . $this->auth)
            ->deleteJson("/api/accounts/{$account->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('accounts', ['id' => $account->id]);
    }

    #[Test]
    public function it_returns_404_when_account_not_found()
    {
        $response = $this->withHeader('Authorization', 'Basic ' . $this->auth)
            ->getJson('/api/accounts/999');

        $response->assertStatus(404);
    }

    #[Test]
    public function it_validates_required_fields_when_creating_account()
    {
        $response = $this->withHeader('Authorization', 'Basic ' . $this->auth)
            ->postJson('/api/accounts', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name', 'code', 'type']);
    }

    #[Test]
    public function it_validates_unique_code_when_creating_account()
    {
        Account::factory()->create(['code' => 100]);

        $payload = [
            'name' => 'Duplicate Code',
            'code' => 100,
            'type' => 'asset',
        ];

        $response = $this->withHeader('Authorization', 'Basic ' . $this->auth)
            ->postJson('/api/accounts', $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['code']);
    }

    #[Test]
    public function it_validates_type_enum_when_creating_account()
    {
        $payload = [
            'name' => 'Invalid Type',
            'code' => 101,
            'type' => 'invalid',
        ];

        $response = $this->withHeader('Authorization', 'Basic ' . $this->auth)
            ->postJson('/api/accounts', $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['type']);
    }

    protected function createJournalEntries(Account $account, float $debit, float $credit, ?Carbon $date = null)
    {
        $date = $date ?? Carbon::now();
        $transaction = Transaction::factory()->create(['date' => $date, 'is_posted' => true]);

        if ($debit > 0) {
            JournalEntry::factory()->create([
                'transaction_id' => $transaction->id,
                'account_id' => $account->id,
                'amount' => $debit,
                'type' => JournalEntryTypes::Debit,
            ]);
        }
        if ($credit > 0) {
            JournalEntry::factory()->create([
                'transaction_id' => $transaction->id,
                'account_id' => $account->id,
                'amount' => $credit,
                'type' => JournalEntryTypes::Credit,
            ]);
        }
        return $transaction;
    }
}