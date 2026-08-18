<?php

namespace App\Livewire;

use App\Enums\ShiftStatus;
use App\Models\CashMovement;
use App\Models\Shift;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ShiftManager extends Component
{
    public int $openingCash = 200000;

    public ?int $actualCash = null;

    public string $notes = '';

    public string $movementType = 'CASH_IN';

    public int $movementAmount = 0;

    public string $movementReason = '';

    public function openShift(): void
    {
        abort_if(auth()->user()->activeShift(), 422, 'Shift aktif sudah ada.');
        $this->validate(['openingCash' => ['required', 'integer', 'min:0']]);
        Shift::create([
            'cashier_id' => auth()->id(), 'open_cashier_id' => auth()->id(), 'opened_at' => now(),
            'opening_cash' => $this->openingCash, 'expected_cash' => $this->openingCash,
            'status' => ShiftStatus::OPEN,
        ]);
        session()->flash('success', 'Shift berhasil dibuka.');
    }

    public function addMovement(): void
    {
        $shift = auth()->user()->activeShift();
        abort_unless($shift, 422, 'Tidak ada shift aktif.');
        $this->validate([
            'movementType' => ['required', 'in:CASH_IN,CASH_OUT'],
            'movementAmount' => ['required', 'integer', 'min:1'],
            'movementReason' => ['required', 'string', 'max:255'],
        ]);
        CashMovement::create([
            'shift_id' => $shift->id, 'type' => $this->movementType,
            'amount' => $this->movementAmount, 'reason' => $this->movementReason,
            'created_by' => auth()->id(), 'created_at' => now(),
        ]);
        $this->reset('movementAmount', 'movementReason');
        session()->flash('success', 'Pergerakan kas tercatat.');
    }

    public function closeShift(): void
    {
        $shift = auth()->user()->activeShift();
        abort_unless($shift, 422, 'Tidak ada shift aktif.');
        $this->validate(['actualCash' => ['required', 'integer', 'min:0']]);
        DB::transaction(function () use ($shift) {
            $cashSales = $shift->orders()->whereHas('payment', fn ($q) => $q->where('method', 'CASH'))->sum('grand_total');
            $cashIn = CashMovement::where('shift_id', $shift->id)->where('type', 'CASH_IN')->sum('amount');
            $cashOut = CashMovement::where('shift_id', $shift->id)->where('type', 'CASH_OUT')->sum('amount');
            $expected = $shift->opening_cash + $cashSales + $cashIn - $cashOut;
            $shift->update([
                'closed_at' => now(), 'expected_cash' => $expected,
                'actual_cash' => $this->actualCash, 'difference' => $this->actualCash - $expected,
                'status' => ShiftStatus::CLOSED, 'open_cashier_id' => null, 'notes' => $this->notes,
            ]);
        });
        session()->flash('success', 'Shift berhasil ditutup.');
    }

    public function render()
    {
        $activeShift = auth()->user()->activeShift();

        return view('livewire.shift-manager', [
            'activeShift' => $activeShift,
            'movements' => $activeShift ? CashMovement::where('shift_id', $activeShift->id)->latest('created_at')->get() : collect(),
        ])->layout('layouts.app', ['title' => 'Shift']);
    }
}
