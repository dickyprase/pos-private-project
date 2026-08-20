<?php

namespace App\Livewire\Pos;

use App\Actions\Orders\CompleteOrder;
use App\Models\Category;
use App\Models\ModifierOption;
use App\Models\Product;
use App\Models\StoreSetting;
use App\Models\Order;
use App\Services\PricingService;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Component;

class CashierScreen extends Component
{
    public string $search = '';

    public ?int $activeCategory = null;

    public bool $favoriteOnly = false;

    public string $orderType = 'DINE_IN';

    public string $tableNumber = '';

    public string $customerName = '';

    public array $cart = [];

    public int $discount = 0;

    public string $notes = '';

    public string $paymentMethod = 'CASH';

    public int $receivedAmount = 0;

    public string $referenceNumber = '';

    public string $submissionToken = '';

    public ?int $lastOrderId = null;

    public bool $paymentOpen = false;

    public bool $cartOpen = false;

    public bool $heldOpen = false;

    public bool $modifierOpen = false;

    public bool $successOpen = false;

    public string $lastOrderNumber = '';

    public int $lastOrderTotal = 0;

    public ?int $modifierProductId = null;

    public string $modifierProductName = '';

    public int $modifierBasePrice = 0;

    public array $modifierGroups = [];

    public array $selectedModifiers = [];

    public string $itemNotes = '';

    public bool $itemNoteOpen = false;

    public bool $historyOpen = false;

    public ?int $historyDetailId = null;

    public ?int $editingItemIndex = null;

    public string $editingItemName = '';

    public string $editingItemNote = '';

    public float $taxRate = 0;

    public float $serviceChargeRate = 0;

    public string $storeName = 'Kopi Senja';

    public bool $qrisEnabled = false;

    public ?string $qrisImagePath = null;

    public function mount(): void
    {
        $this->submissionToken = (string) Str::uuid();
        $settings = StoreSetting::current();
        $this->taxRate = $settings->tax_enabled ? (float) $settings->tax_rate : 0;
        $this->serviceChargeRate = 0;
        $this->storeName = $settings->store_name;
        $this->qrisEnabled = (bool) ($settings->qris_enabled ?? false);
        $this->qrisImagePath = $settings->qris_image_path;
    }

    #[Computed]
    public function products()
    {
        return Product::query()
            ->with('category')
            ->where('is_active', true)
            ->when($this->activeCategory, fn ($q) => $q->where('category_id', $this->activeCategory))
            ->when($this->favoriteOnly, fn ($q) => $q->where('is_favorite', true))
            ->when($this->search, fn ($q) => $q->where(fn ($x) => $x->where('name', 'like', "%{$this->search}%")->orWhere('sku', 'like', "%{$this->search}%")))
            ->orderByDesc('is_favorite')->orderBy('sort_order')->orderBy('name')->limit(200)->get();
    }

    public function selectCategory(?int $categoryId): void
    {
        $this->activeCategory = $categoryId;
        $this->favoriteOnly = false;
        unset($this->products);
    }

    public function selectFavorites(): void
    {
        $this->activeCategory = null;
        $this->favoriteOnly = true;
        unset($this->products);
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->activeCategory = null;
        $this->favoriteOnly = false;
        unset($this->products);
    }

    public function quickAdd(int $productId): void
    {
        $product = Product::query()->with('modifierGroups.options')->whereKey($productId)->where('is_active', true)->where('is_available', true)->firstOrFail();
        if ($product->modifierGroups->isNotEmpty()) {
            $this->modifierProductId = $product->id;
            $this->modifierProductName = $product->name;
            $this->modifierBasePrice = $product->base_price;
            $this->modifierGroups = $product->modifierGroups->map(fn ($group) => [
                'id' => $group->id,
                'name' => $group->name,
                'min' => $group->min_selection,
                'max' => $group->max_selection,
                'required' => $group->is_required,
                'options' => $group->options->where('is_active', true)->map(fn ($option) => ['id' => $option->id, 'name' => $option->name, 'price' => $option->price_adjustment])->values()->all(),
            ])->values()->all();
            $this->selectedModifiers = [];
            foreach ($this->modifierGroups as $group) {
                if ($group['required'] && $group['options'] !== []) {
                    $this->selectedModifiers[$group['id']] = [$group['options'][0]['id']];
                }
            }
            $this->itemNotes = '';
            $this->modifierOpen = true;

            return;
        }
        $this->appendItem($product, [], '');
    }

