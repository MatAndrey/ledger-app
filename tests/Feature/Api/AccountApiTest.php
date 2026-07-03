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