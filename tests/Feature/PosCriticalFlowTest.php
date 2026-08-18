<?php

namespace Tests\Feature;

use App\Actions\Orders\CompleteOrder;
use App\Enums\PaymentMethod;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\InventoryItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Recipe;
use App\Models\Shift;
use App\Models\StockMovement;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PosCriticalFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_must_have_an_open_shift_to_complete_payment(): void
    {
        [$cashier, $product] = $this->seedSaleContext();

        $this->expectException(ValidationException::class);

        app(CompleteOrder::class)->handle($cashier, [
            'submission_token' => 'without-shift',
            'order_type' => 'TAKE_AWAY',
            'table_number' => 'TEST-01', 'customer_name' => 'Pelanggan Test',
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
            'payment' => ['method' => PaymentMethod::CASH->value, 'received_amount' => 50000],
        ]);
    }

    public function test_completed_order_persists_payment_and_deducts_recipe_stock_once(): void
    {
        [$cashier, $product, $ingredient] = $this->seedSaleContext(withShift: true);
        $payload = [
            'submission_token' => 'checkout-001',
            'order_type' => 'TAKE_AWAY',
            'table_number' => 'TEST-01', 'customer_name' => 'Pelanggan Test',
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
            'payment' => ['method' => PaymentMethod::CASH->value, 'received_amount' => 100000],
        ];

        $order = app(CompleteOrder::class)->handle($cashier, $payload);
        $sameOrder = app(CompleteOrder::class)->handle($cashier, $payload);

        $this->assertTrue($order->is($sameOrder));
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseCount('stock_movements', 1);
        $this->assertSame(1, Payment::count());
        $this->assertSame(1, StockMovement::count());
        $this->assertSame(964.0, (float) $ingredient->fresh()->current_stock);
        $this->assertSame(60000, $order->grand_total);
        $this->assertSame(40000, $order->payment->change_amount);
    }

    private function seedSaleContext(bool $withShift = false): array
    {
        $cashier = User::factory()->create(['role' => UserRole::CASHIER]);
        if ($withShift) {
            Shift::create([
                'cashier_id' => $cashier->id,
                'open_cashier_id' => $cashier->id,
                'opened_at' => now(),
                'opening_cash' => 200000,
                'status' => 'OPEN',
            ]);
        }

        $category = Category::create(['name' => 'Coffee', 'slug' => 'coffee', 'is_active' => true]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Cafe Latte',
            'slug' => 'cafe-latte',
            'sku' => 'LATTE-REG',
            'base_price' => 30000,
            'is_active' => true,
            'is_available' => true,
        ]);
        $unit = Unit::create(['name' => 'Gram', 'symbol' => 'g', 'precision' => 2]);
        $ingredient = InventoryItem::create([
            'unit_id' => $unit->id,
            'name' => 'Coffee Bean',
            'sku' => 'BEAN-001',
            'current_stock' => 1000,
            'minimum_stock' => 100,
            'allow_negative_stock' => false,
            'is_active' => true,
        ]);
        Recipe::create([
            'product_id' => $product->id,
            'inventory_item_id' => $ingredient->id,
            'quantity' => 18,
        ]);

        return [$cashier, $product, $ingredient];
    }
}
