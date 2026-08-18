<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Livewire\CategoryManager;
use App\Livewire\InventoryManager;
use App\Livewire\ProductManager;
use App\Livewire\UserManager;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class UserManagementSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_edit_user_role_status_and_reset_password(): void
    {
        $owner = User::factory()->create(['role' => UserRole::OWNER]);
        $cashier = User::factory()->create(['role' => UserRole::CASHIER, 'name' => 'Kasir Lama', 'username' => 'kasir-lama', 'is_active' => true]);
        $this->actingAs($owner);

        Livewire::test(UserManager::class)
            ->call('editUser', $cashier->id)
            ->assertSet('editOpen', true)
            ->set('editName', 'Kasir Baru')
            ->set('editUsername', 'kasir-baru')
            ->set('editEmail', 'kasir-baru@example.test')
            ->set('editRole', 'MANAGER')
            ->set('editIsActive', false)
            ->set('newPassword', 'reset-password-123')
            ->set('newPasswordConfirmation', 'reset-password-123')
            ->call('saveUser')
            ->assertHasNoErrors()
            ->assertSet('editOpen', false);

        $cashier->refresh();
        $this->assertSame('Kasir Baru', $cashier->name);
        $this->assertSame(UserRole::MANAGER, $cashier->role);
        $this->assertFalse($cashier->is_active);
        $this->assertTrue(Hash::check('reset-password-123', $cashier->password));
    }

    public function test_owner_cannot_disable_or_demote_own_account(): void
    {
        $owner = User::factory()->create(['role' => UserRole::OWNER]);
        $this->actingAs($owner);

        Livewire::test(UserManager::class)
            ->call('editUser', $owner->id)
            ->set('editRole', 'CASHIER')
            ->set('editIsActive', false)
            ->call('saveUser')
            ->assertHasErrors(['editRole', 'editIsActive']);
    }

    public function test_disabled_authenticated_user_is_logged_out_on_next_request(): void
    {
        $cashier = User::factory()->create(['role' => UserRole::CASHIER, 'is_active' => true]);
        $this->actingAs($cashier);
        $cashier->update(['is_active' => false]);

        $this->get('/dashboard')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_livewire_mutations_enforce_roles_internally(): void
    {
        $cashier = User::factory()->create(['role' => UserRole::CASHIER]);
        $manager = User::factory()->create(['role' => UserRole::MANAGER]);

        $this->actingAs($cashier);
        Livewire::test(ProductManager::class)->call('create')->assertForbidden();
        Livewire::test(InventoryManager::class)->call('adjust')->assertForbidden();
        Livewire::test(CategoryManager::class)->call('create')->assertForbidden();

        $this->actingAs($manager);
        Livewire::test(UserManager::class)->call('editUser', $cashier->id)->assertForbidden();
    }
}
