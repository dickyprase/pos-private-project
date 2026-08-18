<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Livewire\ProfileManager;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class ProfileManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_authenticated_user_can_open_and_update_own_profile(): void
    {
        $cashier = User::factory()->create(['role' => UserRole::CASHIER, 'name' => 'Kasir Lama', 'email' => 'old@example.test']);
        $this->actingAs($cashier);

        $this->get('/profile')->assertOk()->assertSee('Profil Saya');

        Livewire::test(ProfileManager::class)
            ->set('name', 'Kasir Baru')
            ->set('email', 'new@example.test')
            ->set('currentPassword', 'password')
            ->set('password', 'new-password-123')
            ->set('passwordConfirmation', 'new-password-123')
            ->call('save')
            ->assertHasNoErrors();

        $cashier->refresh();
        $this->assertSame('Kasir Baru', $cashier->name);
        $this->assertSame('new@example.test', $cashier->email);
        $this->assertTrue(Hash::check('new-password-123', $cashier->password));
    }

    public function test_admin_topbar_profile_icon_links_to_profile(): void
    {
        $owner = User::factory()->create(['role' => UserRole::OWNER]);

        $this->actingAs($owner)->get('/dashboard')
            ->assertOk()
            ->assertSee('href="'.route('profile').'"', false)
            ->assertSee('Buka profil', false);
    }
}
