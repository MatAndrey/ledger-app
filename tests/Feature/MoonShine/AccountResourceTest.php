<?php

declare(strict_types=1);

namespace Tests\Feature\MoonShine;

use App\Enums\AccountTypes;
use App\Models\Account;
use Illuminate\Foundation\Testing\RefreshDatabase;
use MoonShine\Laravel\Models\MoonshineUser;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AccountResourceTest extends TestCase
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
        $this->get('/admin/resource/account-resource/account-index-page')
            ->assertRedirect('/admin/login');
    }

    #[Test]
    public function it_lists_accounts(): void
    {
        Account::factory()->count(3)->create();

        $this->actingAs($this->admin, 'moonshine')
            ->get('/admin/resource/account-resource/account-index-page')
            ->assertOk()
            ->assertSee('Счета');
    }

    #[Test]
    public function it_creates_an_account(): void
    {
        $this->actingAs($this->admin, 'moonshine')
            ->post('/admin/resource/account-resource/crud', [
                'name' => 'Test Account',
                'code' => 100,
                'type' => AccountTypes::Asset->value,
                'is_active' => '1',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('accounts', [
            'name' => 'Test Account',
            'code' => 100,
            'type' => 'asset',
            'is_active' => true,
        ]);
    }

    #[Test]
    public function it_validates_unique_code_on_create(): void
    {
        Account::factory()->create(['code' => 100]);

        $this->actingAs($this->admin, 'moonshine')
            ->post('/admin/resource/account-resource/crud', [
                'name' => 'Duplicate',
                'code' => 100,
                'type' => AccountTypes::Liability->value,
                'is_active' => '1',
            ])
            ->assertRedirect(); // MoonShine редиректит с ошибкой в сессии

        $this->assertDatabaseMissing('accounts', ['name' => 'Duplicate']);
    }

    #[Test]
    public function it_updates_an_account(): void
    {
        $account = Account::factory()->create(['name' => 'Old Name', 'code' => 200]);

        $this->actingAs($this->admin, 'moonshine')
            ->put("/admin/resource/account-resource/crud/{$account->id}", [
                'name' => 'Updated Name',
                'code' => $account->code,
                'type' => $account->type->value,
                'is_active' => '0',
            ])
            ->assertRedirect();

        $account->refresh();
        $this->assertEquals('Updated Name', $account->name);
        $this->assertFalse($account->is_active);
    }

    #[Test]
    public function it_deletes_an_account(): void
    {
        $account = Account::factory()->create();

        $this->actingAs($this->admin, 'moonshine')
            ->delete("/admin/resource/account-resource/crud/{$account->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('accounts', ['id' => $account->id]);
    }
}