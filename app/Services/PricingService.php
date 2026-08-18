<?php

namespace App\Services;

class PricingService
{
    /** @param array<int, array{unit_price:int, quantity:int, modifier_total?:int}> $items */
    public function calculate(array $items, int $discountPercent = 0, float $taxRate = 0, float $serviceRate = 0): array
    {
        $subtotal = collect($items)->sum(fn (array $item): int => ((int) $item['unit_price'] + (int) ($item['modifier_total'] ?? 0)) * max(1, (int) $item['quantity'])
        );
        $discountPercent = min(100, max(0, $discountPercent));
        $discountTotal = (int) round($subtotal * $discountPercent / 100);
        $taxable = max(0, $subtotal - $discountTotal);
        $taxTotal = (int) round($taxable * max(0, $taxRate) / 100);
        $serviceTotal = (int) round($taxable * max(0, $serviceRate) / 100);

        return [
            'subtotal' => $subtotal,
            'discount_total' => $discountTotal,
            'tax_total' => $taxTotal,
            'service_charge_total' => $serviceTotal,
            'rounding_total' => 0,
            'grand_total' => max(0, $taxable + $taxTotal + $serviceTotal),
        ];
    }
}
