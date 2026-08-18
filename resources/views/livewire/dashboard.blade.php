@php
    $formatMoney = fn (int|float $value) => 'Rp'.number_format($value, 0, ',', '.');
    $formatChange = fn (float $value) => ($value >= 0 ? '+' : '').number_format($value, 1, ',', '.').'%';
    $roleOwner = $isOwner;
    $kpis = [
        ['label' => 'Penjualan Bersih', 'value' => $formatMoney($revenue), 'change' => $revenueChange, 'icon' => 'wallet'],
        ['label' => 'Total Transaksi', 'value' => number_format($transactions, 0, ',', '.'), 'change' => $transactionChange, 'icon' => 'receipt'],
        ['label' => 'Rata-rata Pesanan', 'value' => $formatMoney($averageOrder), 'change' => $averageChange, 'icon' => 'average'],
        $roleOwner
            ? ['label' => 'Estimasi Laba Kotor', 'value' => $formatMoney($grossProfit), 'change' => $profitChange, 'icon' => 'profit']
            : ['label' => 'Item Stok Menipis', 'value' => $lowStock->count().' item', 'change' => null, 'icon' => 'stock'],
    ];
    $icons = [
        'wallet' => '<path d="M4 7h15a2 2 0 0 1 2 2v9H4a2 2 0 0 1-2-2V6a3 3 0 0 1 3-3h12v4M16 12h5"/>',
        'receipt' => '<path d="M6 3h12v18l-3-2-3 2-3-2-3 2V3Z"/><path d="M9 8h6M9 12h6M9 16h3"/>',
        'average' => '<path d="M4 19V9M10 19V5M16 19v-7M22 19H2"/>',
        'profit' => '<path d="m4 16 5-5 4 4 7-8"/><path d="M15 7h5v5"/>',
        'stock' => '<path d="m4 7 8-4 8 4-8 4-8-4Z"/><path d="m4 7 8 4 8-4M4 12l8 4 8-4M4 17l8 4 8-4"/>',
    ];
    $chartMax = max(1, collect($chart)->max('value'));
    $chartWidth = 700;
    $chartHeight = 250;
    $paddingX = 12;
    $paddingY = 18;
    $usableWidth = $chartWidth - ($paddingX * 2);
    $usableHeight = $chartHeight - ($paddingY * 2);
    $chartPoints = collect($chart)->values()->map(function ($point, $index) use ($chart, $chartMax, $paddingX, $paddingY, $usableWidth, $usableHeight) {
        $x = $paddingX + (($usableWidth / max(1, count($chart) - 1)) * $index);
        $y = $paddingY + $usableHeight - (($point['value'] / $chartMax) * $usableHeight);
        return ['x' => round($x, 2), 'y' => round($y, 2), ...$point];
    });
    $linePath = $chartPoints->map(fn ($point, $index) => ($index === 0 ? 'M' : 'L').' '.$point['x'].' '.$point['y'])->implode(' ');
    $firstPoint = $chartPoints->first();
    $lastPoint = $chartPoints->last();
    $areaPath = $linePath.' L '.$lastPoint['x'].' '.($chartHeight - $paddingY).' L '.$firstPoint['x'].' '.($chartHeight - $paddingY).' Z';
    $targetDegrees = (int) round(($targetProgress / 100) * 360);
@endphp

