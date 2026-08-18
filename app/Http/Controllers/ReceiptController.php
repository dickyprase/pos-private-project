<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\StoreSetting;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;


class ReceiptController extends Controller
{

    public function data(Order $order): JsonResponse
    {
        $this->authorizeOrder($order);
        return response()->json($this->thermalData($order));
    }

    public function show(Order $order): View
    {
        $this->authorizeOrder($order);

        $order->load('items.modifiers', 'payment', 'cashier');
        $store = StoreSetting::current();

        $thermalTransaction = $this->thermalData($order, $store);
/*
        $thermalTransaction = [
            'store' => [
                'name' => $store->store_name,
                'address' => $store->address,
                'phone' => $store->phone,
                'footer' => $store->receipt_footer,
            ],
            'orderNumber' => $order->order_number,
            'paidAt' => $order->paid_at?->format('d/m/Y H:i'),
            'cashier' => $order->cashier->name,
            'orderType' => $order->order_type->value,
            'tableNumber' => $order->table_number,
            'customerName' => $order->customer_name,
            'items' => $order->items->map(fn ($item) => [
                'quantity' => $item->quantity,
                'name' => $item->product_name_snapshot,
                'total' => $item->line_total,
                'variant' => $item->variant_name_snapshot,
                'modifiers' => $item->modifiers->map(fn ($modifier) => [
                    'name' => $modifier->name_snapshot,
                    'price' => $modifier->price_adjustment,
                ])->values(),
                'notes' => $item->notes,
            ])->values(),
            'subtotal' => $order->subtotal,
            'discount' => $order->discount_total,
            'tax' => $order->tax_total,
            'serviceCharge' => $order->service_charge_total,
            'total' => $order->grand_total,
            'paymentMethod' => $order->payment->method->value,
            'paidAmount' => $order->payment->received_amount,
            'change' => $order->payment->change_amount,
        ];*/

        return view('receipts.thermal', [
            'order' => $order,
            'store' => $store,
            'thermalTransaction' => $thermalTransaction,
        ]);
    }

    private function authorizeOrder(Order $order): void
    {
        abort_unless(auth()->user()->hasRole('OWNER', 'MANAGER') || $order->cashier_id === auth()->id(), 403);
    }

    private function thermalData(Order $order, ?StoreSetting $store = null): array
    {
        $order->loadMissing('items.modifiers', 'payment', 'cashier');
        $store ??= StoreSetting::current();
        return [
            'store' => ['name' => $store->store_name, 'address' => $store->address, 'phone' => $store->phone, 'footer' => $store->receipt_footer],
            'orderNumber' => $order->order_number, 'paidAt' => $order->paid_at?->format('d/m/Y H:i'),
            'cashier' => $order->cashier->name, 'orderType' => $order->order_type->value,
            'tableNumber' => $order->table_number, 'customerName' => $order->customer_name,
            'items' => $order->items->map(fn ($item) => ['quantity' => $item->quantity, 'name' => $item->product_name_snapshot,
                'total' => $item->line_total, 'variant' => $item->variant_name_snapshot,
                'modifiers' => $item->modifiers->map(fn ($m) => ['name' => $m->name_snapshot, 'price' => $m->price_adjustment])->values(), 'notes' => $item->notes])->values(),
            'subtotal' => $order->subtotal, 'discount' => $order->discount_total, 'tax' => $order->tax_total,
            'serviceCharge' => $order->service_charge_total, 'total' => $order->grand_total,
            'paymentMethod' => $order->payment->method->value, 'paidAmount' => $order->payment->received_amount, 'change' => $order->payment->change_amount,
        ];
    }
}
