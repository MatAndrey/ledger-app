<?php

namespace Tests\Unit\Services;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\Transaction;
use App\Services\AccountService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        $account = Account::factory()->create(['type' => 'asset']);
        // Создаём транзакцию с проводками
        $this->createJournalEntries($account, 500, 200);

        $balance = $this->service->getBalance($account);
        $this->assertEquals(300, $balance);
    }

    #[Test]
    public function it_calculates_balance_for_liability_account()
    {
        $account = Account::factory()->create(['type' => 'liability']);
        // Для пассивного: кредит увеличивает, дебет уменьшает
        $this->createJournalEntries($account, 200, 500);

        $balance = $this->service->getBalance($account);
        $this->assertEquals(300, $balance); // кредит 500 - дебет 200 = 300
    }

    #[Test]
    public function it_calculates_balance_as_of_specific_date()
    {
        $account = Account::factory()->create(['type' => 'asset']);
        $date = Carbon::parse('2026-07-01');
        // Создаём проводки до и после даты
        $this->createJournalEntries($account, 100, 50, $date->subDay()); // до
        $this->createJournalEntries($account, 200, 100, $date->addDay()); // после

        $balance = $this->service->getBalance($account, $date);
        $this->assertEquals(50, $balance); // только первая транзакция
    }

    #[Test]
    public function it_generates_trial_balance()
    {
        $start = Carbon::parse('2026-07-01');
        $end = Carbon::parse('2026-07-31');

        $account1 = Account::factory()->create(['code' => 1, 'name' => 'Cash', 'type' => 'asset']);
        $account2 = Account::factory()->create(['code' => 2, 'name' => 'Revenue', 'type' => 'revenue']);

        // Транзакция внутри периода
        $this->createJournalEntries($account1, 100, 30, $start->copy()->addDay());
        $this->createJournalEntries($account2, 50, 0, $start->copy()->addDay());

        // Транзакция до периода (начальное сальдо)
        $this->createJournalEntries($account1, 200, 50, $start->copy()->subDay());

        $report = $this->service->generateTrialBalance($start, $end);

        $this->assertCount(3, $report); // два счета + итог

        $account1Row = $report->firstWhere('account.id', $account1->id);
        $this->assertEquals(150, $account1Row->opening_debit); // 200 - 50 = 150
        $this->assertEquals(100, $account1Row->debit_turnover);
        $this->assertEquals(30, $account1Row->credit_turnover);
        $this->assertEquals(250, $account1Row->closing_debit); // 150 + 100 = 250
        $this->assertEquals(30, $account1Row->closing_credit); // 0 + 30

        $account2Row = $report->firstWhere('account.id', $account2->id);
        $this->assertEquals(0, $account2Row->opening_debit);
        $this->assertEquals(50, $account2Row->debit_turnover);
        $this->assertEquals(0, $account2Row->credit_turnover);

        // Итоговая строка
        $totalRow = $report->last();
        $this->assertTrue($totalRow->is_total);
        $this->assertEquals(150, $totalRow->opening_debit);
        $this->assertEquals(0, $totalRow->opening_credit);
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
                'type' => 'debit',
            ]);
        }
        if ($credit > 0) {
            JournalEntry::factory()->create([
                'transaction_id' => $transaction->id,
                'account_id' => $account->id,
                'amount' => $credit,
                'type' => 'credit',
            ]);
        }
        return $transaction;
    }
}