<div class="mx-auto max-w-[1600px] space-y-5 p-4 sm:p-6 lg:p-8">
    <section class="dashboard-card flex flex-col gap-3 p-3 sm:flex-row sm:items-center sm:justify-between sm:p-4" aria-label="Filter dashboard">
        <div class="scrollbar-thin flex gap-2 overflow-x-auto pb-1 sm:pb-0">
            @foreach(['today' => 'Hari ini', 'week' => '7 hari', 'month' => '30 hari'] as $value => $label)
                <button type="button" wire:click="setPeriod('{{ $value }}')" wire:loading.attr="disabled" class="pressable min-h-10 shrink-0 rounded-xl px-4 text-sm font-semibold {{ $period === $value ? 'bg-brand-600 text-white' : 'border border-stone-200 bg-white text-stone-600 hover:bg-brand-50' }}">{{ $label }}</button>
            @endforeach
        </div>
        <div class="flex items-center gap-2">
            <div class="hidden text-right md:block"><p class="text-xs font-semibold text-stone-700">Terakhir diperbarui</p><p class="text-[11px] text-stone-500">{{ now()->format('H:i') }} WIB</p></div>
            <a href="{{ route('reports.sales') }}" class="pressable inline-flex min-h-10 flex-1 items-center justify-center gap-2 rounded-xl border border-stone-200 bg-white px-3 text-sm font-semibold text-stone-600 hover:bg-stone-50 sm:flex-none">
                <svg viewBox="0 0 24 24" class="size-4" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M5 20V10M12 20V4M19 20v-7"/></svg>Laporan
            </a>
        </div>
    </section>

    <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Ringkasan performa">
        @foreach($kpis as $index => $item)
            @php($positive = $item['change'] !== null && $item['change'] >= 0)
            <article class="dashboard-card group relative overflow-hidden p-4 sm:p-5">
                <div class="absolute -right-8 -top-8 size-28 rounded-full {{ $index === 0 ? 'bg-brand-50' : 'bg-stone-50' }} transition group-hover:scale-110"></div>
                <div class="relative flex items-start justify-between gap-3">
                    <div class="min-w-0"><p class="truncate text-sm font-medium text-stone-500">{{ $item['label'] }}</p><p class="mt-2 truncate text-2xl font-black tracking-tight sm:text-[28px]">{{ $item['value'] }}</p></div>
                    <div class="grid size-11 shrink-0 place-items-center rounded-2xl {{ $index === 0 ? 'bg-brand-100 text-brand-700' : 'bg-stone-100 text-stone-600' }}"><svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="1.8">{!! $icons[$item['icon']] !!}</svg></div>
                </div>
                <div class="relative mt-4 flex items-center gap-2 text-xs">
                    @if($item['change'] !== null)
                        <span class="inline-flex items-center gap-1 rounded-full px-2 py-1 font-bold {{ $positive ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">{{ $formatChange($item['change']) }}</span><span class="text-stone-400">dibanding periode lalu</span>
                    @else
                        <a href="{{ route('inventory') }}" class="inline-flex rounded-full bg-rose-100 px-2 py-1 font-bold text-rose-700">Perlu tindakan</a><span class="text-stone-400">cek inventori</span>
                    @endif
                </div>
            </article>
        @endforeach
    </section>

    <section class="grid gap-5 xl:grid-cols-[minmax(0,1.65fr)_minmax(320px,0.75fr)]">
        <article class="dashboard-card min-w-0 p-4 sm:p-5">
            <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div><div class="flex items-center gap-2"><h2 class="font-bold tracking-tight">Tren Penjualan</h2><span class="rounded-full {{ $revenueChange >= 0 ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }} px-2 py-1 text-[10px] font-bold">{{ $formatChange($revenueChange) }}</span></div><p class="mt-1 text-sm text-stone-500">Omzet {{ $periodLabel }} berdasarkan transaksi tersimpan.</p></div>
                <span class="flex items-center gap-2 text-xs font-medium text-stone-500"><span class="size-2 rounded-full bg-brand-500"></span>Penjualan</span>
            </div>
            <div class="relative h-[280px] w-full overflow-hidden" aria-label="Grafik tren penjualan">
                <svg viewBox="0 0 {{ $chartWidth }} {{ $chartHeight }}" preserveAspectRatio="none" class="h-full w-full" role="img" aria-label="Grafik penjualan {{ $periodLabel }}">
                    <defs><linearGradient id="salesGradient" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stop-color="#f97316" stop-opacity="0.28"/><stop offset="100%" stop-color="#f97316" stop-opacity="0.02"/></linearGradient></defs>
                    @foreach([.25,.5,.75,1] as $ratio)<line x1="{{ $paddingX }}" y1="{{ $paddingY + ($usableHeight * $ratio) }}" x2="{{ $chartWidth - $paddingX }}" y2="{{ $paddingY + ($usableHeight * $ratio) }}" stroke="#e7e5e4" stroke-width="1" stroke-dasharray="4 5"/>@endforeach
                    <path d="{{ $areaPath }}" fill="url(#salesGradient)"/><path d="{{ $linePath }}" fill="none" stroke="#f97316" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" vector-effect="non-scaling-stroke"/>
                    @foreach($chartPoints as $point)<circle cx="{{ $point['x'] }}" cy="{{ $point['y'] }}" r="4" fill="#fff" stroke="#f97316" stroke-width="3"><title>{{ $point['label'] }}: {{ $formatMoney($point['value']) }}</title></circle>@endforeach
                </svg>
            </div>
            <div class="mt-3 grid grid-cols-7 text-center text-[10px] text-stone-500 sm:text-xs">@foreach($chart as $point)<span>{{ $point['label'] }}</span>@endforeach</div>
        </article>

        <article class="dashboard-card p-4 sm:p-5">
            <div class="flex items-start justify-between gap-4"><div><h2 class="font-bold tracking-tight">Target Bulanan</h2><p class="mt-1 text-sm text-stone-500">Progress omzet bulan {{ now()->translatedFormat('F') }}.</p></div>@if($roleOwner)<a href="{{ route('settings') }}" class="pressable grid size-9 place-items-center rounded-xl text-stone-500 hover:bg-stone-100" aria-label="Atur target"><svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="3"/><path d="M19 12a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z"/></svg></a>@endif</div>
            <div class="mx-auto mt-5 grid size-44 place-items-center rounded-full" style="background:conic-gradient(#f97316 0deg {{ $targetDegrees }}deg,#f5f5f4 {{ $targetDegrees }}deg 360deg)"><div class="grid size-32 place-items-center rounded-full bg-white text-center shadow-inner"><div><p class="text-3xl font-black tracking-tight">{{ $targetProgress }}%</p><p class="mt-1 text-xs text-stone-500">tercapai</p></div></div></div>
            <div class="mt-5 rounded-2xl bg-stone-50 p-4"><div class="flex items-center justify-between gap-3 text-sm"><span class="text-stone-500">Realisasi</span><strong>{{ $formatMoney($monthlyRevenue) }}</strong></div><div class="mt-2 flex items-center justify-between gap-3 text-sm"><span class="text-stone-500">Target</span><strong>{{ $formatMoney($monthlyTarget) }}</strong></div><p class="mt-3 text-xs leading-relaxed text-stone-500">Butuh rata-rata <strong class="text-stone-800">{{ $formatMoney($dailyTargetNeeded) }}/hari</strong> untuk mencapai target.</p></div>
        </article>
    </section>

    <section class="grid gap-5 xl:grid-cols-[minmax(0,1.15fr)_minmax(0,0.85fr)]">
        <article class="dashboard-card min-w-0 overflow-hidden">
            <div class="flex items-start justify-between gap-3 border-b border-stone-200 p-4 sm:p-5"><div><h2 class="font-bold tracking-tight">Menu Terlaris</h2><p class="mt-1 text-sm text-stone-500">Berdasarkan jumlah item terjual {{ $periodLabel }}.</p></div><a href="{{ route('reports.sales') }}" class="pressable min-h-10 rounded-xl border border-stone-200 px-3 py-2.5 text-xs font-semibold text-stone-600 hover:bg-stone-50">Lihat semua</a></div>
            <div class="divide-y divide-stone-100">
                @forelse($topProducts as $index => $product)
                    <div class="flex items-center gap-3 p-4 sm:px-5"><div class="grid size-9 shrink-0 place-items-center rounded-xl {{ $index === 0 ? 'bg-brand-500 text-white' : 'bg-stone-100 text-stone-500' }} text-xs font-black">{{ $index + 1 }}</div><div class="grid size-11 shrink-0 place-items-center rounded-2xl bg-brand-50 text-xl">☕</div><div class="min-w-0 flex-1"><p class="truncate text-sm font-bold">{{ $product->product_name_snapshot }}</p><p class="mt-0.5 text-xs text-stone-500">{{ number_format($product->sold) }} terjual</p></div><p class="text-right text-sm font-bold">{{ $formatMoney($product->revenue) }}</p></div>
                @empty
                    <p class="p-8 text-center text-sm text-stone-500">Belum ada penjualan pada periode ini.</p>
                @endforelse
            </div>
        </article>

        <article class="dashboard-card min-w-0 overflow-hidden">
            <div class="flex items-start justify-between gap-3 border-b border-stone-200 p-4 sm:p-5"><div><div class="flex items-center gap-2"><h2 class="font-bold tracking-tight">Stok Perlu Perhatian</h2><span class="grid size-6 place-items-center rounded-full bg-rose-100 text-[10px] font-bold text-rose-700">{{ $lowStock->count() }}</span></div><p class="mt-1 text-sm text-stone-500">Segera lakukan pembelian atau penyesuaian.</p></div><a href="{{ route('inventory') }}" class="pressable min-h-10 rounded-xl bg-brand-600 px-3 py-2.5 text-xs font-semibold text-white hover:bg-brand-700">Restock</a></div>
            <div class="divide-y divide-stone-100">
                @forelse($lowStock as $item)
                    @php($level = (float)$item->minimum_stock > 0 ? min(100, (int)round(((float)$item->current_stock / (float)$item->minimum_stock) * 100)) : 0)
                    <a href="{{ route('inventory') }}" class="pressable flex w-full items-center gap-3 p-4 text-left hover:bg-stone-50 sm:px-5"><div class="min-w-0 flex-1"><div class="flex items-center justify-between gap-3"><p class="truncate text-sm font-bold">{{ $item->name }}</p><span class="shrink-0 rounded-full bg-rose-100 px-2 py-1 text-[10px] font-bold text-rose-700">{{ $level <= 30 ? 'Kritis' : 'Rendah' }}</span></div><div class="mt-2 h-1.5 overflow-hidden rounded-full bg-stone-100"><div class="h-full rounded-full {{ $level <= 30 ? 'bg-rose-500' : 'bg-amber-500' }}" style="width:{{ $level }}%"></div></div><div class="mt-1.5 flex items-center justify-between gap-3 text-xs text-stone-500"><span>{{ (float)$item->current_stock }} {{ $item->unit->symbol }}</span><span>Min. {{ (float)$item->minimum_stock }} {{ $item->unit->symbol }}</span></div></div><svg viewBox="0 0 24 24" class="size-4 shrink-0 text-stone-400" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m9 18 6-6-6-6"/></svg></a>
                @empty
                    <p class="p-8 text-center text-sm text-emerald-700">Semua stok aman.</p>
                @endforelse
            </div>
        </article>
    </section>

    <section class="grid gap-5 lg:grid-cols-2">
        <article class="dashboard-card p-4 sm:p-5">
            <div class="mb-5"><h2 class="font-bold tracking-tight">Metode Pembayaran</h2><p class="mt-1 text-sm text-stone-500">Distribusi transaksi {{ $periodLabel }}.</p></div>
            <div class="space-y-4">
                @forelse($paymentMethods as $index => $method)
                    @php($label = match($method->method->value ?? $method->method) {'CASH'=>'Tunai','DEBIT_CARD'=>'Kartu Debit','CREDIT_CARD'=>'Kartu Kredit','BANK_TRANSFER'=>'Transfer Bank','EWALLET'=>'E-Wallet',default=>str_replace('_',' ',($method->method->value ?? $method->method))})
                    <div><div class="mb-2 flex items-center justify-between gap-4"><div class="flex items-center gap-2"><span class="size-3 rounded-full {{ $index === 0 ? 'bg-brand-500' : ($index === 1 ? 'bg-stone-800' : 'bg-stone-400') }}"></span><span class="text-sm font-semibold">{{ $label }}</span></div><div class="text-right"><p class="text-sm font-bold">{{ $formatMoney($method->total) }}</p><p class="text-[11px] text-stone-500">{{ $method->transaction_count }} transaksi</p></div></div><div class="h-2 overflow-hidden rounded-full bg-stone-100"><div class="h-full rounded-full {{ $index === 0 ? 'bg-brand-500' : ($index === 1 ? 'bg-stone-800' : 'bg-stone-400') }}" style="width:{{ $method->percentage }}%"></div></div></div>
                @empty
                    <p class="py-8 text-center text-sm text-stone-500">Belum ada data pembayaran.</p>
                @endforelse
            </div>
        </article>

        <article class="dashboard-card overflow-hidden">
            <div class="flex items-start justify-between gap-3 border-b border-stone-200 p-4 sm:p-5"><div><h2 class="font-bold tracking-tight">Aktivitas Terbaru</h2><p class="mt-1 text-sm text-stone-500">Transaksi terbaru yang terjadi di toko.</p></div><a href="{{ route('orders') }}" class="pressable grid size-10 place-items-center rounded-xl text-stone-500 hover:bg-stone-100" aria-label="Lihat semua transaksi"><svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 3h12v18l-3-2-3 2-3-2-3 2V3Z"/><path d="M9 8h6M9 12h6M9 16h3"/></svg></a></div>
            <div class="divide-y divide-stone-100">
                @forelse($recentOrders as $order)
                    <a href="{{ route('orders') }}" class="flex gap-3 p-4 hover:bg-stone-50 sm:px-5"><div class="grid size-10 shrink-0 place-items-center rounded-2xl bg-emerald-100 text-emerald-700"><svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m5 12 4 4L19 6"/></svg></div><div class="min-w-0 flex-1"><p class="text-sm font-bold leading-5">Transaksi {{ $order->order_number }} selesai</p><p class="mt-0.5 text-xs leading-5 text-stone-500">{{ str_replace('_',' ',$order->payment?->method?->value ?? '-') }} · {{ $formatMoney($order->grand_total) }} · {{ $order->cashier?->name }}</p></div><time class="shrink-0 text-[11px] text-stone-400">{{ $order->paid_at?->diffForHumans() }}</time></a>
                @empty
                    <p class="p-8 text-center text-sm text-stone-500">Belum ada aktivitas transaksi.</p>
                @endforelse
            </div>
        </article>
    </section>

    <section class="dashboard-card overflow-hidden">
        <div class="flex flex-col gap-3 border-b border-stone-200 p-4 sm:p-5"><div class="flex items-start justify-between gap-3"><div><h2 class="font-bold tracking-tight">Ringkasan Shift Hari Ini</h2><p class="mt-1 text-sm text-stone-500">Pantau status kasir dan pencatatan kas.</p></div><a href="{{ route('shifts') }}" class="pressable min-h-10 rounded-xl border border-stone-200 bg-white px-3 py-2.5 text-xs font-semibold text-stone-600 hover:bg-stone-50">Riwayat shift</a></div><div class="grid gap-2 sm:grid-cols-[1fr_180px]"><input wire:model.live.debounce.300ms="shiftSearch" class="field-input" placeholder="Cari kasir"><x-ui.dropdown model="shiftStatus" :options="[['value'=>'','label'=>'Semua status'],['value'=>'OPEN','label'=>'Aktif'],['value'=>'CLOSED','label'=>'Tutup']]" /></div></div>
        <div class="overflow-x-auto"><table class="data-table"><thead><tr><th>Kasir</th><th>Mulai</th><th>Transaksi</th><th>Penjualan</th><th>Kas Tunai</th><th>Status</th></tr></thead><tbody>
            @forelse($shiftSummary as $shift)
                <tr><td><div class="flex items-center gap-3"><div class="grid size-9 place-items-center rounded-xl bg-brand-100 text-xs font-bold text-brand-800">{{ strtoupper(substr($shift->cashier?->name ?? 'K', 0, 2)) }}</div><span class="font-semibold">{{ $shift->cashier?->name }}</span></div></td><td class="text-stone-600">{{ $shift->opened_at->format('H.i') }}</td><td class="font-semibold">{{ $shift->transaction_count }}</td><td class="font-semibold">{{ $formatMoney($shift->sales_total) }}</td><td class="text-stone-600">{{ $formatMoney($shift->cash_total) }}</td><td><span class="inline-flex items-center gap-1.5 rounded-full {{ $shift->status->value === 'OPEN' ? 'bg-emerald-100 text-emerald-700' : 'bg-stone-100 text-stone-600' }} px-2.5 py-1 text-xs font-bold"><span class="size-1.5 rounded-full {{ $shift->status->value === 'OPEN' ? 'bg-emerald-500' : 'bg-stone-400' }}"></span>{{ $shift->status->value === 'OPEN' ? 'Aktif' : 'Tutup' }}</span></td></tr>
            @empty
                <tr><td colspan="6" class="px-5 py-10 text-center text-sm text-stone-500">Belum ada shift hari ini.</td></tr>
            @endforelse
        </tbody></table></div>
        <div data-shift-pagination class="border-t border-stone-200 px-4 py-3 sm:px-5">{{ $shiftSummary->links() }}</div>
    </section>

    <p class="pb-2 text-center text-xs text-stone-400">Data dashboard berasal dari transaksi, pembayaran, shift, dan inventori tersimpan.</p>
</div>
