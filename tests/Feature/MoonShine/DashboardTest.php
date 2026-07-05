<?php

declare(strict_types=1);

namespace Tests\Feature\MoonShine;

use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use MoonShine\Laravel\Models\MoonshineUser;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private MoonshineUser $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = MoonshineUser::factory()->create();
    }

    #[Test]
    public function dashboard_page_requires_authentication(): void
    {
        $this->get('/admin')
            ->assertRedirect('/admin/login');
    }

    #[Test]
    public function dashboard_displays_metrics(): void
    {
        Account::factory()->count(5)->create();
        Transaction::factory()->count(3)->create();
        User::factory()->count(2)->create();

        $this->actingAs($this->admin, 'moonshine')
            ->get('/admin')
            ->assertOk()
            ->assertSee('Всего счетов')
            ->assertSee('5')
            ->assertSee('Всего транзакций')
            ->assertSee('3')
            ->assertSee('Всего пользователей')
            ->assertSee('2');
    }

    #[Test]
    public function dashboard_contains_links_to_api_docs_and_trial_balance(): void
    {
        $this->actingAs($this->admin, 'moonshine')
            ->get('/admin')
            ->assertOk()
            ->assertSee('Документация API')
            ->assertSee('Оборотно-сальдовая ведомость')
            ->assertSee('/docs/api')
            ->assertSee('/admin/page/trial-balance');
    }
}