    public function selectModifier(int $groupId, int $optionId): void
    {
        $group = collect($this->modifierGroups)->firstWhere('id', $groupId);
        if (! $group || ! collect($group['options'])->contains('id', $optionId)) {
            return;
        }
        $current = $this->selectedModifiers[$groupId] ?? [];
        if ($group['max'] === 1) {
            $this->selectedModifiers[$groupId] = $current === [$optionId] && ! $group['required'] ? [] : [$optionId];
        } else {
            $this->selectedModifiers[$groupId] = in_array($optionId, $current, true)
                ? array_values(array_diff($current, [$optionId]))
                : array_slice([...$current, $optionId], 0, $group['max']);
        }
    }

    public function addConfiguredItem(): void
    {
        $ids = collect($this->selectedModifiers)->flatten()->map(fn ($id) => (int) $id)->unique()->values();
        foreach ($this->modifierGroups as $group) {
            $count = collect($this->selectedModifiers[$group['id']] ?? [])->count();
            if ($count < $group['min'] || $count > $group['max']) {
                throw ValidationException::withMessages(['modifierSelection' => "Pilih {$group['name']} sesuai ketentuan."]);
            }
        }
        $product = Product::query()->whereKey($this->modifierProductId)->where('is_active', true)->where('is_available', true)->firstOrFail();
        $this->appendItem($product, $ids->all(), $this->itemNotes);
        $this->modifierOpen = false;
    }

    #[Computed]
    public function modifierPrice(): int
    {
        $selectedIds = collect($this->selectedModifiers)->flatten();

        return $this->modifierBasePrice + collect($this->modifierGroups)
            ->flatMap(fn ($group) => $group['options'])
            ->whereIn('id', $selectedIds)
            ->sum('price');
    }

    private function appendItem(Product $product, array $modifierIds, string $notes): void
    {
        $modifiers = ModifierOption::query()->whereIn('id', $modifierIds)->where('is_active', true)->get();
        $key = 'p'.$product->id.'-'.collect($modifierIds)->sort()->implode('-').'-'.md5($notes);
        foreach ($this->cart as $index => $row) {
            if (($row['key'] ?? null) === $key) {
                $this->cart[$index]['quantity']++;
                $this->recalculate();

                return;
            }
        }
        $this->cart[] = [
            'key' => $key, 'product_id' => $product->id, 'name' => $product->name,
            'sku' => $product->sku, 'unit_price' => $product->base_price,
            'quantity' => 1, 'modifier_ids' => $modifiers->pluck('id')->all(),
            'modifiers' => $modifiers->pluck('name')->all(),
            'modifier_total' => (int) $modifiers->sum('price_adjustment'), 'notes' => $notes,
        ];
        $this->recalculate();
    }

    public function increment(int $index): void
    {
        if (isset($this->cart[$index])) {
            $this->cart[$index]['quantity']++;
            $this->recalculate();
        }
    }

    public function decrement(int $index): void
    {
        if (! isset($this->cart[$index])) {
            return;
        }
        if ($this->cart[$index]['quantity'] <= 1) {
            $this->remove($index);

            return;
        }
        $this->cart[$index]['quantity']--;
        $this->recalculate();
    }

    public function setQuantity(int $index, int $quantity): void
    {
        if (! isset($this->cart[$index])) {
            return;
        }
        if ($quantity <= 0) {
            $this->remove($index);

            return;
        }
        $this->cart[$index]['quantity'] = min(99, $quantity);
        $this->recalculate();
    }

    public function remove(int $index): void
    {
        unset($this->cart[$index]);
        $this->cart = array_values($this->cart);
        $this->recalculate();
    }

