<?php

namespace Tests\Unit;

use App\Services\PricingService;
use PHPUnit\Framework\TestCase;

class PricingServiceTest extends TestCase
{
    public function test_it_calculates_integer_rupiah_totals(): void
    {
        $totals = (new PricingService)->calculate(
            items: [
                ['unit_price' => 25000, 'quantity' => 2, 'modifier_total' => 5000],
                ['unit_price' => 18000, 'quantity' => 1, 'modifier_total' => 0],
            ],
            discountPercent: 10,
            taxRate: 10,
            serviceRate: 5,
        );

        $this->assertSame(78000, $totals['subtotal']);
        $this->assertSame(7800, $totals['discount_total']);
        $this->assertSame(7020, $totals['tax_total']);
        $this->assertSame(3510, $totals['service_charge_total']);
        $this->assertSame(80730, $totals['grand_total']);
    }

    public function test_discount_percentage_is_clamped_between_zero_and_one_hundred(): void
    {
        $totals = (new PricingService)->calculate(
            items: [['unit_price' => 10000, 'quantity' => 1, 'modifier_total' => 0]],
            discountPercent: 150,
            taxRate: 10,
            serviceRate: 5,
        );

        $this->assertSame(0, $totals['grand_total']);
    }
}
