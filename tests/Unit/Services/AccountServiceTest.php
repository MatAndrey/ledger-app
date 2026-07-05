<?php

namespace Tests\Unit\Services;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\Transaction;
use App\Services\AccountService;
use App\Enums\AccountTypes;
use App\Enums\JournalEntryTypes;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AccountServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AccountService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(AccountService::class);
    }

    #[Test]
    public function it_calculates_balance_for_asset_account()
    {
        $account = Account::factory()->create(['type' => AccountTypes::Asset]);
        // Создаём транзакцию с проводками
        $this->createJournalEntries($account, 500, 200);

        $balance = $this->service->getBalance($account);
        $this->assertEquals(300, $balance);
    }

    #[Test]
    public function it_calculates_balance_for_liability_account()
    {
        $account = Account::factory()->create(['type' => AccountTypes::Liability]);
        // Для пассивного: кредит увеличивает, дебет уменьшает
        $this->createJournalEntries($account, 200, 500);

        $balance = $this->service->getBalance($account);
        $this->assertEquals(300, $balance); // кредит 500 - дебет 200 = 300
    }

    #[Test]
    public function it_calculates_balance_as_of_specific_date()
    {
        $account = Account::factory()->create(['type' => AccountTypes::Asset]);
        $date = Carbon::parse('2026-07-01');
        // Создаём проводки до и после даты
        $this->createJournalEntries($account, 100, 50, $date->copy()->subDay()); // до
        $this->createJournalEntries($account, 200, 100, $date->copy()->addDays()); // после

        $balance = $this->service->getBalance($account, $date);
        $this->assertEquals(50, $balance); // только первая транзакция
    }

    #[Test]
    public function it_generates_trial_balance()
    {
        $start = Carbon::parse('2026-07-01');
        $end = Carbon::parse('2026-07-31');

        $account1 = Account::factory()->create(['code' => 1, 'name' => 'Cash', 'type' => AccountTypes::Asset]);
        $account2 = Account::factory()->create(['code' => 2, 'name' => 'Revenue', 'type' => AccountTypes::Revenue]);

        // Транзакция внутри периода
        $this->createJournalEntries($account1, 100, 30, $start->copy()->addDay());
        $this->createJournalEntries($account2, 50, 0, $start->copy()->addDay());

        // Транзакция до периода (начальное сальдо)
        $this->createJournalEntries($account1, 200, 50, $start->copy()->subDay());

        $report = $this->service->generateTrialBalance($start, $end);

        $this->assertCount(3, $report); // два счета + итог

        $account1Row = $report->firstWhere('account.id', $account1->id);
        $this->assertEquals(200, $account1Row->opening_debit);
        $this->assertEquals(100, $account1Row->debit_turnover);
        $this->assertEquals(30, $account1Row->credit_turnover);
        $this->assertEquals(300, $account1Row->closing_debit);
        $this->assertEquals(80, $account1Row->closing_credit);

        $account2Row = $report->firstWhere('account.id', $account2->id);
        $this->assertEquals(0, $account2Row->opening_debit);
        $this->assertEquals(50, $account2Row->debit_turnover);
        $this->assertEquals(0, $account2Row->credit_turnover);

        $totalRow = $report->last();
        $this->assertTrue($totalRow->is_total);
        $this->assertEquals(200, $totalRow->opening_debit);
        $this->assertEquals(50, $totalRow->opening_credit);
        $this->assertEquals(150, $totalRow->debit_turnover);
        $this->assertEquals(30, $totalRow->credit_turnover);
    }

    // Вспомогательный метод для создания проводок
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

    #[Test]
    public function it_creates_account_successfully()
    {
        $data = [
            'name' => 'New Account',
            'code' => 123,
            'type' => 'asset',
            'is_active' => true,
        ];

        $account = $this->service->createAccount($data);

        $this->assertDatabaseHas('accounts', [
            'id' => $account->id,
            'name' => 'New Account',
            'code' => 123,
            'type' => 'asset',
            'is_active' => true,
        ]);
    }

    #[Test]
    public function it_updates_account_successfully()
    {
        $account = Account::factory()->create(['code' => 10, 'name' => 'Old Name']);

        $updated = $this->service->updateAccount($account, [
            'name' => 'New Name',
            'code' => 20,
            'type' => 'liability',
            'is_active' => false,
        ]);

        $this->assertEquals('New Name', $updated->name);
        $this->assertEquals(20, $updated->code);
        $this->assertEquals('liability', $updated->type);
        $this->assertFalse($updated->is_active);
    }

    #[Test]
    public function it_deletes_account()
    {
        $account = Account::factory()->create();

        $this->service->destroyAccount($account);

        $this->assertDatabaseMissing('accounts', ['id' => $account->id]);
    }

    #[Test]
    public function it_gets_all_accounts()
    {
        Account::factory()->count(3)->create();

        $accounts = $this->service->getAllAccounts();

        $this->assertCount(3, $accounts);
    }
}