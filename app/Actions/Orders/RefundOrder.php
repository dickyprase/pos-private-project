<?php

namespace App\Actions\Orders;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\StockMovementType;
use App\Models\AuditLog;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RefundOrder
{
    public function handle(Order $order, User $actor, User $manager, string $pin, string $reason, bool $restock): Order
    {
        if (! $manager->is_active || ! $manager->hasRole('OWNER', 'MANAGER') || ! Hash::check($pin, (string) $manager->pin)) {
            throw ValidationException::withMessages(['pin' => 'PIN manager tidak valid.']);
        }

        if (mb_strlen(trim($reason)) < 5) {
            throw ValidationException::withMessages(['reason' => 'Alasan refund minimal 5 karakter.']);
        }

        return DB::transaction(function () use ($order, $actor, $manager, $reason, $restock): Order {
            $order = Order::query()
                ->with(['payment', 'items.product.recipes'])
                ->lockForUpdate()
                ->findOrFail($order->id);

            if ($order->status !== OrderStatus::COMPLETED || ! $order->payment || $order->payment->status !== PaymentStatus::PAID) {
                throw ValidationException::withMessages(['order' => 'Order tidak dapat direfund.']);
            }

            if ($restock) {
                foreach ($order->items as $item) {
                    foreach ($item->product?->recipes ?? [] as $recipe) {
                        $inventory = InventoryItem::query()->lockForUpdate()->find($recipe->inventory_item_id);
                        if (! $inventory) {
                            continue;
                        }

                        $qty = (float) $recipe->quantity * $item->quantity;
                        $inventory->update(['current_stock' => (float) $inventory->current_stock + $qty]);
                        StockMovement::create([
                            'inventory_item_id' => $inventory->id,
                            'type' => StockMovementType::RETURN,
                            'source_key' => 'refund:'.$order->id.':'.$inventory->id.':'.Str::uuid(),
                            'quantity' => $qty,
                            'unit_cost' => $inventory->average_cost,
                            'notes' => 'Refund order '.$order->order_number,
                            'created_by' => $actor->id,
                            'created_at' => now(),
                        ]);
                    }
                }
            }

            $order->payment->update(['status' => PaymentStatus::REFUNDED]);
            $order->update(['status' => OrderStatus::REFUNDED]);
            $order->payment->refunds()->create([
                'order_id' => $order->id,
                'idempotency_key' => (string) Str::uuid(),
                'amount' => $order->payment->amount,
                'reason' => trim($reason),
                'approved_by' => $manager->id,
                'created_by' => $actor->id,
                'restock' => $restock,
            ]);

            AuditLog::create([
                'user_id' => $actor->id,
                'action' => 'ORDER_REFUNDED',
                'entity_type' => Order::class,
                'entity_id' => $order->id,
                'before_data' => ['status' => 'COMPLETED'],
                'after_data' => [
                    'status' => 'REFUNDED',
                    'approved_by' => $manager->id,
                    'reason' => trim($reason),
                    'restock' => $restock,
                ],
                'created_at' => now(),
            ]);

            return $order->fresh(['payment', 'cashier']);
        }, 3);
    }
}
