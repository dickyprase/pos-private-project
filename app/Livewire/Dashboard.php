<?php

namespace App\Livewire;

use App\Enums\UserRole;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Shift;
use App\Models\StoreSetting;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\WithPagination;

class Dashboard extends Component
{
    use WithPagination;

    public string $period = 'today';

    public string $shiftSearch = '';

    public string $shiftStatus = '';

    public function updatedShiftSearch(): void
    {
        $this->resetPage('shiftPage');
    }

    public function updatedShiftStatus(): void
    {
        $this->resetPage('shiftPage');
    }

    public function setPeriod(string $period): void
    {
        if (in_array($period, ['today', 'week', 'month'], true)) {
            $this->period = $period;
        }
    }

    public function render()
    {
        [$start, $end, $previousStart, $previousEnd] = $this->periodRange();
        $orders = Order::query()
            ->where('status', 'COMPLETED')
            ->whereBetween('paid_at', [$start, $end])
            ->get(['id', 'grand_total', 'paid_at']);
        $previousOrders = Order::query()
            ->where('status', 'COMPLETED')
            ->whereBetween('paid_at', [$previousStart, $previousEnd])
            ->get(['id', 'grand_total', 'paid_at']);

        $revenue = (int) $orders->sum('grand_total');
        $transactions = $orders->count();
        $averageOrder = $transactions ? (int) round($revenue / $transactions) : 0;
        $previousRevenue = (int) $previousOrders->sum('grand_total');
        $previousCount = $previousOrders->count();
        $previousAverage = $previousCount ? (int) round($previousRevenue / $previousCount) : 0;
        $grossProfit = $this->grossProfit($start, $end);
        $previousGrossProfit = $this->grossProfit($previousStart, $previousEnd);

        $lowStock = InventoryItem::query()
            ->where('is_active', true)
            ->whereColumn('current_stock', '<=', 'minimum_stock')
            ->with('unit')
            ->orderByRaw('CASE WHEN minimum_stock = 0 THEN 1 ELSE 0 END')
            ->orderBy('current_stock')
            ->limit(5)
            ->get();

        $monthlyRevenue = (int) Order::query()
            ->where('status', 'COMPLETED')
            ->whereBetween('paid_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->sum('grand_total');
        $monthlyTarget = (int) config('pos.monthly_revenue_target', 50_000_000);

        return view('livewire.dashboard', [
            'store' => StoreSetting::current(),
            'revenue' => $revenue,
            'transactions' => $transactions,
            'averageOrder' => $averageOrder,
            'grossProfit' => $grossProfit,
            'revenueChange' => $this->percentageChange($revenue, $previousRevenue),
            'transactionChange' => $this->percentageChange($transactions, $previousCount),
            'averageChange' => $this->percentageChange($averageOrder, $previousAverage),
            'profitChange' => $this->percentageChange($grossProfit, $previousGrossProfit),
            'lowStock' => $lowStock,
            'chart' => $this->chartData($orders, $start, $end),
            'topProducts' => $this->topProducts($start, $end),
            'paymentMethods' => $this->paymentMethods($start, $end, $revenue),
            'recentOrders' => Order::query()->with('cashier', 'payment')->latest('paid_at')->limit(4)->get(),
            'shiftSummary' => $this->shiftSummary(),
            'monthlyRevenue' => $monthlyRevenue,
            'monthlyTarget' => $monthlyTarget,
            'targetProgress' => $monthlyTarget > 0 ? min(100, (int) round(($monthlyRevenue / $monthlyTarget) * 100)) : 0,
            'dailyTargetNeeded' => $this->dailyTargetNeeded($monthlyRevenue, $monthlyTarget),
            'periodLabel' => match ($this->period) {
                'week' => '7 hari terakhir',
                'month' => '30 hari terakhir',
                default => 'hari ini',
            },
            'isOwner' => auth()->user()->role === UserRole::OWNER,
        ])->layout('layouts.app', ['title' => 'Dashboard']);
    }

    private function periodRange(): array
    {
        $end = now()->endOfDay();
        $start = match ($this->period) {
            'week' => now()->subDays(6)->startOfDay(),
            'month' => now()->subDays(29)->startOfDay(),
            default => now()->startOfDay(),
        };
        $duration = $start->diffInSeconds($end) + 1;
        $previousEnd = $start->copy()->subSecond();
        $previousStart = $previousEnd->copy()->subSeconds($duration - 1);

        return [$start, $end, $previousStart, $previousEnd];
    }

    private function percentageChange(int $current, int $previous): float
    {
        if ($previous === 0) {
            return $current > 0 ? 100 : 0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    private function grossProfit(Carbon $start, Carbon $end): int
    {
        return max(0, (int) OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->leftJoin('products', 'products.id', '=', 'order_items.product_id')
            ->where('orders.status', 'COMPLETED')
            ->whereBetween('orders.paid_at', [$start, $end])
            ->selectRaw('COALESCE(SUM(order_items.line_total - (COALESCE(products.cost_estimate, 0) * order_items.quantity)), 0) as profit')
            ->value('profit'));
    }

    private function topProducts(Carbon $start, Carbon $end): Collection
    {
        return OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.status', 'COMPLETED')
            ->whereBetween('orders.paid_at', [$start, $end])
            ->select('order_items.product_name_snapshot')
            ->selectRaw('SUM(order_items.quantity) as sold')
            ->selectRaw('SUM(order_items.line_total) as revenue')
            ->groupBy('order_items.product_name_snapshot')
            ->orderByDesc('sold')
            ->limit(4)
            ->get();
    }

    private function paymentMethods(Carbon $start, Carbon $end, int $revenue): Collection
    {
        return Payment::query()
            ->join('orders', 'orders.id', '=', 'payments.order_id')
            ->where('orders.status', 'COMPLETED')
            ->whereBetween('payments.paid_at', [$start, $end])
            ->select('payments.method')
            ->selectRaw('SUM(payments.amount) as total')
            ->selectRaw('COUNT(*) as transaction_count')
            ->groupBy('payments.method')
            ->orderByDesc('total')
            ->get()
            ->map(function ($payment) use ($revenue) {
                $payment->percentage = $revenue > 0 ? (int) round(((int) $payment->total / $revenue) * 100) : 0;

                return $payment;
            });
    }

    private function chartData(Collection $orders, Carbon $start, Carbon $end): array
    {
        $slots = [];
        if ($this->period === 'today') {
            foreach ([8, 10, 12, 14, 16, 18, 20] as $hour) {
                $slots[] = ['label' => sprintf('%02d.00', $hour), 'start' => today()->setHour($hour), 'end' => today()->setHour($hour + 2)->subSecond()];
            }
        } elseif ($this->period === 'week') {
            for ($day = 0; $day < 7; $day++) {
                $date = $start->copy()->addDays($day);
                $slots[] = ['label' => $date->translatedFormat('D'), 'start' => $date->copy()->startOfDay(), 'end' => $date->copy()->endOfDay()];
            }
        } else {
            $seconds = max(1, (int) floor($start->diffInSeconds($end) / 7));
            for ($slot = 0; $slot < 7; $slot++) {
                $slotStart = $start->copy()->addSeconds($seconds * $slot);
                $slotEnd = $slot === 6 ? $end->copy() : $slotStart->copy()->addSeconds($seconds)->subSecond();
                $slots[] = ['label' => $slotStart->format('d M'), 'start' => $slotStart, 'end' => $slotEnd];
            }
        }

        return collect($slots)->map(fn (array $slot) => [
            'label' => $slot['label'],
            'value' => (int) $orders->filter(fn (Order $order) => $order->paid_at?->between($slot['start'], $slot['end']))->sum('grand_total'),
        ])->all();
    }

    private function shiftSummary()
    {
        $shifts = Shift::query()
            ->with(['cashier', 'orders' => fn ($query) => $query
                ->whereDate('paid_at', today())
                ->where('status', 'COMPLETED')
                ->with('payment')])
            ->where(function ($query) {
                $query->whereDate('opened_at', today())->orWhere('status', 'OPEN');
            })
            ->when($this->shiftSearch, fn ($query) => $query->whereHas('cashier', fn ($cashier) => $cashier->where('name', 'like', "%{$this->shiftSearch}%")))
            ->when($this->shiftStatus, fn ($query) => $query->where('status', $this->shiftStatus))
            ->latest('opened_at')
            ->paginate(6, ['*'], 'shiftPage');

        $shifts->setCollection($shifts->getCollection()->map(function (Shift $shift) {
            $shift->transaction_count = $shift->orders->count();
            $shift->sales_total = (int) $shift->orders->sum('grand_total');
            $shift->cash_total = (int) $shift->orders
                ->filter(fn (Order $order) => $order->payment?->method?->value === 'CASH')
                ->sum('grand_total');

            return $shift;
        }));

        return $shifts;
    }

    private function dailyTargetNeeded(int $revenue, int $target): int
    {
        $daysRemaining = max(1, now()->daysInMonth - now()->day + 1);

        return max(0, (int) ceil(($target - $revenue) / $daysRemaining));
    }
}
