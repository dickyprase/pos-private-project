<?php

namespace App\Actions\Shifts;

use App\Enums\ShiftStatus;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class OpenShift
{
    public function handle(User $cashier, int $openingCash): Shift
    {
        if ($cashier->activeShift()) throw ValidationException::withMessages(['shift' => 'Shift aktif sudah ada.']);
        return Shift::create(['cashier_id' => $cashier->id, 'open_cashier_id' => $cashier->id, 'opened_at' => now(), 'opening_cash' => $openingCash, 'expected_cash' => $openingCash, 'status' => ShiftStatus::OPEN]);
    }
}
