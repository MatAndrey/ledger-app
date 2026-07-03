<?php

namespace Tests\Unit\Services;

use App\Models\Account;
use App\Models\Transaction;
use App\Services\TransactionService;
use App\Enums\JournalEntryTypes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TransactionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected TransactionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(TransactionService::class);
    }

    #[Test]
    public function it_creates_transaction_with_entries()
    {
        $account1 = Account::factory()->create();
        $account2 = Account::factory()->create();

        $data = [
            'date' => '2026-07-03',
            'description' => 'Test transaction',
            'journalEntries' => [
                ['account_id' => $account1->id, 'amount' => 100.50, 'type' => JournalEntryTypes::Credit],
                ['account_id' => $account2->id, 'amount' => 100.50, 'type' => JournalEntryTypes::Debit],
            ]
        ];

        $transaction = $this->service->createTransaction($data);

        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'date' => '2026-07-03',
            'description' => 'Test transaction',
            'is_posted' => true,
        ]);

        $this->assertCount(2, $transaction->journalEntries);
    }

    #[Test]
    public function it_updates_transaction_successfully()
    {
        $transaction = Transaction::factory()
            ->hasJournalEntries(2)
            ->create(['is_posted' => false]);

        $account = Account::factory()->create();

        $newData = [
            'date' => '2026-07-04',
            'description' => 'Updated description',
            'is_posted' => true,
            'journalEntries' => [
                ['account_id' => $account->id, 'amount' => 150, 'type' => 'debit'],
                ['account_id' => $account->id, 'amount' => 150, 'type' => 'credit'],
            ]
        ];

        $updated = $this->service->updateTransaction($transaction, $newData);

        $this->assertEquals('Updated description', $updated->description);
        $this->assertEquals('2026-07-04', $updated->date);
        $this->assertTrue($updated->is_posted);
        $this->assertCount(2, $updated->journalEntries);
    }

    #[Test]
    public function it_throws_exception_when_updating_with_unbalanced_entries()
    {
        $transaction = Transaction::factory()
            ->hasJournalEntries(2)
            ->create(['is_posted' => false]);

        $account = Account::factory()->create();

        $this->expectException(ValidationException::class);

        $this->service->updateTransaction($transaction, [
            'date' => '2026-07-04',
            'description' => 'Invalid',
            'journalEntries' => [
                ['account_id' => $account->id, 'amount' => 100, 'type' => 'debit'],
                ['account_id' => $account->id, 'amount' => 50, 'type' => 'credit'],
            ]
        ]);
    }

    #[Test]
    public function it_deletes_transaction()
    {
        $transaction = Transaction::factory()
            ->hasJournalEntries(2)
            ->create(['is_posted' => false]);

        $this->service->deleteTransaction($transaction);

        $this->assertDatabaseMissing('transactions', ['id' => $transaction->id]);
        $this->assertDatabaseMissing('journal_entries', ['transaction_id' => $transaction->id]);
    }
}