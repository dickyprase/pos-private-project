@php
    $itemCount = collect($cart)->sum('quantity');
@endphp

<div class="flex items-center justify-between border-b-2 border-brand-500 bg-[#f7f7f5] px-3 py-3 text-stone-900 sm:px-4">
    <div>
        <h2 @if($mobile ?? false) id="mobileCartTitle" @endif class="text-lg font-black tracking-tight">Pesanan Saat Ini</h2>
        <p class="text-xs text-stone-500">{{ $orderType === 'DINE_IN' ? 'Dine In' : 'Take Away' }} · {{ $itemCount }} item</p>
    </div>
    <div class="flex items-center gap-1">
        <button type="button" @click="$dispatch('ui-alert:open', { type: 'warning', title: 'Kosongkan pesanan?', message: 'Semua item yang sudah dipilih akan dihapus dari pesanan saat ini.', confirmLabel: 'Ya, kosongkan', action: 'clearCart' })" class="pressable min-h-10 rounded-xl px-3 text-xs font-bold text-rose-600 hover:bg-rose-50 hover:text-rose-700 disabled:opacity-40" @disabled(! $cart)>Kosongkan</button>
        @if($mobile ?? false)
            <x-ui.modal-close wire:click="$set('cartOpen', false)" label="Tutup keranjang" />
        @endif
    </div>
</div>

<div class="scrollbar-thin min-h-0 flex-1 overflow-y-auto px-3 py-2 sm:px-4">
    <div class="mb-3 grid grid-cols-2 gap-2 rounded-2xl border border-brand-100 bg-brand-50/60 p-3">
        <label class="block"><span class="mb-1.5 block text-xs font-bold text-stone-700">Nomor meja <span class="text-rose-600">*</span></span><input wire:model="tableNumber" class="pos-field bg-white" placeholder="A-03">@error('tableNumber')<span class="mt-1 block text-[11px] font-semibold text-rose-600">{{ $message }}</span>@enderror</label>
        <label class="block"><span class="mb-1.5 block text-xs font-bold text-stone-700">Atas nama <span class="text-rose-600">*</span></span><input wire:model="customerName" class="pos-field bg-white" placeholder="Nama pelanggan">@error('customerName')<span class="mt-1 block text-[11px] font-semibold text-rose-600">{{ $message }}</span>@enderror</label>
    </div>

    @forelse($cart as $i => $item)
        <article data-cart-item wire:key="{{ $mount ?? 'cart' }}-cart-{{ $item['key'] }}" class="cart-item-enter mb-2 rounded-xl border border-stone-200 border-l-4 border-l-brand-500 bg-[#fffdf8] p-2.5 shadow-sm shadow-stone-200/40">
            <div class="flex gap-2.5">
                <div class="grid size-9 shrink-0 place-items-center rounded-lg bg-stone-100 text-base" aria-hidden="true">☕</div>
                <div class="min-w-0 flex-1">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <h3 class="text-sm font-bold leading-snug">{{ $item['name'] }}</h3>
                            @if($item['modifiers'])
                                <p class="mt-0.5 line-clamp-1 text-xs text-stone-500" title="{{ implode(' · ', $item['modifiers']) }}">{{ implode(' · ', $item['modifiers']) }}</p>
                            @endif
                            @if($item['notes'])
                                <p class="mt-1 line-clamp-1 rounded-lg bg-amber-50 px-2 py-1 text-xs text-amber-800" title="{{ $item['notes'] }}">Catatan: {{ $item['notes'] }}</p>
                            @endif
                        </div>
                        <div class="flex shrink-0 items-center gap-2 rounded-xl border border-stone-200 bg-white p-1 shadow-sm">
                            <button data-item-note-button type="button" wire:click="openItemNote({{ $i }})" class="pressable grid size-9 place-items-center rounded-lg {{ filled($item['notes'] ?? '') ? 'bg-brand-100 text-brand-700' : 'text-stone-500 hover:bg-brand-50 hover:text-brand-700' }}" aria-label="Catatan {{ $item['name'] }}" title="Catatan item"><svg viewBox="0 0 24 24" class="size-4" fill="{{ filled($item['notes'] ?? '') ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="1.8"><path d="M5 4h14v13H9l-4 3V4Z"/><path d="M8 8h8M8 12h5"/></svg></button>
                            <span class="h-6 w-px bg-stone-200" aria-hidden="true"></span>
                            <button data-delete-confirm type="button" @click="$dispatch('ui-alert:open', { type: 'warning', title: 'Hapus item?', message: '{{ addslashes($item['name']) }} akan dihapus dari pesanan saat ini.', confirmLabel: 'Ya, hapus', action: 'remove', actionArgs: [{{ $i }}] })" class="pressable grid size-9 place-items-center rounded-lg text-rose-500 hover:bg-rose-50 hover:text-rose-700" aria-label="Hapus {{ $item['name'] }}" title="Hapus item"><svg viewBox="0 0 24 24" class="size-4" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 7h16M9 7V4h6v3M8 7l1 13h6l1-13"/></svg></button>
                        </div>
                    </div>
                    <div class="mt-2 flex items-center justify-between gap-3">
                        <div x-data="{ optimisticQuantity: {{ $item['quantity'] }}, timer: null, scheduleSync() { clearTimeout(this.timer); this.timer = setTimeout(() => $wire.setQuantity({{ $i }}, this.optimisticQuantity), 350) }, change(delta) { this.optimisticQuantity = Math.max(0, Math.min(99, this.optimisticQuantity + delta)); this.scheduleSync() } }" class="inline-flex items-center overflow-hidden rounded-xl border border-stone-300 bg-white shadow-sm">
                            <button data-qty-control type="button" @click="change(-1)" class="pressable grid size-10 place-items-center border-r border-stone-200 bg-stone-50 text-xl font-bold text-stone-700 transition duration-150 active:scale-90 active:bg-brand-100 hover:bg-brand-50 hover:text-brand-700" aria-label="Kurangi jumlah {{ $item['name'] }}">−</button>
                            <span x-text="optimisticQuantity" class="w-9 text-center text-sm font-black tabular-nums">{{ $item['quantity'] }}</span>
                            <button data-qty-control type="button" @click="change(1)" class="pressable grid size-10 place-items-center border-l border-stone-200 bg-stone-50 text-xl font-bold text-stone-700 transition duration-150 active:scale-90 active:bg-brand-100 hover:bg-brand-50 hover:text-brand-700" aria-label="Tambah jumlah {{ $item['name'] }}">+</button>
                        </div>
                        <p class="text-sm font-black">Rp {{ number_format(($item['unit_price'] + $item['modifier_total']) * $item['quantity'], 0, ',', '.') }}</p>
                    </div>
                </div>
            </div>
        </article>
    @empty
        <div class="grid min-h-64 place-items-center py-12 text-center">
            <div>
                <div class="mx-auto grid size-16 place-items-center rounded-3xl bg-stone-100 text-stone-400">
                    <svg viewBox="0 0 24 24" class="size-8" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M4 7h16l-1.5 12h-13L4 7Z"/><path d="M9 10V6a3 3 0 0 1 6 0v4"/></svg>
                </div>
                <p class="mt-4 font-bold">Belum ada pesanan</p>
                <p class="mt-1 mx-auto max-w-48 text-sm text-stone-500">Pilih menu untuk menambahkannya ke keranjang.</p>
            </div>
        </div>
    @endforelse
