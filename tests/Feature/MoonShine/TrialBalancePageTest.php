<?php

declare(strict_types=1);

namespace Tests\Feature\MoonShine;

use App\Models\Account;
use App\Models\Transaction;
use App\Models\JournalEntry;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use MoonShine\Laravel\Models\MoonshineUser;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use App\Enums\JournalEntryTypes;

class TrialBalancePageTest extends TestCase
{
    use RefreshDatabase;

    private MoonshineUser $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = MoonshineUser::factory()->create();
    }

    #[Test]
    public function trial_balance_page_requires_authentication(): void
    {
        $this->get('/admin/page/trial-balance')
            ->assertRedirect('/admin/login');
    }

    #[Test]
    public function trial_balance_page_displays_data(): void
    {
        $account = Account::factory()->create(['type' => 'asset']);
        $start = Carbon::now()->startOfMonth();

        $transaction = Transaction::factory()->create(['date' => $start->addDay(), 'is_posted' => true]);
        JournalEntry::factory()->create([
            'transaction_id' => $transaction->id,
            'account_id' => $account->id,
            'amount' => 100,
            'type' => 'debit',
        ]);
        JournalEntry::factory()->create([
            'transaction_id' => $transaction->id,
            'account_id' => $account->id,
            'amount' => 50,
            'type' => 'credit',
        ]);

        $this->actingAs($this->admin, 'moonshine')
            ->get('/admin/page/trial-balance')
            ->assertOk()
            ->assertSee($account->name)
            ->assertSee('100')
            ->assertSee('50');
    }

    #[Test]
    public function trial_balance_filters_by_date_range(): void
    {
        $account = Account::factory()->create(['type' => 'asset']);

        $txInside = Transaction::factory()->create(['date' => '2026-07-15', 'is_posted' => true]);
        JournalEntry::factory()->create(['transaction_id' => $txInside->id, 'account_id' => $account->id, 'amount' => 100, 'type' => JournalEntryTypes::Debit]);

        $txOutside = Transaction::factory()->create(['date' => '2026-06-15', 'is_posted' => true]);
        JournalEntry::factory()->create(['transaction_id' => $txOutside->id, 'account_id' => $account->id, 'amount' => 200, 'type' => JournalEntryTypes::Debit]);

        $this->actingAs($this->admin, 'moonshine')
            ->get("/admin/page/trial-balance?start=2026-07-01&end=2026-07-31")
            ->assertOk()
            ->assertSee('100')
            ->assertSee('200');
    }
}