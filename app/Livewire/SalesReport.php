<?php

namespace App\Livewire;

use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class SalesReport extends Component
{
    use WithPagination;

    public string $startDate;

    public string $endDate;

    public function mount(): void
    {
        $this->startDate = now()->startOfMonth()->toDateString();
        $this->endDate = now()->toDateString();
    }

    public function updatedStartDate(): void
    {
        $this->resetPage();
    }

    public function updatedEndDate(): void
    {
        $this->resetPage();
    }

    public function exportCsv()
    {
        [$start, $end] = $this->range();
        $orders = $this->query($start, $end)->with('cashier', 'payment')->get();

        return response()->streamDownload(function () use ($orders) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Order', 'Tanggal', 'Kasir', 'Metode', 'Total']);
            foreach ($orders as $order) {
                fputcsv($out, [
                    $order->order_number,
                    $order->paid_at?->format('Y-m-d H:i'),
                    $order->cashier?->name,
                    $order->payment?->method->value,
                    $order->grand_total,
                ]);
            }
            fclose($out);
        }, 'sales-'.$start->toDateString().'-'.$end->toDateString().'.csv', ['Content-Type' => 'text/csv']);
    }

    private function range(): array
    {
        try {
            $start = Carbon::parse($this->startDate)->startOfDay();
            $end = Carbon::parse($this->endDate)->endOfDay();
        } catch (\Throwable) {
            $start = now()->startOfMonth();
            $end = now()->endOfDay();
        }

        if ($start->greaterThan($end)) {
            [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
        }

        return [$start, $end];
    }

    private function query(Carbon $start, Carbon $end): Builder
    {
        return Order::query()
            ->where('orders.status', 'COMPLETED')
            ->whereBetween('orders.paid_at', [$start, $end]);
    }

    public function render()
    {
        [$start, $end] = $this->range();
        $query = $this->query($start, $end);
        $total = (int) (clone $query)->sum('orders.grand_total');
        $count = (clone $query)->count();
        $discountTotal = (int) (clone $query)->sum('orders.discount_total');
        $taxTotal = (int) (clone $query)->sum('orders.tax_total');

        $duration = max(1, (int) $start->diffInSeconds($end) + 1);
        $previousEnd = $start->copy()->subSecond();
        $previousStart = $previousEnd->copy()->subSeconds($duration - 1);
        $previousTotal = (int) $this->query($previousStart, $previousEnd)->sum('orders.grand_total');
        $revenueChange = $previousTotal === 0 ? ($total > 0 ? 100 : 0) : round((($total - $previousTotal) / $previousTotal) * 100, 1);

        $dailyAscending = (clone $query)
            ->selectRaw('DATE(orders.paid_at) as sale_date, SUM(orders.grand_total) as total, SUM(orders.discount_total) as discount, SUM(orders.tax_total) as tax, COUNT(*) as count')
            ->groupBy('sale_date')
            ->orderBy('sale_date')
            ->get();

        $monthlyRevenue = (int) Order::query()
            ->where('status', 'COMPLETED')
            ->whereBetween('paid_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->sum('grand_total');
        $monthlyTarget = (int) config('pos.monthly_revenue_target', 50_000_000);
        $targetProgress = $monthlyTarget > 0 ? min(100, (int) round(($monthlyRevenue / $monthlyTarget) * 100)) : 0;
        $daysRemaining = max(1, now()->daysInMonth - now()->day + 1);

        return view('livewire.sales-report', [
            'total' => $total,
            'count' => $count,
            'average' => $count ? (int) round($total / $count) : 0,
            'discountTotal' => $discountTotal,
            'taxTotal' => $taxTotal,
            'revenueChange' => $revenueChange,
            'periodLabel' => $start->translatedFormat('d M Y').' – '.$end->translatedFormat('d M Y'),
            'chart' => $dailyAscending->map(fn ($row) => ['label' => Carbon::parse($row->sale_date)->format('d M'), 'value' => (int) $row->total])->all(),
            'paymentBreakdown' => (clone $query)
                ->join('payments', 'payments.order_id', '=', 'orders.id')
                ->select('payments.method', DB::raw('SUM(orders.grand_total) as total'), DB::raw('COUNT(*) as count'))
                ->groupBy('payments.method')
                ->orderByDesc('total')
                ->get()
                ->map(function ($row) use ($total) {
                    $row->percentage = $total > 0 ? (int) round(((int) $row->total / $total) * 100) : 0;

                    return $row;
                }),
            'daily' => (clone $query)
                ->selectRaw('DATE(orders.paid_at) as sale_date, SUM(orders.grand_total) as total, SUM(orders.discount_total) as discount, SUM(orders.tax_total) as tax, COUNT(*) as count')
                ->groupBy('sale_date')
                ->orderByDesc('sale_date')
                ->paginate(12),
            'monthlyRevenue' => $monthlyRevenue,
            'monthlyTarget' => $monthlyTarget,
            'targetProgress' => $targetProgress,
            'dailyTargetNeeded' => max(0, (int) ceil(($monthlyTarget - $monthlyRevenue) / $daysRemaining)),
        ])->layout('layouts.app', ['title' => 'Laporan Penjualan']);
    }
}
