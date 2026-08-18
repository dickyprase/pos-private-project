<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Livewire\Dashboard;
use App\Models\StoreSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_sees_supplied_dashboard_structure_and_owner_navigation(): void
    {
        $owner = User::factory()->create(['name' => 'Mila Pratiwi', 'role' => UserRole::OWNER]);
        StoreSetting::create(['store_name' => 'KopiKita']);

        $this->actingAs($owner);

        $this->get('/dashboard')
            ->assertOk()
            ->assertSee('Coffee Shop Management')
            ->assertSee('Tren Penjualan')
            ->assertSee('Target Bulanan')
            ->assertSee('Menu Terlaris')
            ->assertSee('Stok Perlu Perhatian')
            ->assertSee('Metode Pembayaran')
            ->assertSee('Ringkasan Shift Hari Ini')
            ->assertSee('Pengguna & Akses')
            ->assertSee('Estimasi Laba Kotor');
    }

    public function test_manager_sees_operational_kpi_without_owner_navigation(): void
    {
        $manager = User::factory()->create(['name' => 'Andi Saputra', 'role' => UserRole::MANAGER]);
        StoreSetting::create(['store_name' => 'KopiKita']);

        $this->actingAs($manager);

        $this->get('/dashboard')
            ->assertOk()
            ->assertSee('MANAGER')
            ->assertSee('Item Stok Menipis')
            ->assertDontSee('Estimasi Laba Kotor')
            ->assertDontSee('Pengguna & Akses');
    }

    public function test_dashboard_period_can_change_to_last_seven_days(): void
    {
        $owner = User::factory()->create(['role' => UserRole::OWNER]);
        StoreSetting::create(['store_name' => 'KopiKita']);

        $this->actingAs($owner);

        Livewire::test(Dashboard::class)
            ->call('setPeriod', 'week')
            ->assertSet('period', 'week')
            ->assertSee('7 hari');
    }

    public function test_dashboard_shift_table_has_search_filter_and_pagination(): void
    {
        $owner = User::factory()->create(['role' => UserRole::OWNER]);
        StoreSetting::create(['store_name' => 'KopiKita']);
        $this->actingAs($owner);

        Livewire::test(Dashboard::class)
            ->assertSee('shiftSearch', false)
            ->assertSee('shiftStatus', false)
            ->assertSee('shift-pagination', false);
    }

    public function test_admin_shell_uses_brand_and_light_surfaces_without_solid_black_ui_fills(): void
    {
        $owner = User::factory()->create(['role' => UserRole::OWNER]);
        StoreSetting::create(['store_name' => 'KopiKita']);

        $this->actingAs($owner)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('bg-brand-600', false)
            ->assertDontSee('bg-stone-950 text-white', false)
            ->assertDontSee('bg-stone-900 text-white', false);
    }
}
