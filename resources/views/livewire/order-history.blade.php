<div class="space-y-3">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs text-stone-500">Riwayat order</p>
            <h2 class="text-xl font-bold">Transaksi</h2>
        </div>
        <div class="flex flex-wrap gap-2">
            <input wire:model.live.debounce.300ms="search" class="field-input !w-48" placeholder="Cari nomor order, meja, nama, kasir, metode">
            <div class="w-44"><x-ui.dropdown model="status" :options="[['value'=>'','label'=>'Semua status'],['value'=>'COMPLETED','label'=>'Selesai'],['value'=>'HELD','label'=>'Ditahan'],['value'=>'CANCELLED','label'=>'Dibatalkan'],['value'=>'REFUNDED','label'=>'Direfund']]" /></div>
        </div>
    </div>

    <div class="table-shell overflow-x-auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Order</th>
                    <th>Waktu</th>
                    <th>Kasir</th>
                    <th>Meja / Atas nama</th>
                    <th>Metode</th>
                    <th>Status</th>
                    <th class="text-right">Total</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                    <tr wire:key="order-{{ $order->id }}">
                        <td class="font-semibold">{{ $order->order_number }}</td>
                        <td>{{ $order->created_at->format('d M Y H:i') }}</td>
                        <td>{{ $order->cashier?->name }}</td>
                        <td>
                            <span class="font-semibold">{{ $order->table_number ?: '-' }}</span>
                            <span class="mt-0.5 block text-xs text-stone-500">{{ $order->customer_name ?: '-' }}</span>
                        </td>
                        <td>{{ $order->payment?->method->value ?? '-' }}</td>
                        <td>
                            <span @class="inline-flex rounded-full px-2 py-0.5 text-[11px] font-semibold {{ $order->status->value === 'COMPLETED' ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }}">{{ $order->status->value }}</span>
                        </td>
                        <td class="text-right font-bold">Rp {{ number_format($order->grand_total, 0, ',', '.') }}</td>
                        <td>
                            <div class="table-actions">
                                <a target="_blank" href="{{ route('orders.receipt', $order) }}" class="table-action-button text-brand-700">Struk</a>
                                @if($canManage && $order->status->value === 'COMPLETED')
                                    <button wire:click="openAction({{ $order->id }}, 'refund')" class="table-action-button">Refund</button>
                                    <button wire:click="openAction({{ $order->id }}, 'void')" class="table-action-button text-rose-700 hover:bg-rose-50">Void</button>
                                @endif
                                <button wire:click="openDetail({{ $order->id }})" class="table-action-button">Detail</button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="py-10 text-center text-xs text-stone-400">Belum ada transaksi.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $orders->links() }}

    @if($actionOrderId)
        <!-- refund/void modal tetap -->
    @endif

    @if($detailOrderId)
        <div class="fixed inset-0 z-50 grid place-items-end bg-stone-950/35 sm:place-items-center sm:p-4">
            <div class="w-full max-w-2xl rounded-t-lg bg-white p-4 sm:rounded-lg">
                <div class="mb-3 flex items-start justify-between">
                    <div><p class="text-[10px] font-bold uppercase tracking-wide text-stone-500">Detail Order</p><h3 class="text-base font-bold">{{ $detailOrderNumber }}</h3></div>
                    <x-ui.modal-close wire:click="closeDetail" label="Tutup detail order" />
                </div>
                <div class="max-h-[60vh] overflow-y-auto">
                    <div class="space-y-4">
                        <div class="flex justify-between text-sm"><span class="text-stone-500">Meja</span><b>{{ $detailOrder->table_number ?: '-' }}</b></div>
                        <div class="flex justify-between text-sm"><span class="text-stone-500">Atas nama</span><b>{{ $detailOrder->customer_name ?: '-' }}</b></div>
                        <div class="flex justify-between text-sm"><span class="text-stone-500">Kasir</span><b>{{ $detailOrder->cashier?->name }}</b></div>
                        <div class="flex justify-between text-sm"><span class="text-stone-500">Metode</span><b>{{ $detailOrder->payment?->method->value }}</b></div>
                        <div class="flex justify-between text-sm"><span class="text-stone-500">Total</span><b>Rp {{ number_format($detailOrder->grand_total, 0, ',', '.') }}</b></div>
                    </div>
                    <div class="mt-6 border-t border-stone-200 pt-4">
                        <h4 class="text-sm font-bold mb-3">Item Pesanan</h4>
                        <div class="divide-y divide-stone-100">
                            @foreach($detailOrder->items as $item)
                                <div class="py-2 flex justify-between gap-4 text-sm">
                                    <div>
                                        <b>{{ $item->quantity }}x {{ $item->product_name_snapshot }}</b>
                                        @if($item->variant_name_snapshot) <span class="text-xs text-stone-500">+ {{ $item->variant_name_snapshot }}</span>@endif
                                        @foreach($item->modifiers as $mod)<span class="text-xs text-stone-500 ml-2">+ {{ $mod->name_snapshot }} (Rp {{ number_format($mod->price_adjustment) }})</span>@endforeach
                                        @if($item->notes) <span class="text-xs text-stone-500 ml-2">Catatan: {{ $item->notes }}</span>@endif
                                    </div>
                                    <div class="text-right font-medium">Rp {{ number_format($item->line_total) }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
