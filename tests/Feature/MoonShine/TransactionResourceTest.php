<?php

declare(strict_types=1);

namespace Tests\Feature\MoonShine;

use App\Models\Account;
use App\Models\Transaction;
use App\Models\JournalEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use MoonShine\Laravel\Models\MoonshineUser;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TransactionResourceTest extends TestCase
{
    use RefreshDatabase;

    private MoonshineUser $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = MoonshineUser::factory()->create();
    }

    #[Test]
    public function index_page_requires_authentication(): void
    {
        $this->get('/admin/resource/transaction-resource/transaction-index-page')
            ->assertRedirect('/admin/login');
    }

    #[Test]
    public function it_lists_transactions(): void
    {
        Transaction::factory()->count(3)->create();

        $this->actingAs($this->admin, 'moonshine')
            ->get('/admin/resource/transaction-resource/transaction-index-page')
            ->assertOk()
            ->assertSee('Транзакции');
    }

    #[Test]
    public function it_creates_a_transaction_with_entries(): void
    {
        $account1 = Account::factory()->create();
        $account2 = Account::factory()->create();

        $this->actingAs($this->admin, 'moonshine')
            ->post('/admin/resource/transaction-resource/crud', [
                'date' => '2026-07-05',
                'description' => 'Test Transaction',
                'journalEntries' => [
                    ['account_id' => $account1->id, 'amount' => 100.50, 'type' => 'debit'],
                    ['account_id' => $account2->id, 'amount' => 100.50, 'type' => 'credit'],
                ]
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('transactions', ['description' => 'Test Transaction']);
        $this->assertDatabaseCount('journal_entries', 2);
    }

    #[Test]
    public function it_rejects_transaction_with_unbalanced_entries(): void
    {
        $account1 = Account::factory()->create();
        $account2 = Account::factory()->create();

        $this->actingAs($this->admin, 'moonshine')
            ->post('/admin/resource/transaction-resource/crud', [
                'date' => '2026-07-05',
                'description' => 'Invalid',
                'journalEntries' => [
                    ['account_id' => $account1->id, 'amount' => 100, 'type' => 'debit'],
                    ['account_id' => $account2->id, 'amount' => 50, 'type' => 'credit'],
                ]
            ])
            ->assertRedirect();

        $this->assertDatabaseMissing('transactions', ['description' => 'Invalid']);
    }

    #[Test]
    public function it_requires_at_least_two_entries(): void
    {
        $account = Account::factory()->create();

        $this->actingAs($this->admin, 'moonshine')
            ->post('/admin/resource/transaction-resource/crud', [
                'date' => '2026-07-05',
                'description' => 'Single entry',
                'journalEntries' => [
                    ['account_id' => $account->id, 'amount' => 100, 'type' => 'debit'],
                ]
            ])
            ->assertRedirect();

        $this->assertDatabaseMissing('transactions', ['description' => 'Single entry']);
    }

    #[Test]
    public function it_updates_a_non_posted_transaction(): void
    {
        $transaction = Transaction::factory()
            ->hasJournalEntries(2)
            ->create(['is_posted' => false]);

        $account = Account::factory()->create();

        $this->actingAs($this->admin, 'moonshine')
            ->put("/admin/resource/transaction-resource/crud/{$transaction->id}", [
                'date' => '2026-07-06',
                'description' => 'Updated',
                'journalEntries' => [
                    ['account_id' => $account->id, 'amount' => 200, 'type' => 'debit'],
                    ['account_id' => $account->id, 'amount' => 200, 'type' => 'credit'],
                ]
            ])
            ->assertRedirect();

        $transaction->refresh();
        $this->assertEquals('Updated', $transaction->description);
        $this->assertCount(2, $transaction->journalEntries);
    }

    #[Test]
    public function it_cannot_update_a_posted_transaction(): void
    {
        $transaction = Transaction::factory()
            ->hasJournalEntries(2)
            ->create(['is_posted' => true]);

        $this->actingAs($this->admin, 'moonshine')
            ->put("/admin/resource/transaction-resource/crud/{$transaction->id}", [
                'date' => '2026-07-06',
                'description' => 'Should fail',
                'journalEntries' => [
                    ['account_id' => $transaction->journalEntries->first()->account_id, 'amount' => 100, 'type' => 'debit'],
                    ['account_id' => $transaction->journalEntries->first()->account_id, 'amount' => 100, 'type' => 'credit'],
                ]
            ])
            ->assertForbidden();

        $transaction->refresh();
        $this->assertNotEquals('Should fail', $transaction->description);
    }

    #[Test]
    public function it_deletes_a_non_posted_transaction(): void
    {
        $transaction = Transaction::factory()
            ->hasJournalEntries(2)
            ->create(['is_posted' => false]);

        $this->actingAs($this->admin, 'moonshine')
            ->delete("/admin/resource/transaction-resource/crud/{$transaction->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('transactions', ['id' => $transaction->id]);
        $this->assertDatabaseMissing('journal_entries', ['transaction_id' => $transaction->id]);
    }

    #[Test]
    public function it_cannot_delete_a_posted_transaction(): void
    {
        $transaction = Transaction::factory()
            ->hasJournalEntries(2)
            ->create(['is_posted' => true]);

        $this->actingAs($this->admin, 'moonshine')
            ->delete("/admin/resource/transaction-resource/crud/{$transaction->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('transactions', ['id' => $transaction->id]);
    }
}