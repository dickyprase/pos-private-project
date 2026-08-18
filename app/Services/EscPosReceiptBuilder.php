<?php

namespace App\Services;

use App\Models\Order;
use App\Models\StoreSetting;

class EscPosReceiptBuilder
{
    private const WIDTH = 32;

    public function build(Order $order, ?StoreSetting $store = null): string
    {
        $order->loadMissing('items.modifiers', 'payment', 'cashier');
        $store ??= StoreSetting::current();

        $data = "\x1b\x40"; // Initialize ESC/POS.
        $data .= "\x1b\x61\x01"; // Center.
        $data .= "\x1b\x45\x01".$this->center($store->store_name ?: 'KopiPOS')."\x1b\x45\x00";
        if ($store->address) $data .= $this->center($store->address);
        if ($store->phone) $data .= $this->center($store->phone);

        $data .= "\x1b\x61\x00"; // Left.
        $data .= $this->divider();
        $data .= $this->columns('Order', $order->order_number);
        $data .= $this->columns('Waktu', $order->paid_at?->format('d/m/Y H:i') ?? '-');
        $data .= $this->columns('Kasir', $order->cashier->name);
        if ($order->table_number) $data .= $this->columns('Meja', $order->table_number);
        if ($order->customer_name) $data .= $this->columns('Nama', $order->customer_name);
        $data .= $this->divider();

        foreach ($order->items as $item) {
            $data .= $this->columns($item->quantity.'x '.$item->product_name_snapshot, $this->money($item->line_total));
            if ($item->variant_name_snapshot) $data .= $this->line('  + '.$item->variant_name_snapshot);
            foreach ($item->modifiers as $modifier) {
                $data .= $this->line('  + '.$modifier->name_snapshot.' '.$this->money($modifier->price_adjustment));
            }
            if ($item->notes) $data .= $this->line('  Catatan: '.$item->notes);
        }

        $data .= $this->divider();
        $data .= $this->columns('Subtotal', $this->money($order->subtotal));
        if ($order->discount_total) $data .= $this->columns('Diskon', '-'.$this->money($order->discount_total));
        if ($order->tax_total) $data .= $this->columns('Pajak', $this->money($order->tax_total));
        if ($order->service_charge_total) $data .= $this->columns('Layanan', $this->money($order->service_charge_total));
        $data .= "\x1b\x45\x01".$this->columns('TOTAL', 'Rp '.$this->money($order->grand_total))."\x1b\x45\x00";
        $data .= $this->columns($order->payment->method->value, $this->money($order->payment->received_amount));
        if ($order->payment->change_amount) $data .= $this->columns('Kembali', $this->money($order->payment->change_amount));
        $data .= $this->divider();
        $data .= "\x1b\x61\x01".$this->center($store->receipt_footer ?: 'Terima kasih');
        $data .= "\n\n\n\n";

        return $data;
    }

    private function center(string $text): string
    {
        $text = mb_strimwidth($this->normalize($text), 0, self::WIDTH, '');
        return str_repeat(' ', max(0, intdiv(self::WIDTH - mb_strwidth($text), 2))).$text."\n";
    }

    private function columns(string $left, string $right): string
    {
        $left = $this->normalize($left);
        $right = $this->normalize($right);
        $left = mb_strimwidth($left, 0, max(1, self::WIDTH - mb_strwidth($right) - 1), '');
        return $left.str_repeat(' ', max(1, self::WIDTH - mb_strwidth($left) - mb_strwidth($right))).$right."\n";
    }

    private function line(string $text): string
    {
        return mb_strimwidth($this->normalize($text), 0, self::WIDTH, '')."\n";
    }

    private function divider(): string
    {
        return str_repeat('-', self::WIDTH)."\n";
    }

    private function money(mixed $value): string
    {
        return number_format((int) $value, 0, ',', '.');
    }

    private function normalize(string $value): string
    {
        $value = preg_replace('/\s+/u', ' ', trim($value)) ?? '';
        return iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
    }
}
