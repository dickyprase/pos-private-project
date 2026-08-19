@php
    $productIcons = [
        'coffee' => '☕', 'non-coffee' => '🍵', 'tea' => '🫖',
        'food' => '🥐', 'dessert' => '🍰',
    ];
    $productTones = [
        'coffee' => 'bg-amber-100', 'non-coffee' => 'bg-emerald-100', 'tea' => 'bg-pink-100',
        'food' => 'bg-orange-100', 'dessert' => 'bg-stone-200',
    ];
    $itemCount = collect($cart)->sum('quantity');
@endphp

<div class="flex h-dvh min-h-0 flex-col overflow-hidden bg-stone-100">
    @if(session('success'))
        <div class="pointer-events-none fixed inset-x-4 top-4 z-[100] flex justify-center sm:justify-end" aria-live="polite">
            <div class="pointer-events-auto flex w-full max-w-sm items-center gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-900 shadow-lg">
                <span class="grid size-7 shrink-0 place-items-center rounded-full bg-white/70 font-black">✓</span>
                <span>{{ session('success') }}</span>
            </div>
        </div>
    @endif

    <header class="z-30 shrink-0 border-b border-stone-200 bg-white/95 backdrop-blur">
        <div class="flex min-h-16 items-center gap-3 px-3 py-2 sm:px-5">
            <div class="flex min-w-0 items-center gap-3">
                <a href="{{ route('dashboard') }}" class="grid size-10 shrink-0 place-items-center rounded-2xl bg-brand-600 text-white shadow-sm" aria-label="Kembali ke dashboard">
                    <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M6 8h10v5a5 5 0 0 1-5 5h0a5 5 0 0 1-5-5V8Z"/><path d="M16 10h1.5a2.5 2.5 0 0 1 0 5H16M8 4c0 1 1 1 1 2M12 4c0 1 1 1 1 2"/>
                    </svg>
                </a>
                <div class="min-w-0">
                    <p class="truncate text-sm font-bold sm:text-base">{{ $storeName }} POS</p>
                    <div class="flex items-center gap-1.5 text-xs text-stone-500">
                        <span class="inline-flex size-2 rounded-full {{ $activeShift ? 'bg-emerald-500' : 'bg-red-500' }}"></span>
                        <span>{{ $activeShift ? 'Shift aktif · '.auth()->user()->name : 'Shift belum dibuka' }}</span>
                    </div>
                </div>
            </div>

            <div class="ml-auto flex items-center gap-2">
                <button type="button" wire:click="openHistory" class="pressable hidden min-h-11 rounded-xl border border-stone-200 bg-white px-3 text-sm font-semibold text-stone-600 hover:bg-stone-50 sm:block">Riwayat</button>
                @if($heldOrders->isNotEmpty())
                    <button type="button" wire:click="$toggle('heldOpen')" class="pressable hidden min-h-11 rounded-xl border border-stone-200 bg-white px-3 text-sm font-semibold text-stone-600 hover:bg-stone-50 sm:block">Held ({{ $heldOrders->count() }})</button>
                @endif
                <div data-pos-datetime class="relative hidden rounded-2xl bg-gradient-to-br from-brand-500 via-brand-200 to-white p-[1.5px] shadow-[0_8px_24px_rgba(249,115,22,0.15)] md:block">
                    <div class="rounded-[14px] bg-gradient-to-br from-white via-[#fffaf5] to-brand-50 px-4 py-2 text-right">
                        <p data-pos-clock class="text-base font-black tracking-[0.08em] text-stone-900 tabular-nums">{{ now()->format('H:i:s') }}</p>
                        <p data-pos-date class="mt-0.5 text-[11px] font-semibold text-brand-800">{{ now()->translatedFormat('l, j F Y') }}</p>
                    </div>
                </div>
                <a href="{{ route('profile') }}" class="pressable grid size-11 place-items-center rounded-xl border border-brand-200 bg-brand-50 text-brand-700 hover:bg-brand-100" aria-label="Buka profil akun">
                    <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="8" r="3.5"/><path d="M5 20a7 7 0 0 1 14 0"/></svg>
                </a>
            </div>
        </div>
    </header>

    @if(!$activeShift)
        <main class="grid min-h-0 flex-1 place-items-center p-4">
            <div class="w-full max-w-sm rounded-3xl border border-stone-200 bg-white p-6 text-center shadow-sm">
                <div class="mx-auto grid size-14 place-items-center rounded-2xl bg-amber-100 text-amber-800">
                    <svg viewBox="0 0 24 24" class="size-7" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                </div>
                <h1 class="mt-4 text-xl font-black">Buka shift dulu</h1>
                <p class="mt-1 text-sm text-stone-500">Kasir wajib punya shift aktif sebelum transaksi.</p>
                <a href="{{ route('shifts') }}" class="pressable mt-5 inline-flex min-h-12 items-center justify-center rounded-2xl bg-brand-600 px-5 text-sm font-bold text-white hover:bg-brand-700">Buka Shift</a>
            </div>
        </main>
    @else
        <main class="flex min-h-0 flex-1">
            <section class="flex min-w-0 flex-1 flex-col" aria-label="Katalog menu">
                <div class="shrink-0 space-y-3 border-b border-stone-200 bg-white px-3 py-3 sm:px-5">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                        <label class="relative block min-w-0 flex-1">
                            <span class="sr-only">Cari menu</span>
                            <svg viewBox="0 0 24 24" class="pointer-events-none absolute left-3.5 top-1/2 size-5 -translate-y-1/2 text-stone-400" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="7"/><path d="m16.5 16.5 4 4"/></svg>
                            <input data-product-search wire:model.live.debounce.250ms="search" type="search" autocomplete="off" placeholder="Cari kopi, teh, atau makanan…" class="h-12 w-full rounded-2xl border border-stone-200 bg-stone-50 pl-11 pr-4 text-sm placeholder:text-stone-400 hover:border-stone-300 focus:bg-white">
                        </label>
                        <div class="grid grid-cols-2 gap-2 sm:flex" role="group" aria-label="Jenis pesanan">
                            <button type="button" wire:click="$set('orderType', 'DINE_IN')" class="pressable h-12 rounded-2xl px-4 text-sm font-semibold {{ $orderType === 'DINE_IN' ? 'bg-brand-600 text-white shadow-sm' : 'border border-stone-200 bg-white text-stone-600 hover:bg-brand-50' }}">Dine In</button>
                            <button type="button" wire:click="$set('orderType', 'TAKE_AWAY')" class="pressable h-12 rounded-2xl px-4 text-sm font-semibold {{ $orderType === 'TAKE_AWAY' ? 'bg-brand-600 text-white shadow-sm' : 'border border-stone-200 bg-white text-stone-600 hover:bg-brand-50' }}">Take Away</button>
                        </div>
                    </div>
                    <div class="scrollbar-thin flex gap-2 overflow-x-auto pb-1" aria-label="Kategori menu">
                        <button type="button" wire:click="selectCategory(null)" class="pressable min-h-11 shrink-0 rounded-xl px-4 text-sm font-semibold {{ $activeCategory === null && ! $favoriteOnly ? 'bg-brand-600 text-white shadow-sm' : 'border border-stone-200 bg-white text-stone-600 hover:bg-brand-50' }}">Semua</button>
                        <button type="button" wire:click="selectFavorites" class="pressable min-h-11 shrink-0 rounded-xl px-4 text-sm font-semibold {{ $favoriteOnly ? 'bg-brand-600 text-white shadow-sm' : 'border border-stone-200 bg-white text-stone-600 hover:bg-brand-50' }}">★ Favorit</button>
                        @foreach($categories as $category)
                            <button type="button" wire:key="category-{{ $category->id }}" wire:click="selectCategory({{ $category->id }})" class="pressable min-h-11 shrink-0 rounded-xl px-4 text-sm font-semibold {{ $activeCategory === $category->id && ! $favoriteOnly ? 'bg-brand-600 text-white shadow-sm' : 'border border-stone-200 bg-white text-stone-600 hover:bg-brand-50' }}">{{ $category->name }}</button>
                        @endforeach
                    </div>
                </div>

                <div class="scrollbar-thin min-h-0 flex-1 overflow-y-auto px-3 py-4 sm:px-5">
                    <div class="mb-4 flex items-end justify-between gap-4">
                        <div>
                            <p class="text-lg font-bold tracking-tight">Pilih Menu</p>
                            <p class="text-sm text-stone-500">{{ $this->products->where('is_available', true)->count() }} produk tersedia</p>
                        </div>
                        @if($search !== '' || $activeCategory !== null || $favoriteOnly)
                            <button type="button" wire:click="clearFilters" class="pressable hidden min-h-11 items-center gap-2 rounded-xl border border-stone-200 bg-white px-3 text-sm font-semibold text-stone-600 hover:bg-stone-50 sm:inline-flex">Reset filter</button>
                        @endif
                    </div>

                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5">
                        @forelse($this->products as $product)
                            @php
                                $slug = $product->category->slug;
                                $icon = $productIcons[$slug] ?? '•';
                                $tone = $productTones[$slug] ?? 'bg-stone-100';
                            @endphp
                            <button data-product-card type="button" wire:key="product-{{ $product->id }}" wire:click="quickAdd({{ $product->id }})" class="pressable group relative min-h-40 overflow-hidden rounded-2xl border border-stone-200 bg-white p-2.5 text-left shadow-sm transition duration-150 ease-out active:scale-95 active:border-brand-400 active:ring-4 active:ring-brand-100 hover:-translate-y-0.5 hover:border-stone-300 hover:shadow-md disabled:hover:translate-y-0" @disabled(!$product->is_available)>
                                @if($product->image_path)
                                    <img src="{{ asset('storage/'.$product->image_path) }}" alt="" class="aspect-[4/3] w-full rounded-2xl object-cover">
                                @else
                                    <span class="grid aspect-[4/3] place-items-center rounded-2xl {{ $tone }} text-4xl" aria-hidden="true">{{ $icon }}</span>
                                @endif
                                <span class="mt-3 block min-h-10 text-sm font-bold leading-snug text-stone-800">{{ $product->name }}</span>
                                <span class="mt-1 flex items-center justify-between gap-2">
                                    <span class="text-sm font-black text-stone-900">Rp {{ number_format($product->base_price, 0, ',', '.') }}</span>
                                    @if($product->is_favorite)<span class="text-amber-500" aria-label="Menu favorit">★</span>@endif
                                </span>
                                @if($product->is_available)
                                    <span class="absolute right-5 top-5 grid size-8 translate-y-1 place-items-center rounded-full bg-white/90 text-stone-700 opacity-0 shadow-sm transition group-hover:translate-y-0 group-hover:opacity-100">+</span>
                                @else
                                    <span class="absolute inset-0 grid place-items-center bg-white/70"><span class="rounded-full bg-rose-600 px-3 py-1.5 text-xs font-bold text-white">Habis</span></span>
                                @endif
                            </button>
                        @empty
                            <div class="col-span-full grid min-h-64 place-items-center rounded-3xl border border-dashed border-stone-300 bg-white p-6 text-center">
                                <div>
                                    <div class="mx-auto mb-3 grid size-12 place-items-center rounded-2xl bg-stone-100 text-stone-500">
                                        <svg viewBox="0 0 24 24" class="size-6" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="7"/><path d="m16.5 16.5 4 4M8.5 11h5"/></svg>
                                    </div>
                                    <p class="font-bold">Menu tidak ditemukan</p>
                                    <p class="mt-1 text-sm text-stone-500">Coba kata kunci atau kategori lain.</p>
                                </div>
                            </div>
                        @endforelse
                    </div>
                </div>
            </section>

            <aside class="hidden w-[360px] shrink-0 border-l border-stone-300 bg-[#fafafa] lg:flex lg:flex-col xl:w-[380px]" aria-label="Keranjang pesanan">
                @include('livewire.pos.partials.cart', ['mobile' => false, 'mount' => 'desktop'])
            </aside>
        </main>

        <div class="safe-bottom z-30 shrink-0 border-t border-stone-200 bg-white p-3 lg:hidden">
            @if($cart)
                <button type="button" wire:click="$set('cartOpen', true)" class="pressable flex min-h-14 w-full items-center justify-between rounded-2xl bg-brand-600 px-4 text-white shadow-lg hover:bg-brand-700">
                    <span class="flex items-center gap-3"><span class="grid size-9 place-items-center rounded-xl bg-white/10 text-sm font-black">{{ $itemCount }}</span><span class="text-left"><span class="block text-xs text-stone-300">Lihat pesanan</span><span class="block text-sm font-bold">Rp {{ number_format($grandTotal, 0, ',', '.') }}</span></span></span>
                    <span class="flex items-center gap-2 text-sm font-bold">Buka <svg viewBox="0 0 24 24" class="size-4" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg></span>
                </button>
            @else
                <div class="flex min-h-14 items-center justify-between rounded-2xl border border-stone-200 bg-stone-50 px-4"><span class="text-sm font-semibold text-stone-500">Keranjang masih kosong</span><span class="text-xs text-stone-400">Pilih menu di atas</span></div>
            @endif
        </div>
    @endif

    @if($cartOpen)
        <div class="fixed inset-0 z-50 bg-stone-950/40 backdrop-blur-[2px] lg:hidden">
            <button type="button" wire:click="$set('cartOpen', false)" class="absolute inset-0 size-full" aria-label="Tutup keranjang"></button>
            <section class="absolute inset-x-0 bottom-0 flex max-h-[96dvh] flex-col overflow-hidden rounded-t-3xl bg-[#fafafa] shadow-2xl" role="dialog" aria-modal="true" aria-labelledby="mobileCartTitle">
                <div class="flex justify-center bg-[#f7f7f5] py-1.5" aria-hidden="true"><span class="h-1 w-10 rounded-full bg-stone-300"></span></div>
                @include('livewire.pos.partials.cart', ['mobile' => true, 'mount' => 'mobile'])
            </section>
        </div>
    @endif

    @if($heldOpen)
        <div class="fixed inset-0 z-[55] grid place-items-center bg-stone-950/40 p-3 backdrop-blur-[2px]">
            <button type="button" wire:click="$set('heldOpen', false)" class="absolute inset-0 size-full" aria-label="Tutup pesanan tertahan"></button>
            <section class="relative w-full max-w-md rounded-[28px] bg-white p-5 shadow-2xl">
                <div class="flex items-center justify-between"><div><h2 class="font-black">Pesanan Ditahan</h2><p class="text-sm text-stone-500">Pilih pesanan untuk dilanjutkan.</p></div><x-ui.modal-close wire:click="$set('heldOpen', false)" label="Tutup pesanan ditahan" /></div>
                <div class="mt-4 space-y-2">
                    @forelse($heldOrders as $token => $held)
                        <button type="button" wire:click="resumeHeld('{{ $token }}')" class="pressable flex min-h-14 w-full items-center justify-between rounded-2xl border border-stone-200 px-4 text-left hover:border-brand-400"><span><b class="block text-sm">{{ $held['label'] ?? 'Held' }}</b><small class="text-stone-500">{{ count($held['cart'] ?? []) }} baris item</small></span><span class="text-sm font-bold text-brand-700">Lanjut</span></button>
                    @empty
                        <p class="py-8 text-center text-sm text-stone-500">Tidak ada pesanan tertahan.</p>
                    @endforelse
                </div>
            </section>
        </div>
    @endif

    @if($modifierOpen)
        <div class="fixed inset-0 z-[60] grid place-items-end bg-stone-950/40 backdrop-blur-[2px] sm:place-items-center sm:p-3">
            <button data-close-modal type="button" wire:click="$set('modifierOpen', false)" class="absolute inset-0 size-full" aria-label="Tutup pengaturan item"></button>
            <section class="relative flex max-h-[90dvh] w-full max-w-lg flex-col rounded-t-[28px] bg-white shadow-2xl sm:rounded-[28px]" role="dialog" aria-modal="true">
                <div class="flex items-start gap-3 border-b border-stone-200 p-5">
                    <div class="grid size-12 shrink-0 place-items-center rounded-2xl bg-brand-100 text-2xl">☕</div>
                    <div class="min-w-0 flex-1"><h2 class="font-bold">{{ $modifierProductName }}</h2><p class="mt-0.5 text-sm text-stone-500">Pilih preferensi pesanan.</p></div>
                    <x-ui.modal-close wire:click="$set('modifierOpen', false)" label="Tutup pengaturan item" />
                </div>
                <div class="scrollbar-thin min-h-0 space-y-5 overflow-y-auto p-5">
                    @foreach($modifierGroups as $group)
                        <fieldset>
                            <div class="mb-2 flex items-center justify-between"><legend class="text-sm font-bold">{{ $group['name'] }}</legend><span class="text-xs text-stone-500">{{ $group['required'] ? 'Wajib' : 'Opsional' }} · max {{ $group['max'] }}</span></div>
                            <div class="grid grid-cols-2 gap-2">
                                @foreach($group['options'] as $option)
                                    <button type="button" wire:click="selectModifier({{ $group['id'] }}, {{ $option['id'] }})" class="pressable flex min-h-12 items-center justify-between rounded-2xl border px-4 text-sm {{ in_array($option['id'], $selectedModifiers[$group['id']] ?? [], true) ? 'border-brand-500 bg-brand-50 text-brand-800' : 'border-stone-200' }}"><span class="font-semibold">{{ $option['name'] }}</span><span>{{ $option['price'] ? '+'.number_format($option['price'] / 1000).'k' : '+0' }}</span></button>
                                @endforeach
                            </div>
                        </fieldset>
                    @endforeach
                    <label class="block"><span class="mb-2 block text-sm font-bold">Catatan item <span class="font-normal text-stone-400">(opsional)</span></span><textarea wire:model="itemNotes" rows="2" maxlength="80" placeholder="Contoh: es sedikit, cup terpisah…" class="w-full resize-none rounded-2xl border border-stone-200 bg-stone-50 px-4 py-3 text-sm focus:bg-white"></textarea></label>
                    @error('modifierSelection')<p class="text-sm font-semibold text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="border-t border-stone-200 p-4 sm:p-5"><button type="button" wire:click="addConfiguredItem" class="pressable flex min-h-13 w-full items-center justify-between rounded-2xl bg-brand-600 px-5 font-bold text-white shadow-sm hover:bg-brand-700"><span>Tambah ke pesanan</span><span>Rp {{ number_format($this->modifierPrice, 0, ',', '.') }}</span></button></div>
            </section>
        </div>
    @endif

    @if($paymentOpen)
        @php
            $roundedTen = (int) ceil($grandTotal / 10000) * 10000;
            $roundedFifty = (int) ceil($grandTotal / 50000) * 50000;
            $cashOptions = array_unique([$grandTotal, $roundedTen, $roundedFifty]);
        @endphp
        <div x-data="{ shown: false, close() { this.shown = false; setTimeout(() => $wire.set('paymentOpen', false), 180) } }" x-init="$nextTick(() => shown = true)" data-payment-backdrop class="fixed inset-0 z-[60] grid place-items-end sm:place-items-center sm:p-3">
            <div x-show="shown" x-transition.opacity.duration.200ms class="absolute inset-0 bg-stone-950/40 backdrop-blur-[2px]"></div>
            <button data-close-modal type="button" @click="close()" class="absolute inset-0 size-full" aria-label="Tutup pembayaran"></button>
            <section data-payment-dialog x-show="shown" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95 translate-y-3" x-transition:enter-end="opacity-100 scale-100 translate-y-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95 translate-y-2" class="relative flex max-h-[92dvh] w-full max-w-xl flex-col overflow-hidden rounded-t-[28px] bg-[#fafafa] shadow-2xl sm:rounded-[28px]" role="dialog" aria-modal="true" aria-labelledby="paymentTitle">
                <div class="flex items-start gap-3 border-b-2 border-brand-500 bg-[#f7f7f5] p-5">
                    <div class="grid size-12 shrink-0 place-items-center rounded-2xl bg-brand-100 text-brand-700"><svg viewBox="0 0 24 24" class="size-6" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="6" width="18" height="13" rx="2"/><path d="M3 10h18M7 15h3"/></svg></div>
                    <div class="min-w-0 flex-1"><h2 id="paymentTitle" class="font-bold">Pembayaran</h2><p class="mt-0.5 text-sm text-stone-500">Periksa total sebelum menyelesaikan transaksi.</p></div>
                    <x-ui.modal-close @click="close()" label="Tutup pembayaran" />
                </div>
                <div class="scrollbar-thin min-h-0 space-y-5 overflow-y-auto p-5">
                    <div class="rounded-3xl border border-stone-200 border-l-4 border-l-brand-500 bg-[#fffdf8] p-5 text-stone-900 shadow-sm"><p class="text-sm font-semibold text-stone-500">Total pembayaran</p><p class="mt-1 text-3xl font-black tracking-tight">Rp {{ number_format($grandTotal, 0, ',', '.') }}</p><div class="mt-4 flex items-center justify-between border-t border-stone-200 pt-3 text-sm"><span class="text-stone-500">Pesanan</span><span class="font-semibold">{{ $orderType === 'DINE_IN' ? 'Dine In' : 'Take Away' }}</span></div></div>
                    <fieldset><legend class="mb-2 text-sm font-bold">Metode pembayaran</legend><div class="grid {{ $qrisEnabled ? 'grid-cols-2' : 'grid-cols-1' }} gap-2">
                        @foreach(array_filter(['CASH' => ['💵','Cash'], 'QRIS' => $qrisEnabled ? ['▦','QRIS'] : null]) as $value => [$icon, $label])
                            <button type="button" wire:click="$set('paymentMethod', '{{ $value }}')" class="pressable grid min-h-16 place-items-center rounded-2xl border p-2 text-center text-xs font-semibold {{ $paymentMethod === $value ? 'border-brand-500 bg-brand-50 text-brand-800' : 'border-stone-200' }}"><span>{{ $icon }}</span><span>{{ $label }}</span></button>
                        @endforeach
                    </div></fieldset>
                    @if($paymentMethod === 'CASH')
                        <div class="space-y-3" x-data="currencyInput($wire.entangle('receivedAmount').live, {{ $grandTotal }})">
                            <label class="block"><span class="mb-2 block text-sm font-bold">Uang diterima</span><div class="relative"><span class="absolute left-4 top-1/2 -translate-y-1/2 text-sm font-semibold text-stone-500">Rp</span><input x-model="display" @input="update($event)" wire:ignore type="text" inputmode="numeric" autocomplete="off" class="h-14 w-full rounded-2xl border border-stone-200 bg-stone-50 pl-11 pr-4 text-right text-xl font-bold tabular-nums focus:bg-white"></div></label>
                            <div class="grid grid-cols-3 gap-2">@foreach($cashOptions as $cash)<button type="button" wire:click="$set('receivedAmount', {{ $cash }})" class="pressable min-h-11 rounded-xl border border-stone-200 bg-white px-2 text-xs font-bold hover:bg-stone-50">{{ $cash === $grandTotal ? 'Uang pas' : number_format($cash, 0, ',', '.') }}</button>@endforeach</div>
                            <div class="flex items-center justify-between rounded-2xl bg-emerald-50 px-4 py-3"><span class="text-sm font-semibold text-emerald-800">Kembalian</span><span class="font-black text-emerald-800">Rp {{ number_format(max(0, $receivedAmount - $grandTotal), 0, ',', '.') }}</span></div>
                        </div>
                    @elseif($paymentMethod === 'QRIS')
                        <div class="space-y-3 text-center"><div class="rounded-2xl border border-brand-200 bg-brand-50 p-3 text-sm text-brand-800">Scan QRIS, lalu pastikan pembayaran sudah diterima sebelum menyelesaikan transaksi.</div>@if($qrisImagePath)<img src="{{ asset('storage/'.$qrisImagePath) }}" class="mx-auto size-64 rounded-3xl border border-brand-200 bg-white object-contain p-3 shadow-sm" alt="QRIS {{ $storeName }}">@else<div class="rounded-2xl border border-dashed border-rose-300 bg-rose-50 p-5 text-sm font-semibold text-rose-700">Gambar QRIS belum diunggah owner.</div>@endif</div>
                    @endif
                    @error('payment.received_amount')<p class="text-sm font-semibold text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="border-t border-stone-200 bg-white p-4 sm:p-5"><button type="button" wire:click="completePayment" wire:loading.attr="disabled" wire:target="completePayment" class="pressable flex min-h-14 w-full items-center justify-center gap-2 rounded-2xl bg-brand-600 px-5 font-bold text-white shadow-sm hover:bg-brand-700 disabled:opacity-60"><svg wire:loading wire:target="completePayment" viewBox="0 0 24 24" class="size-5 animate-spin" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9" opacity=".25"/><path d="M21 12a9 9 0 0 0-9-9"/></svg><span wire:loading.remove wire:target="completePayment">Selesaikan Pembayaran</span><span wire:loading wire:target="completePayment">Menyimpan transaksi…</span></button></div>
            </section>
        </div>
    @endif

    @if($itemNoteOpen)
        <div x-data="{ shown: false, close() { this.shown = false; setTimeout(() => $wire.set('itemNoteOpen', false), 180) } }" x-init="$nextTick(() => shown = true)" class="fixed inset-0 z-[65] grid place-items-end sm:place-items-center sm:p-3">
            <div x-show="shown" x-transition.opacity.duration.200ms class="absolute inset-0 bg-stone-950/40 backdrop-blur-[2px]"></div>
            <button data-close-modal type="button" @click="close()" class="absolute inset-0 size-full" aria-label="Tutup catatan item"></button>
            <section x-show="shown" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95 translate-y-3" x-transition:enter-end="opacity-100 scale-100 translate-y-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95 translate-y-2" class="relative w-full max-w-lg overflow-hidden rounded-t-[28px] border-l-4 border-l-brand-500 bg-[#fafafa] shadow-2xl sm:rounded-[28px]" role="dialog" aria-modal="true" aria-labelledby="itemNoteTitle">
                <div class="flex items-start gap-3 border-b-2 border-brand-500 bg-[#f7f7f5] p-5">
                    <div class="grid size-12 shrink-0 place-items-center rounded-2xl bg-brand-100 text-brand-700"><svg viewBox="0 0 24 24" class="size-6" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M5 4h14v13H9l-4 3V4Z"/><path d="M8 8h8M8 12h5"/></svg></div>
                    <div class="min-w-0 flex-1"><h2 id="itemNoteTitle" class="font-bold">Catatan untuk {{ $editingItemName }}</h2><p class="mt-0.5 text-sm text-stone-500">Instruksi khusus untuk barista atau dapur.</p></div>
                    <x-ui.modal-close @click="close()" label="Tutup catatan item" />
                </div>
                <form wire:submit="saveItemNote">
                    <div class="p-5"><label class="block"><span class="mb-2 block text-sm font-bold">Catatan item</span><textarea wire:model="editingItemNote" rows="4" maxlength="180" autofocus class="pos-field min-h-28 resize-none py-3" placeholder="Contoh: less sugar, no ice, susu terpisah…"></textarea></label>@error('editingItemNote')<p class="mt-2 text-sm font-semibold text-rose-600">{{ $message }}</p>@enderror</div>
                    <div class="grid grid-cols-2 gap-2 border-t border-stone-200 bg-white p-4"><button type="button" @click="close()" class="btn-secondary min-h-11 rounded-xl">Batal</button><button class="btn-primary min-h-11 rounded-xl">Simpan Catatan</button></div>
                </form>
            </section>
        </div>
    @endif

    @if($successOpen)
        <div class="fixed inset-0 z-[70] grid place-items-center bg-stone-950/40 p-3 backdrop-blur-[2px]" x-data @keydown.escape.window="$wire.set('successOpen', false)">
            <button type="button" wire:click="$set('successOpen', false)" class="absolute inset-0 size-full" aria-label="Tutup hasil pembayaran"></button>
            <section class="w-full max-w-md rounded-[28px] bg-white p-6 text-center shadow-2xl sm:p-8" role="dialog" aria-modal="true">
                <div class="mx-auto grid size-16 place-items-center rounded-full bg-emerald-100 text-emerald-700"><svg viewBox="0 0 24 24" class="size-8" fill="none" stroke="currentColor" stroke-width="2"><path d="m5 12 4 4L19 6"/></svg></div>
                <h2 class="mt-4 text-xl font-black">Pembayaran Berhasil</h2><p class="mt-1 text-sm text-stone-500">Transaksi #{{ $lastOrderNumber }} telah tersimpan.</p>
                <div class="mt-5 rounded-2xl bg-stone-100 p-4"><p class="text-xs font-semibold uppercase tracking-wide text-stone-500">Total</p><p class="mt-1 text-2xl font-black">Rp {{ number_format($lastOrderTotal, 0, ',', '.') }}</p></div>
                <div class="relative z-10 mt-5 grid grid-cols-1 gap-2"><button type="button" wire:click="printBrowserReceipt" class="pressable min-h-12 rounded-2xl border border-stone-200 px-4 text-sm font-bold text-stone-700 hover:bg-stone-50">Cetak Struk</button><button type="button" wire:click="printAndStartNewOrder" class="pressable min-h-12 rounded-2xl bg-brand-600 px-4 text-sm font-bold text-white hover:bg-brand-700">Cetak Struk & Pesanan Baru</button><button type="button" wire:click="startNewOrder" class="pressable min-h-12 rounded-2xl bg-stone-100 px-4 text-sm font-bold text-stone-700 hover:bg-stone-200">Pesanan Baru</button></div>
            </section>
        </div>
    @endif

    @if($historyOpen)
        <div class="fixed inset-0 z-[68] grid place-items-center bg-stone-950/40 p-3 backdrop-blur-[2px]">
            <button type="button" wire:click="closeHistory" class="absolute inset-0 size-full" aria-label="Tutup riwayat"></button>
            <section class="relative z-10 flex max-h-[90dvh] w-full max-w-3xl flex-col overflow-hidden rounded-[28px] bg-white shadow-2xl" role="dialog" aria-modal="true">
                <div class="flex items-center justify-between border-b border-stone-200 p-5"><div><p class="text-xs font-semibold uppercase tracking-wide text-stone-500">Kasir</p><h2 class="text-xl font-black">Riwayat Transaksi</h2></div><x-ui.modal-close wire:click="closeHistory" label="Tutup riwayat" /></div>
                <div class="scrollbar-thin min-h-0 flex-1 overflow-y-auto p-4"><div class="space-y-2">
                    @forelse($this->recentOrders as $recent)
                        <div class="flex items-center gap-3 rounded-2xl border border-stone-200 bg-stone-50 p-3"><div class="min-w-0 flex-1"><p class="font-bold">{{ $recent->order_number }}</p><p class="text-xs text-stone-500">{{ $recent->created_at->format('d M Y H:i') }} · {{ $recent->payment?->method->value ?? '-' }}</p></div><p class="font-black">Rp {{ number_format($recent->grand_total, 0, ',', '.') }}</p><button type="button" wire:click="openHistoryDetail({{ $recent->id }})" class="pressable rounded-xl border border-stone-200 bg-white px-3 py-2 text-xs font-bold text-brand-700">Detail</button><a target="_blank" href="{{ route('orders.receipt', $recent) }}" class="pressable rounded-xl border border-stone-200 bg-white px-3 py-2 text-xs font-bold text-stone-700">Print</a></div>
                    @empty <p class="py-10 text-center text-sm text-stone-500">Belum ada transaksi.</p> @endforelse
                </div></div>
            </section>
        </div>
    @endif

    @if($historyDetailId)
        @php($historyDetail = \App\Models\Order::with('items.modifiers', 'payment')->find($historyDetailId))
        @if($historyDetail)
            <div class="fixed inset-0 z-[69] grid place-items-center bg-stone-950/40 p-3 backdrop-blur-[2px]"><button type="button" wire:click="$set('historyDetailId', null)" class="absolute inset-0 size-full" aria-label="Tutup detail"></button><section class="relative z-10 w-full max-w-lg rounded-[28px] bg-white p-5 shadow-2xl"><div class="flex items-start justify-between"><div><p class="text-xs text-stone-500">Detail transaksi</p><h2 class="text-xl font-black">{{ $historyDetail->order_number }}</h2></div><x-ui.modal-close wire:click="$set('historyDetailId', null)" label="Tutup detail" /></div><div class="mt-4 space-y-2">@foreach($historyDetail->items as $item)<div class="flex justify-between gap-3 text-sm"><span>{{ $item->quantity }}x {{ $item->product_name_snapshot }}</span><b>Rp {{ number_format($item->line_total, 0, ',', '.') }}</b></div>@endforeach</div><div class="mt-5 flex items-center justify-between border-t border-stone-200 pt-4"><b>Total</b><b>Rp {{ number_format($historyDetail->grand_total, 0, ',', '.') }}</b></div><a target="_blank" href="{{ route('orders.receipt', $historyDetail) }}" class="pressable mt-5 flex min-h-12 items-center justify-center rounded-2xl bg-brand-600 font-bold text-white">Cetak Struk</a></section></div>
        @endif
    @endif
    <x-ui.alert-modal />
</div>
