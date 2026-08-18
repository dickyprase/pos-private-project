<?php

namespace App\Livewire;

use App\Enums\StockMovementType;
use App\Models\InventoryItem;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class InventoryManager extends Component
{
    use WithPagination;

    public ?int $selectedId = null;

    public string $movementType = 'PURCHASE';

    public float $quantity = 0;

    public string $notes = '';

    public string $search = '';

    public string $stockFilter = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStockFilter(): void
    {
        $this->resetPage();
    }

    public function adjust(): void
    {
        abort_unless(auth()->user()?->hasRole('OWNER', 'MANAGER'), 403);
        $data = $this->validate([
            'selectedId' => ['required', 'exists:inventory_items,id'],
            'movementType' => ['required', 'in:PURCHASE,ADJUSTMENT,WASTE,RETURN,OPNAME'],
            'quantity' => ['required', 'numeric', 'not_in:0'], 'notes' => ['required', 'string', 'max:255'],
        ]);
        DB::transaction(function () use ($data) {
            $item = InventoryItem::lockForUpdate()->findOrFail($data['selectedId']);
            $signed = match ($data['movementType']) {
                'PURCHASE','RETURN' => abs($data['quantity']),
                'WASTE' => -abs($data['quantity']),
                default => $data['quantity'],
            };
            $next = (float) $item->current_stock + $signed;
            abort_if($next < 0, 422, 'Stok tidak boleh negatif.');
            $item->update(['current_stock' => $next]);
            StockMovement::create([
                'inventory_item_id' => $item->id, 'type' => StockMovementType::from($data['movementType']),
                'quantity' => $signed, 'unit_cost' => $item->average_cost, 'notes' => $data['notes'],
                'created_by' => auth()->id(), 'created_at' => now(),
            ]);
        });
        $this->reset(['selectedId', 'quantity', 'notes']);
        session()->flash('success', 'Pergerakan stok tercatat.');
    }

    public function render()
    {
        return view('livewire.inventory-manager', [
            'items' => InventoryItem::with('unit')
                ->when($this->search, fn ($q) => $q->where(fn ($x) => $x->where('name', 'like', "%{$this->search}%")->orWhere('sku', 'like', "%{$this->search}%")))
                ->when($this->stockFilter === 'low', fn ($q) => $q->whereColumn('current_stock', '<=', 'minimum_stock'))
                ->when($this->stockFilter === 'safe', fn ($q) => $q->whereColumn('current_stock', '>', 'minimum_stock'))
                ->orderBy('name')->paginate(15),
            'adjustmentItems' => InventoryItem::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'movements' => StockMovement::with('inventoryItem')->latest('created_at')->limit(10)->get(),
        ])->layout('layouts.app', ['title' => 'Inventori']);
    }
}
