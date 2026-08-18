<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Livewire\Pos\CashierScreen;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Shift;
use App\Models\StoreSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OrderIdentityTest extends TestCase
{
    use RefreshDatabase;

    public function test_table_number_and_customer_name_are_required_before_payment(): void
    {
        [$cashier, $product] = $this->base();
        $this->actingAs($cashier);

        Livewire::test(CashierScreen::class)
            ->call('quickAdd', $product->id)
            ->call('openPayment')
            ->assertHasErrors(['tableNumber', 'customerName'])
            ->set('tableNumber', 'A-07')
            ->set('customerName', 'Dicky')
            ->call('openPayment')
            ->assertHasNoErrors(['tableNumber', 'customerName'])
            ->assertSet('paymentOpen', true);
    }

    public function test_order_identity_is_persisted_and_rendered_on_receipt(): void
    {
        [$cashier, $product] = $this->base();
        $this->actingAs($cashier);

        Livewire::test(CashierScreen::class)
            ->call('quickAdd', $product->id)
            ->set('tableNumber', 'B-12')
            ->set('customerName', 'Atas Nama Demo')
            ->call('openPayment')
            ->set('receivedAmount', 50000)
            ->call('completePayment')
            ->assertHasNoErrors();

        $order = Order::latest('id')->firstOrFail();
        $this->assertSame('B-12', $order->table_number);
        $this->assertSame('Atas Nama Demo', $order->customer_name);

        $this->get(route('orders.receipt', $order))
            ->assertOk()
            ->assertSee('Meja')
            ->assertSee('B-12')
            ->assertSee('Atas nama')
            ->assertSee('Atas Nama Demo');
    }

    private function base(): array
    {
        $cashier = User::factory()->create(['role' => UserRole::CASHIER]);
        Shift::create(['cashier_id' => $cashier->id, 'open_cashier_id' => $cashier->id, 'opened_at' => now(), 'opening_cash' => 0, 'status' => 'OPEN']);
        StoreSetting::create(['store_name' => 'Test', 'tax_enabled' => false, 'tax_rate' => 0, 'transaction_prefix' => 'ID']);
        $category = Category::create(['name' => 'Coffee', 'slug' => 'coffee-identity']);
        $product = Product::create(['category_id' => $category->id, 'name' => 'Latte', 'slug' => 'latte-identity', 'sku' => 'LATTE-ID', 'base_price' => 30000, 'is_active' => true, 'is_available' => true]);

        return [$cashier, $product];
    }
}
