<?php

namespace Tests\Feature;

use Tests\TestCase;

class PersistentBluetoothPrinterTest extends TestCase
{
    public function test_persistent_printer_manager_contract(): void
    {
        $source = file_get_contents(resource_path('js/printer-bluetooth.js'));
        foreach (['init()', 'pairNewPrinter()', 'ensureConnected()', 'printEscPos(payload)', 'getStatus()', 'onStatusChange(callback)', 'forgetPrinter()'] as $contract) {
            $this->assertStringContainsString($contract, $source);
        }
        $this->assertStringContainsString('indexedDB.open', $source);
        $this->assertStringContainsString('navigator.bluetooth.getDevices()', $source);
        $this->assertStringContainsString('BLE_CHUNK_SIZE = 20', $source);
        $this->assertStringContainsString("gattserverdisconnected", $source);
        $this->assertStringContainsString("Livewire.on('print-receipt'", $source);
        $this->assertStringContainsString('[BT DEBUG] getDevices() count:', $source);
        $this->assertStringContainsString('[BT DEBUG] savedId dari IndexedDB:', $source);
        $this->assertStringContainsString('[BT DEBUG] match ditemukan?', $source);
        $this->assertStringContainsString("this.setStatus('checking')", $source);
        $this->assertStringContainsString("this.setStatus('saved-unavailable')", $source);
        $this->assertStringContainsString('[BT] IndexedDB device.id tersimpan:', $source);
        $this->assertStringContainsString('[BT] getDevices() hasil:', $source);
    }

    public function test_livewire_dispatches_raw_escpos_as_base64(): void
    {
        $source = file_get_contents(app_path('Livewire/Pos/CashierScreen.php'));
        $builder = file_get_contents(app_path('Services/EscPosReceiptBuilder.php'));
        $composer = file_get_contents(base_path('composer.json'));

        $this->assertStringContainsString("dispatch('print-receipt'", $source);
        $this->assertStringContainsString('EscPosReceiptBuilder::class', $source);
        $this->assertStringContainsString('base64_encode($receipt)', $source);
        $this->assertStringContainsString('\\x1b\\x40', $builder);
        $this->assertStringNotContainsString('Mike42', $builder);
        $this->assertStringNotContainsString('mike42/escpos-php', $composer);
    }
}
