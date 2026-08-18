<?php

namespace Tests\Feature;

use App\Actions\Orders\CompleteOrder;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Livewire\OrderHistory;
use App\Models\Category;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\Recipe;
use App\Models\Shift;
use App\Models\StoreSetting;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class OrderActionsUiTest extends TestCase
{
    use RefreshDatabase;

    private function seedBase(): array
    {
        StoreSetting::create([
            'id' => 1,
            'store_name' => 'Kopi Senja',
            'currency' => 'IDR',
            'timezone' => 'Asia/Jakarta',
            'tax_rate' => 10,
            'service_charge_rate' => 5,
            'transaction_prefix' => 'KP',
            'allow_negative_stock' => false,
        ]);

        $owner = User::factory()->create([
            'username' => 'owner',
            'role' => UserRole::OWNER,
            'pin' => Hash::make('123456'),
            'is_active' => true,
        ]);
        $cashier = User::factory()->create([
            'username' => 'cashier',
            'role' => UserRole::CASHIER,
            'is_active' => true,
        ]);
        $shift = Shift::create([
            'cashier_id' => $cashier->id,
            'open_cashier_id' => $cashier->id,
            'opened_at' => now(),
            'opening_cash' => 100000,
            'status' => 'OPEN',
        ]);
        $category = Category::create(['name' => 'Coffee', 'slug' => 'coffee', 'sort_order' => 1, 'is_active' => true]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Espresso',
            'slug' => 'espresso',
            'sku' => 'ESP-1',
            'base_price' => 20000,
            'cost_estimate' => 5000,
            'is_active' => true,
            'is_available' => true,
        ]);
        $unit = Unit::create(['name' => 'Gram', 'symbol' => 'g']);
        $item = InventoryItem::create([
            'unit_id' => $unit->id,
            'name' => 'Biji Kopi',
            'sku' => 'INV-1',
            'current_stock' => 1000,
            'minimum_stock' => 100,
            'average_cost' => 100,
            'allow_negative_stock' => false,
        ]);
        Recipe::create([
            'product_id' => $product->id,
            'inventory_item_id' => $item->id,
            'quantity' => 18,
        ]);

        return compact('owner', 'cashier', 'shift', 'product', 'item');
    }

    private function paidOrder(User $cashier, Product $product): Order
    {
        return app(CompleteOrder::class)->handle($cashier, [
            'submission_token' => (string) str()->uuid(),
            'order_type' => 'TAKE_AWAY',
            'table_number' => 'TEST-01',
            'customer_name' => 'Pelanggan Test',
            'items' => [[
                'product_id' => $product->id,
                'quantity' => 1,
                'modifier_ids' => [],
            ]],
            'discount' => 0,
            'payment' => [
                'method' => PaymentMethod::CASH->value,
                'received_amount' => 50000,
            ],
        ]);
    }

    public function test_owner_can_refund_order_from_history_ui(): void
    {
        ['owner' => $owner, 'cashier' => $cashier, 'product' => $product, 'item' => $item] = $this->seedBase();
        $order = $this->paidOrder($cashier, $product);
        $before = (float) $item->fresh()->current_stock;

        $this->actingAs($owner);
        Livewire::test(OrderHistory::class)
            ->call('openAction', $order->id, 'refund')
            ->set('managerUsername', 'owner')
            ->set('managerPin', '123456')
            ->set('reason', 'Komplain pelanggan')
            ->set('restock', true)
            ->call('submitAction')
            ->assertHasNoErrors()
            ->assertSet('actionOrderId', null);

        $this->assertSame(OrderStatus::REFUNDED, $order->fresh()->status);
        $this->assertSame(PaymentStatus::REFUNDED, $order->fresh()->payment->status);
        $this->assertGreaterThan($before, (float) $item->fresh()->current_stock);
    }

    public function test_owner_can_void_order_from_history_ui(): void
    {
        ['owner' => $owner, 'cashier' => $cashier, 'product' => $product] = $this->seedBase();
        $order = $this->paidOrder($cashier, $product);

        $this->actingAs($owner);
        Livewire::test(OrderHistory::class)
            ->call('openAction', $order->id, 'void')
            ->set('managerUsername', 'owner')
            ->set('managerPin', '123456')
            ->set('reason', 'Salah input kasir')
            ->set('restock', true)
            ->call('submitAction')
            ->assertHasNoErrors();

        $this->assertSame(OrderStatus::CANCELLED, $order->fresh()->status);
        $this->assertSame(PaymentStatus::VOIDED, $order->fresh()->payment->status);
    }

    public function test_cashier_cannot_open_refund_action(): void
    {
        ['cashier' => $cashier, 'product' => $product] = $this->seedBase();
        $order = $this->paidOrder($cashier, $product);

        $this->actingAs($cashier);
        Livewire::test(OrderHistory::class)
            ->call('openAction', $order->id, 'refund')
            ->assertForbidden();
    }

    public function test_inventory_and_report_pages_load_for_owner(): void
    {
        ['owner' => $owner] = $this->seedBase();
        $this->actingAs($owner)
            ->get('/inventory')->assertOk()
            ->assertSee('Inventori');
        $this->actingAs($owner)
            ->get('/reports/sales')->assertOk()
            ->assertSee('Laporan');
    }

    public function test_forbidden_page_is_friendly(): void
    {
        ['cashier' => $cashier] = $this->seedBase();
        $this->actingAs($cashier)
            ->get('/settings')
            ->assertForbidden()
            ->assertSee('Akses ditolak');
    }
}