</div>

<div class="shrink-0 border-t border-stone-200 bg-white p-3 {{ ($mobile ?? false) ? 'safe-bottom' : '' }}">
    @if($cart)
        <div class="mb-2 grid grid-cols-2 gap-2">
            <label class="block">
                <span class="mb-1.5 block text-xs font-bold text-stone-600">Diskon (%)</span>
                <div class="relative"><input wire:model.live.debounce.300ms="discount" type="number" min="0" max="100" inputmode="numeric" class="pos-field !min-h-10 pr-9 text-right font-bold tabular-nums lg:!min-h-9" placeholder="0"><span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-sm font-bold text-brand-700">%</span></div>
            </label>
            <label class="block">
                <span class="mb-1.5 block text-xs font-bold text-stone-600">Catatan</span>
                <input wire:model="notes" class="pos-field !min-h-10 lg:!min-h-9" placeholder="Opsional">
            </label>
        </div>
    @endif

    <div class="space-y-1 text-sm">
        <div class="flex justify-between text-stone-600"><span>Subtotal</span><span class="font-semibold tabular-nums">Rp {{ number_format($subtotal, 0, ',', '.') }}</span></div>
        @if($discountTotal > 0)
            <div class="flex justify-between text-brand-700"><span>Diskon {{ $discount }}%</span><span class="font-semibold tabular-nums">− Rp {{ number_format($discountTotal, 0, ',', '.') }}</span></div>
        @endif
        @if($taxTotal > 0)
            <div class="flex justify-between text-stone-600"><span>Pajak</span><span class="font-semibold tabular-nums">Rp {{ number_format($taxTotal, 0, ',', '.') }}</span></div>
        @endif
        @if($serviceChargeTotal > 0)
            <div class="flex justify-between text-stone-600"><span>Service charge</span><span class="font-semibold tabular-nums">Rp {{ number_format($serviceChargeTotal, 0, ',', '.') }}</span></div>
        @endif
        <div class="flex justify-between border-t border-dashed border-stone-300 pt-2"><span class="font-black">Total</span><span class="text-lg font-black tabular-nums">Rp {{ number_format($grandTotal, 0, ',', '.') }}</span></div>
    </div>
    <button data-open-payment type="button" wire:click="openPayment" wire:loading.attr="disabled" wire:target="openPayment" class="pressable mt-3 flex min-h-12 w-full items-center justify-between rounded-xl bg-brand-600 px-4 font-bold text-white shadow-sm transition duration-150 active:scale-[0.98] hover:bg-brand-700 disabled:bg-stone-300 lg:min-h-11" @disabled(! $cart)>
        <span class="flex items-center gap-2"><svg wire:loading wire:target="openPayment" viewBox="0 0 24 24" class="size-4 animate-spin" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9" opacity=".25"/><path d="M21 12a9 9 0 0 0-9-9"/></svg><span wire:loading.remove wire:target="openPayment">Bayar Sekarang</span><span wire:loading wire:target="openPayment">Membuka...</span></span><span>Rp {{ number_format($grandTotal, 0, ',', '.') }}</span>
    </button>
    <button type="button" wire:click="holdOrder" class="pressable mt-1 min-h-10 w-full rounded-xl text-sm font-bold text-stone-600 hover:bg-stone-100 disabled:opacity-40 lg:min-h-9" @disabled(! $cart)>Tahan Pesanan</button>
</div>
