<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Livewire\Pos\CashierScreen;
use App\Livewire\SettingsManager;
use App\Models\Category;
use App\Models\Product;
use App\Models\Shift;
use App\Models\StoreSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class QrisSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_enable_qris_and_upload_qris_image(): void
    {
        Storage::fake('public');
        $owner = User::factory()->create(['role' => UserRole::OWNER]);
        StoreSetting::create(['store_name' => 'Test', 'qris_enabled' => false]);
        $this->actingAs($owner);

        Livewire::test(SettingsManager::class)
            ->set('form.qris_enabled', true)
            ->set('qrisImage', UploadedFile::fake()->image('qris.png', 600, 600))
            ->call('save')
            ->assertHasNoErrors()
            ->assertSee('Aktifkan QRIS')
            ->assertSee('Preview QRIS');

        $setting = StoreSetting::firstOrFail();
        $this->assertTrue($setting->qris_enabled);
        $this->assertNotNull($setting->qris_image_path);
        Storage::disk('public')->assertExists($setting->qris_image_path);
    }

    public function test_cashier_only_sees_cash_and_enabled_qris_with_uploaded_image(): void
    {
        $cashier = User::factory()->create(['role' => UserRole::CASHIER]);
        Shift::create(['cashier_id' => $cashier->id, 'open_cashier_id' => $cashier->id, 'opened_at' => now(), 'opening_cash' => 0, 'status' => 'OPEN']);
        StoreSetting::create(['store_name' => 'Test', 'qris_enabled' => true, 'qris_image_path' => 'qris/store.png']);
        $category = Category::create(['name' => 'Coffee', 'slug' => 'coffee-qris']);
        $product = Product::create(['category_id' => $category->id, 'name' => 'Latte', 'slug' => 'latte-qris', 'sku' => 'LATTE-QRIS', 'base_price' => 30000, 'is_active' => true, 'is_available' => true]);
        $this->actingAs($cashier);

        Livewire::test(CashierScreen::class)
            ->call('quickAdd', $product->id)
            ->set('tableNumber', 'TEST-01')->set('customerName', 'Pelanggan Test')->call('openPayment')
            ->assertSee('Cash')
            ->assertSee('QRIS')
            ->assertDontSee('Kartu')
            ->set('paymentMethod', 'QRIS')
            ->assertSee('qris/store.png', false)
            ->assertDontSee('Uang diterima');
    }

    public function test_owner_can_toggle_tax_and_set_percentage(): void
    {
        $owner = User::factory()->create(['role' => UserRole::OWNER]);
        StoreSetting::create(['store_name' => 'Test', 'tax_enabled' => true, 'tax_rate' => 10]);
        $this->actingAs($owner);

        Livewire::test(SettingsManager::class)
            ->assertSee('Aktifkan Pajak')
            ->set('form.tax_enabled', false)
            ->set('form.tax_rate', 12.5)
            ->call('save')
            ->assertHasNoErrors();

        $setting = StoreSetting::firstOrFail();
        $this->assertFalse($setting->tax_enabled);
        $this->assertSame('12.50', $setting->tax_rate);
    }

    public function test_disabled_tax_uses_zero_effective_rate_in_pos(): void
    {
        $cashier = User::factory()->create(['role' => UserRole::CASHIER]);
        Shift::create(['cashier_id' => $cashier->id, 'open_cashier_id' => $cashier->id, 'opened_at' => now(), 'opening_cash' => 0, 'status' => 'OPEN']);
        StoreSetting::create(['store_name' => 'Test', 'tax_enabled' => false, 'tax_rate' => 10, 'service_charge_rate' => 0]);
        $category = Category::create(['name' => 'Coffee', 'slug' => 'coffee-tax-off']);
        $product = Product::create(['category_id' => $category->id, 'name' => 'Latte', 'slug' => 'latte-tax-off', 'sku' => 'LATTE-TAX-OFF', 'base_price' => 30000, 'is_active' => true, 'is_available' => true]);
        $this->actingAs($cashier);

        Livewire::test(CashierScreen::class)
            ->call('quickAdd', $product->id)
            ->assertSet('taxRate', 0.0)
            ->assertSet('taxTotal', 0)
            ->assertSet('grandTotal', 30000);
    }

    public function test_disabled_qris_falls_back_to_cash(): void
    {
        $cashier = User::factory()->create(['role' => UserRole::CASHIER]);
        Shift::create(['cashier_id' => $cashier->id, 'open_cashier_id' => $cashier->id, 'opened_at' => now(), 'opening_cash' => 0, 'status' => 'OPEN']);
        StoreSetting::create(['store_name' => 'Test', 'qris_enabled' => false]);
        $this->actingAs($cashier);

        Livewire::test(CashierScreen::class)
            ->set('paymentMethod', 'QRIS')
            ->assertSet('paymentMethod', 'CASH');
    }
}
