<?php

namespace Tests\Feature;

use App\Actions\Orders\CompleteOrder;
use App\Actions\Orders\RefundOrder;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\ModifierGroup;
use App\Models\Product;
use App\Models\Shift;
use App\Models\StoreSetting;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PosDomainGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_required_modifier_is_enforced_on_server(): void
    {
        [$cashier, $product] = $this->saleContext();
        $group = ModifierGroup::create(['name' => 'Size', 'selection_type' => 'SINGLE', 'min_selection' => 1, 'max_selection' => 1, 'is_required' => true]);
        $group->options()->create(['name' => 'Regular', 'price_adjustment' => 0, 'is_active' => true]);
        $product->modifierGroups()->attach($group->id);

        $this->expectException(ValidationException::class);
        app(CompleteOrder::class)->handle($cashier, $this->payload($product, 'required-modifier'));
    }

    public function test_same_checkout_token_with_different_payload_is_rejected(): void
    {
        [$cashier, $product] = $this->saleContext();
        $payload = $this->payload($product, 'same-token');
        app(CompleteOrder::class)->handle($cashier, $payload);

        $payload['items'][0]['quantity'] = 2;
        $this->expectException(ValidationException::class);
        app(CompleteOrder::class)->handle($cashier, $payload);
    }

    public function test_database_prevents_two_open_shifts_for_same_cashier(): void
    {
        $cashier = User::factory()->create(['role' => UserRole::CASHIER]);
        Shift::create(['cashier_id' => $cashier->id, 'open_cashier_id' => $cashier->id, 'opened_at' => now(), 'opening_cash' => 0, 'status' => 'OPEN']);

        $this->expectException(QueryException::class);
        Shift::create(['cashier_id' => $cashier->id, 'open_cashier_id' => $cashier->id, 'opened_at' => now(), 'opening_cash' => 0, 'status' => 'OPEN']);
    }

    public function test_paid_order_can_only_be_refunded_once_by_manager_pin(): void
    {
        [$cashier, $product] = $this->saleContext();
        $order = app(CompleteOrder::class)->handle($cashier, $this->payload($product, 'refund-once'));
        $manager = User::factory()->create(['role' => UserRole::MANAGER, 'pin' => Hash::make('654321'), 'is_active' => true]);

        app(RefundOrder::class)->handle($order, $cashier, $manager, '654321', 'Pesanan salah', false);
        $this->assertSame('REFUNDED', $order->fresh()->status->value);
        $this->assertDatabaseCount('refunds', 1);

        $this->expectException(ValidationException::class);
        app(RefundOrder::class)->handle($order->fresh(), $cashier, $manager, '654321', 'Ulang', false);
    }

    private function saleContext(): array
    {
        StoreSetting::create(['store_name' => 'Test', 'tax_rate' => 0, 'service_charge_rate' => 0]);
        $cashier = User::factory()->create(['role' => UserRole::CASHIER]);
        Shift::create(['cashier_id' => $cashier->id, 'open_cashier_id' => $cashier->id, 'opened_at' => now(), 'opening_cash' => 0, 'status' => 'OPEN']);
        $category = Category::create(['name' => 'Coffee', 'slug' => 'coffee']);
        $product = Product::create(['category_id' => $category->id, 'name' => 'Latte', 'slug' => 'latte', 'sku' => 'LATTE', 'base_price' => 30000, 'is_active' => true, 'is_available' => true]);

        return [$cashier, $product];
    }

    private function payload(Product $product, string $token): array
    {
        return ['submission_token' => $token, 'order_type' => 'TAKE_AWAY', 'table_number' => 'TEST-01', 'customer_name' => 'Pelanggan Test', 'items' => [['product_id' => $product->id, 'quantity' => 1]], 'payment' => ['method' => 'CASH', 'received_amount' => 50000]];
    }
}
