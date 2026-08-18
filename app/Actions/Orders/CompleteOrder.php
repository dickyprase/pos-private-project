<?php

namespace App\Actions\Orders;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ShiftStatus;
use App\Enums\StockMovementType;
use App\Models\InventoryItem;
use App\Models\ModifierOption;
use App\Models\Order;
use App\Models\Product;
use App\Models\Shift;
use App\Models\StockMovement;
use App\Models\StoreSetting;
use App\Models\User;
use App\Services\OrderNumberService;
use App\Services\PricingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CompleteOrder
{
    public function __construct(
        private PricingService $pricing,
        private OrderNumberService $numbers,
    ) {}

    public function handle(User $cashier, array $payload): Order
    {
        $tableNumber = trim((string) ($payload['table_number'] ?? ''));
        $customerName = trim((string) ($payload['customer_name'] ?? ''));
        if ($tableNumber === '') {
            throw ValidationException::withMessages(['table_number' => 'Nomor meja wajib diisi.']);
        }
        if ($customerName === '') {
            throw ValidationException::withMessages(['customer_name' => 'Atas nama wajib diisi.']);
        }
        if (mb_strlen($tableNumber) > 30 || mb_strlen($customerName) > 120) {
            throw ValidationException::withMessages(['customer_name' => 'Data meja atau atas nama terlalu panjang.']);
        }
        $payloadHash = hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION));
        $existing = Order::query()->where('submission_token', $payload['submission_token'] ?? '')->first();
        if ($existing) {
            if ($existing->checkout_payload_hash !== $payloadHash) {
                throw ValidationException::withMessages(['submission_token' => 'Token checkout sudah dipakai untuk payload berbeda.']);
            }

            return $existing->load('items.modifiers', 'payment');
        }

        return DB::transaction(function () use ($cashier, $payload, $payloadHash, $tableNumber, $customerName): Order {
            $shift = Shift::query()
                ->where('cashier_id', $cashier->id)
                ->where('status', ShiftStatus::OPEN)
                ->lockForUpdate()
                ->first();

            if (! $shift) {
                throw ValidationException::withMessages(['shift' => 'Buka shift sebelum membuat transaksi.']);
            }

            $settings = StoreSetting::current();
            $prepared = [];
            foreach ($payload['items'] ?? [] as $item) {
                $product = Product::query()
                    ->with(['recipes.inventoryItem', 'variants', 'modifierGroups.options.recipes'])
                    ->whereKey($item['product_id'] ?? null)
                    ->where('is_active', true)
                    ->where('is_available', true)
                    ->first();

                if (! $product) {
                    throw ValidationException::withMessages(['items' => 'Produk tidak aktif atau sedang habis.']);
                }

                $quantity = max(1, (int) ($item['quantity'] ?? 1));
                $variant = null;
                $variantPrice = 0;
                if (! empty($item['variant_id'])) {
                    $variant = $product->variants->firstWhere('id', (int) $item['variant_id']);
                    if (! $variant || ! $variant->is_active) {
                        throw ValidationException::withMessages(['variant' => 'Varian tidak tersedia.']);
                    }
                    $variantPrice = $variant->price_adjustment;
                }

                $selectedModifierIds = collect($item['modifier_ids'] ?? [])->map(fn ($id) => (int) $id)->unique()->values();
                $modifiers = ModifierOption::query()
                    ->whereIn('id', $selectedModifierIds)
                    ->where('is_active', true)
                    ->get();
                if ($modifiers->count() !== $selectedModifierIds->count()) {
                    throw ValidationException::withMessages(['modifiers' => 'Modifier tidak valid.']);
                }
                $allowedModifierIds = $product->modifierGroups->flatMap(fn ($group) => $group->options->pluck('id'));
                if ($selectedModifierIds->diff($allowedModifierIds)->isNotEmpty()) {
                    throw ValidationException::withMessages(['modifiers' => 'Modifier tidak terhubung ke produk.']);
                }
                foreach ($product->modifierGroups as $group) {
                    $allowedIds = $group->options->where('is_active', true)->pluck('id');
                    $selectedCount = $selectedModifierIds->intersect($allowedIds)->count();
                    if ($selectedCount < $group->min_selection || $selectedCount > $group->max_selection) {
                        throw ValidationException::withMessages(['modifiers' => "Pilihan {$group->name} harus {$group->min_selection}-{$group->max_selection} item."]);
                    }
                }
                $modifierTotal = (int) $modifiers->sum('price_adjustment');
                $unitPrice = $product->base_price + $variantPrice;
                $prepared[] = compact('product', 'quantity', 'variant', 'modifiers', 'modifierTotal', 'unitPrice') + [
                    'notes' => $item['notes'] ?? null,
                ];
            }

            if ($prepared === []) {
                throw ValidationException::withMessages(['items' => 'Cart masih kosong.']);
            }

            $totals = $this->pricing->calculate(
                items: array_map(fn ($row) => [
                    'unit_price' => $row['unitPrice'],
                    'quantity' => $row['quantity'],
                    'modifier_total' => $row['modifierTotal'],
                ], $prepared),
                discountPercent: min(100, max(0, (int) ($payload['discount'] ?? 0))),
                taxRate: $settings->tax_enabled ? (float) $settings->tax_rate : 0,
                serviceRate: 0,
            );

            $method = PaymentMethod::from($payload['payment']['method'] ?? PaymentMethod::CASH->value);
            $received = (int) ($payload['payment']['received_amount'] ?? $totals['grand_total']);
            if ($method === PaymentMethod::CASH && $received < $totals['grand_total']) {
                throw ValidationException::withMessages(['payment.received_amount' => 'Uang diterima kurang dari total.']);
            }
            if ($method !== PaymentMethod::CASH) {
                $received = $totals['grand_total'];
            }

            $order = Order::create([
                'order_number' => $this->numbers->next(),
                'submission_token' => $payload['submission_token'],
                'checkout_payload_hash' => $payloadHash,
                'shift_id' => $shift->id,
                'cashier_id' => $cashier->id,
                'customer_id' => $payload['customer_id'] ?? null,
                'customer_name' => $customerName,
                'order_type' => OrderType::from($payload['order_type'] ?? OrderType::TAKE_AWAY->value),
                'table_number' => $tableNumber,
                'status' => OrderStatus::COMPLETED,
                ...$totals,
                'notes' => $payload['notes'] ?? null,
                'paid_at' => now(),
            ]);

            foreach ($prepared as $row) {
                $orderItem = $order->items()->create([
                    'product_id' => $row['product']->id,
                    'product_variant_id' => $row['variant']?->id,
                    'product_name_snapshot' => $row['product']->name,
                    'variant_name_snapshot' => $row['variant']?->name,
                    'sku_snapshot' => $row['variant']?->sku ?? $row['product']->sku,
                    'unit_price' => $row['unitPrice'],
                    'quantity' => $row['quantity'],
                    'discount_total' => 0,
                    'line_total' => ($row['unitPrice'] + $row['modifierTotal']) * $row['quantity'],
                    'notes' => $row['notes'],
                ]);
                foreach ($row['modifiers'] as $modifier) {
                    $orderItem->modifiers()->create([
                        'modifier_option_id' => $modifier->id,
                        'name_snapshot' => $modifier->name,
                        'price_adjustment' => $modifier->price_adjustment,
                        'quantity' => 1,
                    ]);
                }
                $this->deductProductRecipe($row['product'], $row['variant']?->id, $row['quantity'], $order, $cashier);
                foreach ($row['modifiers'] as $modifier) {
                    foreach ($modifier->recipes as $recipe) {
                        $this->deductStock($recipe->inventory_item_id, (float) $recipe->quantity * $row['quantity'], $order, $cashier);
                    }
                }
            }

            $order->payment()->create([
                'idempotency_key' => $payload['submission_token'],
                'method' => $method,
                'status' => PaymentStatus::PAID,
                'amount' => $totals['grand_total'],
                'received_amount' => $received,
                'change_amount' => max(0, $received - $totals['grand_total']),
                'reference_number' => $payload['payment']['reference_number'] ?? null,
                'paid_at' => now(),
                'created_by' => $cashier->id,
            ]);

            return $order->load('items.modifiers', 'payment');
        }, 3);
    }

    private function deductProductRecipe(Product $product, ?int $variantId, int $saleQuantity, Order $order, User $cashier): void
    {
        $recipes = $product->recipes->filter(fn ($recipe) => $recipe->product_variant_id === null || $recipe->product_variant_id === $variantId);
        foreach ($recipes as $recipe) {
            $this->deductStock($recipe->inventory_item_id, (float) $recipe->quantity * $saleQuantity, $order, $cashier);
        }
    }

    private function deductStock(int $inventoryItemId, float $quantity, Order $order, User $cashier): void
    {
        $item = InventoryItem::query()->lockForUpdate()->findOrFail($inventoryItemId);
        $next = (float) $item->current_stock - $quantity;
        if ($next < 0) {
            throw ValidationException::withMessages(['stock' => "Stok {$item->name} tidak cukup."]);
        }
        $item->update(['current_stock' => $next]);
        StockMovement::create([
            'inventory_item_id' => $item->id,
            'type' => StockMovementType::SALE_USAGE,
            'source_key' => 'sale:'.$order->id.':'.$item->id.':'.Str::uuid(),
            'quantity' => -$quantity,
            'balance_after' => $next,
            'unit_cost' => $item->average_cost,
            'reference_type' => Order::class,
            'reference_id' => $order->id,
            'notes' => "Pemakaian untuk {$order->order_number}",
            'created_by' => $cashier->id,
            'created_at' => now(),
        ]);
    }
}
