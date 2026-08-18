<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Livewire\Pos\CashierScreen;
use App\Models\Category;
use App\Models\Product;
use App\Models\Shift;
use App\Models\StoreSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class CashierScreenTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_adds_product_and_sees_server_calculated_total(): void
    {
        $cashier = User::factory()->create(['role' => UserRole::CASHIER]);
        Shift::create(['cashier_id' => $cashier->id, 'open_cashier_id' => $cashier->id, 'opened_at' => now(), 'opening_cash' => 0, 'status' => 'OPEN']);
        StoreSetting::create(['store_name' => 'Test', 'tax_rate' => 10, 'service_charge_rate' => 5]);
        $category = Category::create(['name' => 'Coffee', 'slug' => 'coffee']);
        $product = Product::create([
            'category_id' => $category->id, 'name' => 'Latte', 'slug' => 'latte',
            'sku' => 'LATTE', 'base_price' => 30000, 'is_active' => true, 'is_available' => true,
        ]);

        $this->actingAs($cashier);

        Livewire::test(CashierScreen::class)
            ->call('quickAdd', $product->id)
            ->assertSet('cart.0.quantity', 1)
            ->assertSet('subtotal', 30000)
            ->assertSet('grandTotal', 33000);
    }

    public function test_cashier_can_filter_favorite_products(): void
    {
        $cashier = User::factory()->create(['role' => UserRole::CASHIER]);
        Shift::create(['cashier_id' => $cashier->id, 'open_cashier_id' => $cashier->id, 'opened_at' => now(), 'opening_cash' => 0, 'status' => 'OPEN']);
        StoreSetting::create(['store_name' => 'Test', 'tax_rate' => 10, 'service_charge_rate' => 5]);
        $category = Category::create(['name' => 'Coffee', 'slug' => 'coffee']);
        Product::create([
            'category_id' => $category->id, 'name' => 'Favorite Latte', 'slug' => 'favorite-latte',
            'sku' => 'FAV-LATTE', 'base_price' => 30000, 'is_active' => true, 'is_available' => true,
            'is_favorite' => true,
        ]);
        Product::create([
            'category_id' => $category->id, 'name' => 'Regular Latte', 'slug' => 'regular-latte',
            'sku' => 'REG-LATTE', 'base_price' => 28000, 'is_active' => true, 'is_available' => true,
            'is_favorite' => false,
        ]);

        $this->actingAs($cashier);

        Livewire::test(CashierScreen::class)
            ->call('selectFavorites')
            ->assertSet('favoriteOnly', true)
            ->assertSee('Favorite Latte')
            ->assertDontSee('Regular Latte');
    }

    public function test_cashier_can_clear_current_order(): void
    {
        $cashier = User::factory()->create(['role' => UserRole::CASHIER]);
        Shift::create(['cashier_id' => $cashier->id, 'open_cashier_id' => $cashier->id, 'opened_at' => now(), 'opening_cash' => 0, 'status' => 'OPEN']);
        StoreSetting::create(['store_name' => 'Test', 'tax_rate' => 10, 'service_charge_rate' => 5]);
        $category = Category::create(['name' => 'Coffee', 'slug' => 'coffee']);
        $product = Product::create([
            'category_id' => $category->id, 'name' => 'Latte', 'slug' => 'latte-clear',
            'sku' => 'LATTE-CLEAR', 'base_price' => 30000, 'is_active' => true, 'is_available' => true,
        ]);

        $this->actingAs($cashier);

        Livewire::test(CashierScreen::class)
            ->call('quickAdd', $product->id)
            ->call('clearCart')
            ->assertSet('cart', [])
            ->assertSet('grandTotal', 0);
    }

    public function test_cashier_screen_renders_supplied_responsive_pos_structure(): void
    {
        $cashier = User::factory()->create(['role' => UserRole::CASHIER]);
        Shift::create(['cashier_id' => $cashier->id, 'open_cashier_id' => $cashier->id, 'opened_at' => now(), 'opening_cash' => 0, 'status' => 'OPEN']);
        StoreSetting::create(['store_name' => 'KopiKita POS', 'tax_rate' => 10, 'service_charge_rate' => 5]);

        $this->actingAs($cashier);

        Livewire::test(CashierScreen::class)
            ->assertSee('Pilih Menu')
            ->assertSee('Pesanan Saat Ini')
            ->assertSee('Dine In')
            ->assertSee('Take Away')
            ->assertSee('Bayar Sekarang');
    }

    public function test_cashier_checkout_persists_transaction_and_opens_success_state(): void
    {
        $cashier = User::factory()->create(['role' => UserRole::CASHIER]);
        Shift::create(['cashier_id' => $cashier->id, 'open_cashier_id' => $cashier->id, 'opened_at' => now(), 'opening_cash' => 0, 'status' => 'OPEN']);
        StoreSetting::create(['store_name' => 'Test', 'tax_rate' => 10, 'service_charge_rate' => 0, 'transaction_prefix' => 'TEST']);
        $category = Category::create(['name' => 'Coffee', 'slug' => 'coffee']);
        $product = Product::create([
            'category_id' => $category->id, 'name' => 'Latte', 'slug' => 'latte-checkout',
            'sku' => 'LATTE-CHECKOUT', 'base_price' => 30000, 'is_active' => true, 'is_available' => true,
        ]);

        $this->actingAs($cashier);

        Livewire::test(CashierScreen::class)
            ->call('quickAdd', $product->id)
            ->set('tableNumber', 'TEST-01')->set('customerName', 'Pelanggan Test')->call('openPayment')
            ->set('receivedAmount', 50000)
            ->call('completePayment')
            ->assertSet('successOpen', true)
            ->assertSet('cart', [])
            ->assertSet('lastOrderTotal', 33000)
            ->assertSee('Pembayaran Berhasil');

        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('payments', 1);
    }

    public function test_payment_modal_uses_formatted_currency_input_and_smooth_transition_hooks(): void
    {
        $cashier = User::factory()->create(['role' => UserRole::CASHIER]);
        Shift::create(['cashier_id' => $cashier->id, 'open_cashier_id' => $cashier->id, 'opened_at' => now(), 'opening_cash' => 0, 'status' => 'OPEN']);
        StoreSetting::create(['store_name' => 'Test', 'tax_rate' => 10, 'service_charge_rate' => 0]);
        $category = Category::create(['name' => 'Coffee', 'slug' => 'coffee']);
        $product = Product::create([
            'category_id' => $category->id, 'name' => 'Latte', 'slug' => 'latte-format',
            'sku' => 'LATTE-FORMAT', 'base_price' => 150000, 'is_active' => true, 'is_available' => true,
        ]);

        $this->actingAs($cashier);

        Livewire::test(CashierScreen::class)
            ->call('quickAdd', $product->id)
            ->set('tableNumber', 'TEST-01')->set('customerName', 'Pelanggan Test')->call('openPayment')
            ->assertSet('receivedAmount', 165000)
            ->assertSee('currencyInput', false)
            ->assertSee('data-payment-dialog', false)
            ->assertSee('data-payment-backdrop', false);
    }

    public function test_product_cart_and_quantity_controls_render_interaction_feedback_hooks(): void
    {
        $cashier = User::factory()->create(['role' => UserRole::CASHIER]);
        Shift::create(['cashier_id' => $cashier->id, 'open_cashier_id' => $cashier->id, 'opened_at' => now(), 'opening_cash' => 0, 'status' => 'OPEN']);
        StoreSetting::create(['store_name' => 'Test', 'tax_rate' => 0, 'service_charge_rate' => 0]);
        $category = Category::create(['name' => 'Coffee', 'slug' => 'coffee']);
        $product = Product::create([
            'category_id' => $category->id, 'name' => 'Latte', 'slug' => 'latte-motion',
            'sku' => 'LATTE-MOTION', 'base_price' => 30000, 'is_active' => true, 'is_available' => true,
        ]);

        $this->actingAs($cashier);

        Livewire::test(CashierScreen::class)
            ->call('quickAdd', $product->id)
            ->assertSee('data-product-card', false)
            ->assertSee('data-cart-item', false)
            ->assertSee('data-qty-control', false)
            ->assertSee('optimisticQuantity', false)
            ->assertSee('scheduleSync', false)
            ->assertSee('active:scale-95', false);
    }

    public function test_catalog_render_does_not_eager_load_all_modifiers_or_unused_recent_orders(): void
    {
        $cashier = User::factory()->create(['role' => UserRole::CASHIER]);
        Shift::create(['cashier_id' => $cashier->id, 'open_cashier_id' => $cashier->id, 'opened_at' => now(), 'opening_cash' => 0, 'status' => 'OPEN']);
        StoreSetting::create(['store_name' => 'Test', 'tax_rate' => 0, 'service_charge_rate' => 0]);
        $category = Category::create(['name' => 'Coffee', 'slug' => 'coffee']);
        Product::create([
            'category_id' => $category->id, 'name' => 'Latte', 'slug' => 'latte-query-budget',
            'sku' => 'LATTE-QUERY-BUDGET', 'base_price' => 30000, 'is_active' => true, 'is_available' => true,
        ]);

        $this->actingAs($cashier);
        DB::flushQueryLog();
        DB::enableQueryLog();

        Livewire::test(CashierScreen::class);

        $queries = collect(DB::getQueryLog())->pluck('query')->implode("\n");
        $this->assertStringNotContainsString('modifier_group_product', $queries);
        $this->assertStringNotContainsString('from `orders`', $queries);
        $this->assertLessThanOrEqual(1, substr_count($queries, 'from `products`'));
    }

    public function test_cashier_can_edit_item_note_and_note_persists_to_order_item(): void
    {
        $cashier = User::factory()->create(['role' => UserRole::CASHIER]);
        Shift::create(['cashier_id' => $cashier->id, 'open_cashier_id' => $cashier->id, 'opened_at' => now(), 'opening_cash' => 0, 'status' => 'OPEN']);
        StoreSetting::create(['store_name' => 'Test', 'tax_rate' => 0, 'service_charge_rate' => 0, 'transaction_prefix' => 'TEST']);
        $category = Category::create(['name' => 'Coffee', 'slug' => 'coffee-note']);
        $product = Product::create([
            'category_id' => $category->id, 'name' => 'Caramel Macchiato', 'slug' => 'caramel-note',
            'sku' => 'CARAMEL-NOTE', 'base_price' => 30000, 'is_active' => true, 'is_available' => true,
        ]);

        $this->actingAs($cashier);

        Livewire::test(CashierScreen::class)
            ->call('quickAdd', $product->id)
            ->call('openItemNote', 0)
            ->assertSet('itemNoteOpen', true)
            ->assertSet('editingItemName', 'Caramel Macchiato')
            ->set('editingItemNote', 'less sugar, no ice')
            ->call('saveItemNote')
            ->assertSet('cart.0.notes', 'less sugar, no ice')
            ->assertSee('less sugar, no ice')
            ->assertSee('data-item-note-button', false)
            ->set('tableNumber', 'TEST-01')->set('customerName', 'Pelanggan Test')->call('openPayment')
            ->set('receivedAmount', 30000)
            ->call('completePayment');

        $this->assertDatabaseHas('order_items', [
            'product_id' => $product->id,
            'notes' => 'less sugar, no ice',
        ]);
    }

    public function test_cart_uses_reusable_custom_confirmation_instead_of_native_browser_confirm(): void
    {
        $cashier = User::factory()->create(['role' => UserRole::CASHIER]);
        Shift::create(['cashier_id' => $cashier->id, 'open_cashier_id' => $cashier->id, 'opened_at' => now(), 'opening_cash' => 0, 'status' => 'OPEN']);
        StoreSetting::create(['store_name' => 'Test', 'tax_rate' => 0, 'service_charge_rate' => 0]);
        $category = Category::create(['name' => 'Coffee', 'slug' => 'coffee-delete-confirm']);
        $product = Product::create([
            'category_id' => $category->id, 'name' => 'Latte', 'slug' => 'latte-delete-confirm',
            'sku' => 'LATTE-DELETE-CONFIRM', 'base_price' => 30000, 'is_active' => true, 'is_available' => true,
        ]);

        $this->actingAs($cashier);

        Livewire::test(CashierScreen::class)
            ->call('quickAdd', $product->id)
            ->assertSee('data-ui-alert-modal', false)
            ->assertSee('ui-alert:open', false)
            ->assertSee('data-delete-confirm', false)
            ->assertSee('actionArgs: [0]', false)
            ->assertSee('gap-2 rounded-xl border', false)
            ->assertDontSee('wire:click="remove(0)"', false)
            ->assertDontSee('wire:confirm', false);
    }

    public function test_cashier_without_shift_sees_shift_gate(): void
    {
        $cashier = User::factory()->create(['role' => UserRole::CASHIER]);
        $this->actingAs($cashier);

        Livewire::test(CashierScreen::class)
            ->assertSee('Buka shift dulu');
    }

    public function test_pos_uses_percentage_discount_and_realtime_indonesian_clock(): void
    {
        $cashier = User::factory()->create(['role' => UserRole::CASHIER]);
        Shift::create(['cashier_id' => $cashier->id, 'open_cashier_id' => $cashier->id, 'opened_at' => now(), 'opening_cash' => 0, 'status' => 'OPEN']);
        StoreSetting::create(['store_name' => 'Test', 'tax_rate' => 0, 'service_charge_rate' => 0]);
        $category = Category::create(['name' => 'Coffee', 'slug' => 'coffee-percentage']);
        $product = Product::create([
            'category_id' => $category->id, 'name' => 'Latte', 'slug' => 'latte-percentage',
            'sku' => 'LATTE-PERCENTAGE', 'base_price' => 30000, 'is_active' => true, 'is_available' => true,
        ]);

        $this->actingAs($cashier);

        Livewire::test(CashierScreen::class)
            ->call('quickAdd', $product->id)
            ->set('discount', 150)
            ->assertSet('discount', 100)
            ->assertSee('Diskon (%)')
            ->assertSee('max="100"', false)
            ->assertSee('data-pos-date', false)
            ->assertSee('data-pos-datetime', false)
            ->assertSee('from-brand-500', false);
    }
}