    public function openItemNote(int $index): void
    {
        if (! isset($this->cart[$index])) {
            return;
        }

        $this->editingItemIndex = $index;
        $this->editingItemName = $this->cart[$index]['name'];
        $this->editingItemNote = $this->cart[$index]['notes'] ?? '';
        $this->itemNoteOpen = true;
    }

    public function saveItemNote(): void
    {
        $this->validate(['editingItemNote' => ['nullable', 'string', 'max:180']]);
        if ($this->editingItemIndex === null || ! isset($this->cart[$this->editingItemIndex])) {
            $this->itemNoteOpen = false;

            return;
        }

        $this->cart[$this->editingItemIndex]['notes'] = trim($this->editingItemNote);
        $this->itemNoteOpen = false;
    }

    public function clearCart(): void
    {
        $this->resetCart();
        $this->cartOpen = false;
    }

    public function holdOrder(): void
    {
        if ($this->cart === []) {
            throw ValidationException::withMessages(['cart' => 'Cart masih kosong.']);
        }
        session()->put('held_orders.'.$this->submissionToken, [
            'label' => 'Held '.now()->format('H:i'), 'cart' => $this->cart,
            'order_type' => $this->orderType, 'table_number' => $this->tableNumber, 'customer_name' => $this->customerName,
            'discount' => $this->discount, 'notes' => $this->notes,
        ]);
        $this->resetCart();
        session()->flash('success', 'Pesanan ditahan.');
    }

    public function resumeHeld(string $token): void
    {
        $held = session()->pull('held_orders.'.$token);
        if (! $held) {
            return;
        }
        $this->cart = $held['cart'];
        $this->orderType = $held['order_type'];
        $this->tableNumber = $held['table_number'];
        $this->customerName = $held['customer_name'] ?? '';
        $this->discount = $held['discount'];
        $this->notes = $held['notes'];
        $this->submissionToken = (string) Str::uuid();
        $this->recalculate();
    }

    public function openPayment(): void
    {
        if ($this->cart === []) {
            throw ValidationException::withMessages(['cart' => 'Cart masih kosong.']);
        }
        $this->validateOrderIdentity();
        $this->refreshPaymentAvailability();
        $this->receivedAmount = 0;
        $this->cartOpen = false;
        $this->paymentOpen = true;
    }

    public function completePayment(CompleteOrder $completeOrder): void
    {
        $this->validateOrderIdentity();
        $this->refreshPaymentAvailability();
        $order = $completeOrder->handle(auth()->user(), [
            'submission_token' => $this->submissionToken,
            'order_type' => $this->orderType,
            'table_number' => trim($this->tableNumber),
            'customer_name' => trim($this->customerName),
            'items' => array_map(fn ($row) => [
                'product_id' => $row['product_id'], 'quantity' => $row['quantity'],
                'modifier_ids' => $row['modifier_ids'] ?? [], 'notes' => $row['notes'] ?? null,
            ], $this->cart),
            'discount' => $this->discount, 'notes' => $this->notes,
            'payment' => [
                'method' => $this->paymentMethod, 'received_amount' => $this->receivedAmount,
                'reference_number' => $this->referenceNumber ?: null,
            ],
        ]);
        $this->lastOrderId = $order->id;
        $this->lastOrderNumber = $order->order_number;
        $this->lastOrderTotal = $order->grand_total;
        $this->paymentOpen = false;
        $this->resetCart(keepLastOrder: true);
        $this->successOpen = true;
        session()->flash('success', "Transaksi {$order->order_number} berhasil.");
    }

    public function updatedPaymentMethod(string $method): void
    {
        if (! in_array($method, ['CASH', 'QRIS'], true) || ($method === 'QRIS' && ! $this->qrisEnabled)) {
            $this->paymentMethod = 'CASH';
        }
    }

