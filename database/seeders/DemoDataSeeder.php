<?php

namespace Database\Seeders;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ShiftStatus;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Shift;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [UserRole::CASHIER, UserRole::CASHIER, UserRole::MANAGER];
        foreach (range(1, 15) as $number) {
            User::updateOrCreate(['username' => sprintf('demo%02d', $number)], [
                'name' => 'Demo User '.$number,
                'email' => sprintf('demo%02d@example.test', $number),
                'password' => Hash::make('demo12345'),
                'role' => $roles[($number - 1) % count($roles)],
                'is_active' => $number % 7 !== 0,
            ]);
        }

        $categories = Category::query()->where('is_active', true)->get();
        foreach (range(1, 12) as $number) {
            $category = $categories[($number - 1) % $categories->count()];
            Product::updateOrCreate(['sku' => sprintf('DEMO-%03d', $number)], [
                'category_id' => $category->id,
                'name' => 'Menu Demo '.$number,
                'slug' => 'menu-demo-'.$number,
                'base_price' => 18000 + ($number * 1500),
                'cost_estimate' => 6000 + ($number * 500),
                'is_active' => true,
                'is_available' => $number % 6 !== 0,
                'is_favorite' => $number % 4 === 0,
                'sort_order' => 10 + $number,
            ]);
        }

        $unit = Unit::query()->where('symbol', 'pcs')->firstOrFail();
        foreach (range(1, 12) as $number) {
            InventoryItem::updateOrCreate(['sku' => sprintf('DEMO-ING-%03d', $number)], [
                'unit_id' => $unit->id,
                'name' => 'Bahan Demo '.$number,
                'current_stock' => $number % 4 === 0 ? 5 : 80 + ($number * 10),
                'minimum_stock' => 20,
                'average_cost' => 1000 + ($number * 100),
                'allow_negative_stock' => false,
                'is_active' => true,
            ]);
        }

        $cashiers = User::query()->where('role', UserRole::CASHIER)->where('is_active', true)->get();
        $products = Product::query()->where('is_active', true)->take(8)->get();
        foreach ($cashiers->take(3) as $cashierIndex => $cashier) {
            $openedAt = now()->subDays($cashierIndex + 1)->setTime(8, 0);
            $shift = Shift::updateOrCreate([
                'cashier_id' => $cashier->id,
                'opened_at' => $openedAt,
            ], [
                'open_cashier_id' => null,
                'closed_at' => $openedAt->copy()->addHours(9),
                'opening_cash' => 300000,
                'expected_cash' => 750000,
                'actual_cash' => 748000,
                'difference' => -2000,
                'status' => ShiftStatus::CLOSED,
                'notes' => 'Shift demo untuk pengecekan fitur',
            ]);

            foreach (range(1, 6) as $orderIndex) {
                $paidAt = $openedAt->copy()->addHours(1 + $orderIndex);
                $product = $products[($cashierIndex + $orderIndex) % $products->count()];
                $quantity = ($orderIndex % 3) + 1;
                $subtotal = $product->base_price * $quantity;
                $discount = $orderIndex % 4 === 0 ? (int) round($subtotal * 0.1) : 0;
                $tax = (int) round(($subtotal - $discount) * 0.1);
                $grandTotal = $subtotal - $discount + $tax;
                $number = sprintf('DEMO-%02d-%02d', $cashierIndex + 1, $orderIndex);
                $order = Order::updateOrCreate(['order_number' => $number], [
                    'submission_token' => (string) Str::uuid(),
                    'checkout_payload_hash' => hash('sha256', $number),
                    'shift_id' => $shift->id,
                    'cashier_id' => $cashier->id,
                    'customer_name' => 'Pelanggan Demo '.$orderIndex,
                    'order_type' => $orderIndex % 2 ? OrderType::DINE_IN : OrderType::TAKE_AWAY,
                    'table_number' => 'D-'.$orderIndex,
                    'status' => OrderStatus::COMPLETED,
                    'subtotal' => $subtotal,
                    'discount_total' => $discount,
                    'tax_total' => $tax,
                    'service_charge_total' => 0,
                    'rounding_total' => 0,
                    'grand_total' => $grandTotal,
                    'notes' => $orderIndex === 3 ? 'Order dummy untuk demo' : null,
                    'paid_at' => $paidAt,
                    'created_at' => $paidAt,
                    'updated_at' => $paidAt,
                ]);
                OrderItem::updateOrCreate(['order_id' => $order->id, 'product_id' => $product->id], [
                    'product_name_snapshot' => $product->name,
                    'sku_snapshot' => $product->sku,
                    'unit_price' => $product->base_price,
                    'quantity' => $quantity,
                    'discount_total' => $discount,
                    'line_total' => $subtotal - $discount,
                    'notes' => $orderIndex % 3 === 0 ? 'less sugar' : null,
                ]);
                $method = $orderIndex % 3 === 0 ? PaymentMethod::QRIS : PaymentMethod::CASH;
                Payment::updateOrCreate(['order_id' => $order->id], [
                    'idempotency_key' => (string) Str::uuid(),
                    'method' => $method,
                    'status' => PaymentStatus::PAID,
                    'amount' => $grandTotal,
                    'received_amount' => $method === PaymentMethod::CASH ? (int) ceil($grandTotal / 10000) * 10000 : $grandTotal,
                    'change_amount' => $method === PaymentMethod::CASH ? ((int) ceil($grandTotal / 10000) * 10000) - $grandTotal : 0,
                    'reference_number' => $method === PaymentMethod::QRIS ? 'QR-DEMO-'.$number : null,
                    'paid_at' => $paidAt,
                    'created_by' => $cashier->id,
                ]);
            }
        }
    }
}
