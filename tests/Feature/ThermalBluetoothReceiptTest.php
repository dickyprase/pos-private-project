<?php

namespace Tests\Feature;

use Tests\TestCase;

class ThermalBluetoothReceiptTest extends TestCase
{
    public function test_web_bluetooth_module_uses_rpp02n_service_and_escpos(): void
    {
        $source = file_get_contents(resource_path('js/thermal-printer.js'));

        $this->assertStringContainsString('000018f0-0000-1000-8000-00805f9b34fb', $source);
        $this->assertStringContainsString('navigator.bluetooth.requestDevice', $source);
        $this->assertStringContainsString('navigator.bluetooth.getDevices', $source);
        $this->assertStringContainsString('reconnectRememberedPrinter', $source);
        $this->assertStringContainsString('ensurePrinterConnected', $source);
        $this->assertStringContainsString('acceptAllDevices: true', $source);
        $this->assertStringContainsString('buildReceiptPayload', $source);
        $this->assertStringContainsString('writeValueWithoutResponse', $source);
        $this->assertStringContainsString("bytes(ESC, 0x40)", $source);
        $this->assertStringContainsString("ESC, 0x45, 0x01", $source);
    }

    public function test_receipt_has_bluetooth_controls_and_58mm_layout(): void
    {
        $view = file_get_contents(resource_path('views/receipts/thermal.blade.php'));

        $this->assertStringContainsString('size:58mm auto', $view);
        $this->assertStringContainsString('Hubungkan RPP02N', $view);
        $this->assertStringContainsString('Cetak Bluetooth', $view);
        $this->assertStringContainsString('receipt-transaction', $view);
        $this->assertStringNotContainsString("addEventListener('load',()=>window.print())", $view);
    }
}
