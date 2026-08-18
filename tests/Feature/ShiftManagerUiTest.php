<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Livewire\ShiftManager;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ShiftManagerUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_opening_cash_uses_indonesian_currency_input_while_state_stays_integer(): void
    {
        $cashier = User::factory()->create(['role' => UserRole::CASHIER]);
        $this->actingAs($cashier);

        Livewire::test(ShiftManager::class)
            ->assertSet('openingCash', 200000)
            ->assertSee('currencyInput', false)
            ->assertSee("entangle('openingCash')", false)
            ->assertSee('inputmode="numeric"', false);
    }

    public function test_reusable_alert_popup_uses_visible_modal_close_control(): void
    {
        $cashier = User::factory()->create(['role' => UserRole::CASHIER]);
        $this->actingAs($cashier);

        Livewire::test(ShiftManager::class)
            ->assertSee('data-modal-close', false)
            ->assertSee('Tutup popup', false);
    }
}
