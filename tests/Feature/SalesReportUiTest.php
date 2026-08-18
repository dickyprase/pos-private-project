<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Livewire\SalesReport;
use App\Models\Order;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SalesReportUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_report_renders_trend_target_kpis_and_daily_detail(): void
    {
        $owner = User::factory()->create(['role' => UserRole::OWNER]);
        $shift = Shift::create([
            'cashier_id' => $owner->id,
            'opened_at' => now()->startOfDay(),
            'status' => 'CLOSED',
            'opening_cash' => 100000,
        ]);
        Order::create([
            'order_number' => 'REPORT-001',
            'submission_token' => str()->uuid(),
            'shift_id' => $shift->id,
            'cashier_id' => $owner->id,
            'order_type' => 'TAKE_AWAY',
            'status' => 'COMPLETED',
            'subtotal' => 100000,
            'discount_total' => 10000,
            'tax_total' => 9000,
            'service_charge_total' => 0,
            'grand_total' => 99000,
            'paid_at' => now(),
        ]);
        $this->actingAs($owner);

        Livewire::test(SalesReport::class)
            ->assertSee('Tren Penjualan')
            ->assertSee('Target Bulanan')
            ->assertSee('Penjualan Bersih')
            ->assertSee('Detail Penjualan Harian')
            ->assertViewHas('chart', fn (array $chart) => count($chart) >= 1)
            ->assertViewHas('monthlyTarget', 50_000_000)
            ->assertViewHas('monthlyRevenue', 99000);
    }

    public function test_invalid_date_range_is_normalized(): void
    {
        $owner = User::factory()->create(['role' => UserRole::OWNER]);
        $this->actingAs($owner);

        Livewire::test(SalesReport::class)
            ->set('startDate', now()->toDateString())
            ->set('endDate', now()->subWeek()->toDateString())
            ->assertHasNoErrors()
            ->assertViewHas('chart');
    }
}
