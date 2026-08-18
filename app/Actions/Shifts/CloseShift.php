<?php

namespace App\Actions\Shifts;

use App\Enums\ShiftStatus;
use App\Models\CashMovement;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CloseShift
{
    public function handle(Shift $shift, User $cashier, int $actualCash, ?string $notes = null): Shift
    {
        if ($shift->cashier_id !== $cashier->id || $shift->status !== ShiftStatus::OPEN) throw ValidationException::withMessages(['shift' => 'Shift aktif tidak valid.']);
        return DB::transaction(function () use ($shift, $actualCash, $notes) {
            $locked = Shift::query()->lockForUpdate()->findOrFail($shift->id);
            if ($locked->status !== ShiftStatus::OPEN) throw ValidationException::withMessages(['shift' => 'Shift sudah ditutup.']);
            $cashSales = $locked->orders()->whereHas('payment', fn ($q) => $q->where('method', 'CASH'))->sum('grand_total');
            $cashIn = CashMovement::where('shift_id', $locked->id)->where('type', 'CASH_IN')->sum('amount');
            $cashOut = CashMovement::where('shift_id', $locked->id)->where('type', 'CASH_OUT')->sum('amount');
            $expected = $locked->opening_cash + $cashSales + $cashIn - $cashOut;
            $locked->update(['closed_at' => now(), 'expected_cash' => $expected, 'actual_cash' => $actualCash, 'difference' => $actualCash - $expected, 'status' => ShiftStatus::CLOSED, 'open_cashier_id' => null, 'notes' => $notes]);
            return $locked->fresh();
        }, 3);
    }
}
