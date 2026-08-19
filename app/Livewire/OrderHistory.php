<?php

namespace App\Livewire;

use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class OrderHistory extends Component
{
    use WithPagination;

    public string $search = '';
    public string $status = '';
    public ?int $actionOrderId = null;
    public ?int $detailOrderId = null;
    public string $detailOrderNumber = '';
    public ?Order $detailOrder = null;

    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedStatus(): void { $this->resetPage(); }

    public function openDetail(int $orderId): void
    {
        $order = Order::with('items.modifiers', 'payment', 'cashier')->findOrFail($orderId);
        $this->detailOrder = $order;
        $this->detailOrderId = $order->id;
        $this->detailOrderNumber = $order->order_number;
    }

    public function closeDetail(): void { $this->detailOrderId = null; $this->detailOrder = null; }

    public function render()
    {
        $user = auth()->user();
        return view('livewire.order-history', [
            'orders' => Order::query()
                ->with('cashier', 'payment')
                ->when($user->hasRole('CASHIER'), fn ($q) => $q->where('cashier_id', $user->id))
                ->when($this->search, fn ($q) => $q->where(function ($q) {
                    $q->where('order_number', 'like', "%{$this->search}%")
                      ->orWhere('customer_name', 'like', "%{$this->search}%")
                      ->orWhere('table_number', 'like', "%{$this->search}%")
                      ->orWhereHas('cashier', fn ($cashier) => $cashier->where('name', 'like', "%{$this->search}%"))
                      ->orWhereHas('payment', fn ($payment) => $payment->where('method', 'like', "%{$this->search}%"));
                }))
                ->when($this->status, fn ($q) => $q->where('status', $this->status))
                ->latest()
                ->paginate(15),
            'canManage' => $user->hasRole('OWNER', 'MANAGER'),
        ])->layout('layouts.app', ['title' => 'Transaksi']);
    }
}
