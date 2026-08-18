<div class="space-y-3">
    <div class="rounded-3xl bg-gradient-to-r from-brand-600 via-brand-500 to-brand-300 p-[1px] shadow-lg shadow-brand-100"><div class="rounded-[23px] bg-white px-5 py-4"><p class="text-xs font-bold uppercase tracking-[.16em] text-brand-700">Ledger bahan</p><h2 class="mt-1 text-xl font-black">Inventori</h2><p class="mt-1 text-sm text-stone-500">Pantau stok dan catat setiap movement bahan.</p></div></div>

    <div class="grid gap-3 xl:grid-cols-[1.35fr_.65fr]">
        <section>
            <div class="mb-3 grid gap-2 sm:grid-cols-[1fr_190px]"><input wire:model.live.debounce.300ms="search" class="field-input" placeholder="Cari bahan / SKU"><x-ui.dropdown model="stockFilter" :options="[['value'=>'','label'=>'Semua stok'],['value'=>'low','label'=>'Stok rendah'],['value'=>'safe','label'=>'Stok aman']]" /></div>
            <div class="table-shell overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Bahan</th>
                            <th>Satuan</th>
                            <th class="text-right">Stok</th>
                            <th class="text-right">Min</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($items as $item)
                            <tr>
                                <td>
                                    <b class="text-sm">{{ $item->name }}</b><br>
                                    <small class="text-stone-400">{{ $item->sku }}</small>
                                </td>
                                <td>{{ $item->unit->symbol }}</td>
                                <td class="text-right font-bold">{{ rtrim(rtrim($item->current_stock, '0'), '.') }}</td>
                                <td class="text-right">{{ rtrim(rtrim($item->minimum_stock, '0'), '.') }}</td>
                                <td>
                                    <span class="inline-flex rounded-full px-2 py-0.5 text-[11px] font-semibold {{ $item->current_stock <= $item->minimum_stock ? 'bg-rose-50 text-rose-700' : 'bg-emerald-50 text-emerald-700' }}">
                                        {{ $item->current_stock <= $item->minimum_stock ? 'Low' : 'Aman' }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div data-pagination class="mt-2">{{ $items->links() }}</div>
        </section>

        <aside class="space-y-3">
            <div class="card p-4">
                <h3 class="text-sm font-bold">Catat movement</h3>
                <form wire:submit="adjust" class="mt-3 space-y-2">
                    <x-ui.dropdown model="selectedId" :options="$adjustmentItems->map(fn ($item) => ['value' => $item->id, 'label' => $item->name])->values()->all()" placeholder="Pilih bahan" />
                    <x-ui.dropdown model="movementType" :options="[
                        ['value' => 'PURCHASE', 'label' => 'Pembelian'],
                        ['value' => 'ADJUSTMENT', 'label' => 'Penyesuaian'],
                        ['value' => 'WASTE', 'label' => 'Terbuang'],
                        ['value' => 'RETURN', 'label' => 'Retur'],
                        ['value' => 'OPNAME', 'label' => 'Stok opname'],
                    ]" />
                    <input wire:model="quantity" type="number" step="0.0001" class="field-input" placeholder="Quantity">
                    <textarea wire:model="notes" class="field-input min-h-16 py-2" placeholder="Alasan"></textarea>
                    <button class="btn-primary w-full">Simpan</button>
                </form>
            </div>
            <div class="card p-4">
                <h3 class="text-sm font-bold">Movement terbaru</h3>
                <div class="mt-2 space-y-1.5">
                    @foreach($movements as $movement)
                        <div class="flex justify-between rounded-md bg-stone-50 px-2.5 py-2 text-xs">
                            <span>{{ $movement->inventoryItem?->name }} · {{ $movement->type->value }}</span>
                            <b>{{ rtrim(rtrim($movement->quantity, '0'), '.') }}</b>
                        </div>
                    @endforeach
                </div>
            </div>
        </aside>
    </div>
</div>
