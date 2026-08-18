<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Livewire\CategoryManager;
use App\Livewire\InventoryManager;
use App\Livewire\ProductManager;
use App\Livewire\SettingsManager;
use App\Livewire\UserManager;
use App\Models\Category;
use App\Models\InventoryItem;
use App\Models\StoreSetting;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminManagementUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_charge_and_negative_stock_controls_are_removed(): void
    {
        $owner = User::factory()->create(['role' => UserRole::OWNER]);
        StoreSetting::create(['store_name' => 'Test', 'tax_enabled' => true, 'tax_rate' => 10, 'service_charge_rate' => 5, 'allow_negative_stock' => true]);
        $this->actingAs($owner);

        Livewire::test(SettingsManager::class)
            ->assertDontSee('Service charge')
            ->assertDontSee('Izinkan stok negatif');
    }

    public function test_category_management_supports_create_edit_filter_search_and_pagination(): void
    {
        $owner = User::factory()->create(['role' => UserRole::OWNER]);
        $this->actingAs($owner);

        Livewire::test(CategoryManager::class)
            ->assertSee('Cari kategori')
            ->assertSee('Semua status')
            ->set('name', 'Signature Coffee')
            ->set('sortOrder', 4)
            ->call('save')
            ->assertHasNoErrors()
            ->assertSee('Signature Coffee')
            ->call('edit', Category::where('name', 'Signature Coffee')->value('id'))
            ->set('name', 'Signature Drinks')
            ->call('save')
            ->assertSee('Signature Drinks');
    }

    public function test_product_inventory_and_user_tables_have_search_filter_and_pagination(): void
    {
        $owner = User::factory()->create(['role' => UserRole::OWNER]);
        $category = Category::create(['name' => 'Coffee', 'slug' => 'coffee-table', 'is_active' => true]);
        $unit = Unit::create(['name' => 'Gram', 'symbol' => 'gram-table', 'precision' => 2]);
        InventoryItem::create(['unit_id' => $unit->id, 'name' => 'Beans', 'sku' => 'BEAN-TABLE', 'current_stock' => 1, 'minimum_stock' => 5, 'is_active' => true]);
        $this->actingAs($owner);

        Livewire::test(ProductManager::class)
            ->assertSee('categoryFilter', false)
            ->assertSee('statusFilter', false)
            ->assertSee('pagination', false);

        Livewire::test(InventoryManager::class)
            ->assertSee('stockFilter', false)
            ->assertSee('Cari bahan')
            ->assertSee('pagination', false);

        Livewire::test(UserManager::class)
            ->assertSee('Cari pengguna')
            ->assertSee('roleFilter', false)
            ->assertSee('statusFilter', false)
            ->assertSee('pagination', false);
    }
}
