<?php

declare(strict_types=1);

namespace Tests\Feature\MoonShine;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use MoonShine\Laravel\Models\MoonshineUser;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserResourceTest extends TestCase
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
        $this->get('/admin/resource/user-resource/user-index-page')
            ->assertRedirect('/admin/login');
    }

    #[Test]
    public function it_lists_users(): void
    {
        User::factory()->count(3)->create();

        $this->actingAs($this->admin, 'moonshine')
            ->get('/admin/resource/user-resource/user-index-page')
            ->assertOk()
            ->assertSee('Пользователи');
    }

    #[Test]
    public function it_creates_a_user(): void
    {
        $this->actingAs($this->admin, 'moonshine')
            ->post('/admin/resource/user-resource/crud', [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'password' => 'secret123',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);
    }

    #[Test]
    public function it_validates_unique_email_on_create(): void
    {
        User::factory()->create(['email' => 'existing@example.com']);

        $this->actingAs($this->admin, 'moonshine')
            ->post('/admin/resource/user-resource/crud', [
                'name' => 'Duplicate',
                'email' => 'existing@example.com',
                'password' => 'secret123',
            ])
            ->assertRedirect();

        $this->assertDatabaseMissing('users', ['name' => 'Duplicate']);
    }

    #[Test]
    public function it_updates_a_user(): void
    {
        $user = User::factory()->create(['name' => 'Old Name', 'email' => 'old@example.com']);

        $this->actingAs($this->admin, 'moonshine')
            ->put("/admin/resource/user-resource/crud/{$user->id}", [
                'name' => 'New Name',
                'email' => 'new@example.com',
                'password' => 'newpassword',
            ])
            ->assertRedirect();

        $user->refresh();
        $this->assertEquals('New Name', $user->name);
        $this->assertEquals('new@example.com', $user->email);
        $this->assertNotEquals('newpassword', $user->password);
    }

    #[Test]
    public function it_validates_unique_email_on_update(): void
    {
        $user1 = User::factory()->create(['email' => 'user1@example.com']);
        $user2 = User::factory()->create(['email' => 'user2@example.com']);

        // Попытка обновить user1 на email user2
        $this->actingAs($this->admin, 'moonshine')
            ->put("/admin/resource/user-resource/crud/{$user1->id}", [
                'name' => $user1->name,
                'email' => 'user2@example.com',
                'password' => 'secret123',
            ])
            ->assertRedirect();

        // email не должен измениться
        $user1->refresh();
        $this->assertEquals('user1@example.com', $user1->email);
    }

    #[Test]
    public function it_allows_updating_with_same_email(): void
    {
        $user = User::factory()->create(['email' => 'test@example.com']);

        $this->actingAs($this->admin, 'moonshine')
            ->put("/admin/resource/user-resource/crud/{$user->id}", [
                'name' => 'Changed Name',
                'email' => 'test@example.com',
                'password' => 'secret123',
            ])
            ->assertRedirect();

        $user->refresh();
        $this->assertEquals('Changed Name', $user->name);
        $this->assertEquals('test@example.com', $user->email);
    }

    #[Test]
    public function it_deletes_a_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($this->admin, 'moonshine')
            ->delete("/admin/resource/user-resource/crud/{$user->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    #[Test]
    public function it_requires_password_on_create(): void
    {
        $this->actingAs($this->admin, 'moonshine')
            ->post('/admin/resource/user-resource/crud', [
                'name' => 'No Password',
                'email' => 'nopass@example.com',
            ])
            ->assertRedirect();

        $this->assertDatabaseMissing('users', ['email' => 'nopass@example.com']);
    }
}