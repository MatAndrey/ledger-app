<?php

namespace Tests\Unit\Services;

use App\Models\Account;
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
}