    private function refreshPaymentAvailability(): void
    {
        $settings = StoreSetting::current();
        $this->qrisEnabled = (bool) $settings->qris_enabled;
        $this->qrisImagePath = $settings->qris_image_path;
        if ($this->paymentMethod === 'QRIS' && ! $this->qrisEnabled) {
            $this->paymentMethod = 'CASH';
            $this->dispatch('ui-alert:open', type: 'warning', title: 'QRIS tidak tersedia', message: 'QRIS dinonaktifkan. Metode pembayaran dikembalikan ke Cash.');
        }
    }

    public function printLastReceipt(): void
    {
        if (! $this->lastOrderId) {
            return;
        }

        $order = \App\Models\Order::query()->with('items.modifiers', 'payment', 'cashier')->findOrFail($this->lastOrderId);
        abort_unless(auth()->user()->hasRole('OWNER', 'MANAGER') || $order->cashier_id === auth()->id(), 403);
        $receipt = app(\App\Services\EscPosReceiptBuilder::class)->build($order);
        $this->dispatch('print-receipt', escposBase64: base64_encode($receipt));
    }

    public function printBrowserReceipt(): void
    {
        if (! $this->lastOrderId) return;
        $order = Order::findOrFail($this->lastOrderId);
        abort_unless(auth()->user()->hasRole('OWNER', 'MANAGER') || $order->cashier_id === auth()->id(), 403);
        $this->dispatch('open-receipt', url: route('orders.receipt', $order));
    }

    #[Computed]
    public function recentOrders()
    {
        return Order::query()->with('payment')->where('cashier_id', auth()->id())->latest()->limit(30)->get();
    }

    public function openHistory(): void { $this->historyOpen = true; }

    public function openHistoryDetail(int $id): void
    {
        abort_unless(Order::whereKey($id)->where('cashier_id', auth()->id())->exists(), 403);
        $this->historyDetailId = $id;
    }

    public function closeHistory(): void { $this->historyOpen = false; $this->historyDetailId = null; }

    public function printAndStartNewOrder(): void
    {
        $this->printLastReceipt();
        $this->startNewOrder();
    }

    public function startNewOrder(): void
    {
        $this->successOpen = false;
        $this->orderType = 'DINE_IN';
    }

    public int $subtotal = 0;

    public int $discountTotal = 0;

    public int $taxTotal = 0;

    public int $serviceChargeTotal = 0;

    public int $grandTotal = 0;

    public function updatedDiscount(): void
    {
        $this->discount = min(100, max(0, $this->discount));
        $this->recalculate();
    }

    private function recalculate(): void
    {
        $totals = app(PricingService::class)->calculate($this->cart, min(100, max(0, $this->discount)), $this->taxRate, $this->serviceChargeRate);
        $this->subtotal = $totals['subtotal'];
        $this->discountTotal = $totals['discount_total'];
        $this->taxTotal = $totals['tax_total'];
        $this->serviceChargeTotal = $totals['service_charge_total'];
        $this->grandTotal = $totals['grand_total'];
    }

    private function resetCart(bool $keepLastOrder = false): void
    {
        $this->cart = [];
        $this->discount = 0;
        $this->notes = '';
        $this->tableNumber = '';
        $this->customerName = '';
        $this->paymentMethod = 'CASH';
        $this->receivedAmount = 0;
        $this->referenceNumber = '';
        $this->submissionToken = (string) Str::uuid();
        if (! $keepLastOrder) {
            $this->lastOrderId = null;
        }
        $this->recalculate();
    }

    private function validateOrderIdentity(): void
    {
        $this->validate([
            'tableNumber' => ['required', 'string', 'max:30'],
            'customerName' => ['required', 'string', 'max:120'],
        ], [
            'tableNumber.required' => 'Nomor meja wajib diisi.',
            'customerName.required' => 'Atas nama wajib diisi.',
        ]);
    }

    public function render()
    {
        return view('livewire.pos.cashier-screen', [
            'categories' => Category::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'activeShift' => auth()->user()->activeShift(),
            'heldOrders' => collect(session('held_orders', [])),
        ])->layout('layouts.pos', ['title' => 'Point of Sale']);
    }
}
