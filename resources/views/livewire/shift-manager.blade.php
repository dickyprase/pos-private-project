<div class="mx-auto max-w-4xl space-y-4">
    <div>
        <p class="text-xs text-stone-500">Cash management</p>
        <h2 class="text-xl font-bold tracking-tight">Shift Kasir</h2>
    </div>

    @if(! $activeShift)
        <section class="card mx-auto max-w-md p-4">
            <div class="mb-3 grid size-10 place-items-center rounded-md bg-amber-100 text-sm font-black text-amber-800">S</div>
            <h3 class="text-base font-bold">Buka shift</h3>
            <p class="mt-1 text-xs text-stone-500">Masukkan modal kas awal di laci.</p>
            <form wire:submit="openShift" class="mt-4 space-y-3">
                <label class="field-label">Modal awal
                    <div x-data="currencyInput($wire.entangle('openingCash').live)" class="relative mt-1">
                        <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm font-semibold text-stone-500">Rp</span>
                        <input x-model="display" @input="update($event)" type="text" inputmode="numeric" autocomplete="off" class="field-input pl-10 text-right font-bold tabular-nums">
                    </div>
                </label>
                @error('openingCash') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
                <button class="btn-primary w-full" wire:loading.attr="disabled">Buka Shift</button>
            </form>
        </section>
    @else
        <section class="grid gap-2 sm:grid-cols-3">
            <div class="card p-3">
                <p class="text-[10px] font-semibold uppercase tracking-wide text-stone-500">Status</p>
                <p class="mt-1 text-lg font-bold text-emerald-700">OPEN</p>
            </div>
            <div class="card p-3">
                <p class="text-[10px] font-semibold uppercase tracking-wide text-stone-500">Mulai</p>
                <p class="mt-1 text-lg font-bold">{{ $activeShift->opened_at->format('H:i') }}</p>
                <span class="text-[11px] text-stone-500">{{ $activeShift->opened_at->format('d M Y') }}</span>
            </div>
            <div class="card p-3">
                <p class="text-[10px] font-semibold uppercase tracking-wide text-stone-500">Modal awal</p>
                <p class="mt-1 text-lg font-bold">Rp {{ number_format($activeShift->opening_cash, 0, ',', '.') }}</p>
            </div>
        </section>

        <section class="grid gap-3 lg:grid-cols-2">
            <div class="card p-4">
                <h3 class="text-sm font-bold">Cash in / out</h3>
                <form wire:submit="addMovement" class="mt-3 grid gap-2">
                    <x-ui.dropdown model="movementType" :options="[
                        ['value' => 'CASH_IN', 'label' => 'Cash In'],
                        ['value' => 'CASH_OUT', 'label' => 'Cash Out'],
                    ]" />
                    <div x-data="currencyInput($wire.entangle('movementAmount').live)" class="relative"><span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm font-semibold text-stone-500">Rp</span><input x-model="display" @input="update($event)" type="text" inputmode="numeric" autocomplete="off" class="field-input pl-10 text-right font-bold tabular-nums" placeholder="Jumlah"></div>
                    <input wire:model="movementReason" class="field-input" placeholder="Alasan">
                    <button class="btn-secondary">Catat</button>
                </form>
                <div class="mt-3 space-y-1.5">
                    @foreach($movements as $movement)
                        <div class="flex justify-between rounded-md bg-stone-50 px-2.5 py-2 text-xs">
                            <span>{{ $movement->reason }}</span>
                            <b class="{{ $movement->type === 'CASH_IN' ? 'text-emerald-700' : 'text-rose-700' }}">
                                {{ $movement->type === 'CASH_IN' ? '+' : '-' }} Rp {{ number_format($movement->amount, 0, ',', '.') }}
                            </b>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="card p-4">
                <h3 class="text-sm font-bold">Tutup shift</h3>
                <p class="mt-1 text-xs text-stone-500">Masukkan kas fisik aktual.</p>
                <form wire:submit="closeShift" class="mt-3 space-y-2">
                    <label class="field-label">Kas aktual
                        <div x-data="currencyInput($wire.entangle('actualCash').live)" class="relative mt-1"><span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm font-semibold text-stone-500">Rp</span><input x-model="display" @input="update($event)" type="text" inputmode="numeric" autocomplete="off" class="field-input pl-10 text-right font-bold tabular-nums"></div>
                    </label>
                    <textarea wire:model="notes" class="field-input min-h-20 py-2" placeholder="Catatan selisih (opsional)"></textarea>
                    <button type="button" @click="$dispatch('ui-alert:open', { type: 'warning', title: 'Tutup shift sekarang?', message: 'Pastikan kas aktual dan catatan selisih sudah benar sebelum melanjutkan.', confirmLabel: 'Ya, tutup shift', action: 'closeShift' })" class="btn-danger w-full">Tutup Shift</button>
                </form>
            </div>
        </section>
    @endif
    <x-ui.alert-modal />
</div>
