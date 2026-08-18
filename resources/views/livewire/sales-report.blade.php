@php
    $formatMoney = fn (int|float $value) => 'Rp '.number_format($value, 0, ',', '.');
    $formatChange = fn (float $value) => ($value >= 0 ? '+' : '').number_format($value, 1, ',', '.').'%';
    $chartData = count($chart) ? $chart : [['label' => 'Belum ada', 'value' => 0]];
    $chartMax = max(1, collect($chartData)->max('value'));
    $chartWidth = 800;
    $chartHeight = 280;
    $paddingX = 18;
    $paddingY = 22;
    $usableWidth = $chartWidth - ($paddingX * 2);
    $usableHeight = $chartHeight - ($paddingY * 2);
    $chartPoints = collect($chartData)->values()->map(function ($point, $index) use ($chartData, $chartMax, $paddingX, $paddingY, $usableWidth, $usableHeight) {
        $x = $paddingX + (($usableWidth / max(1, count($chartData) - 1)) * $index);
        $y = $paddingY + $usableHeight - (($point['value'] / $chartMax) * $usableHeight);
        return ['x' => round($x, 2), 'y' => round($y, 2), ...$point];
    });
    $linePath = $chartPoints->map(fn ($point, $index) => ($index === 0 ? 'M' : 'L').' '.$point['x'].' '.$point['y'])->implode(' ');
    $firstPoint = $chartPoints->first();
    $lastPoint = $chartPoints->last();
    $areaPath = $linePath.' L '.$lastPoint['x'].' '.($chartHeight - $paddingY).' L '.$firstPoint['x'].' '.($chartHeight - $paddingY).' Z';
    $targetDegrees = (int) round(($targetProgress / 100) * 360);
    $kpis = [
        ['label' => 'Penjualan Bersih', 'value' => $formatMoney($total), 'meta' => $formatChange($revenueChange).' vs periode lalu', 'tone' => 'brand'],
        ['label' => 'Total Transaksi', 'value' => number_format($count, 0, ',', '.'), 'meta' => 'transaksi selesai', 'tone' => 'stone'],
        ['label' => 'Rata-rata Pesanan', 'value' => $formatMoney($average), 'meta' => 'per transaksi', 'tone' => 'stone'],
        ['label' => 'Diskon Diberikan', 'value' => $formatMoney($discountTotal), 'meta' => 'Pajak '.$formatMoney($taxTotal), 'tone' => 'stone'],
    ];
@endphp

<div class="mx-auto max-w-[1600px] space-y-5">
    <section class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-brand-700 via-brand-600 to-brand-400 p-5 text-white shadow-xl shadow-brand-200/50 sm:p-7">
        <div class="absolute -right-16 -top-20 size-64 rounded-full bg-white/10"></div><div class="absolute -bottom-28 right-28 size-56 rounded-full bg-white/5"></div>
        <div class="relative flex flex-col gap-5 xl:flex-row xl:items-end xl:justify-between"><div><span class="inline-flex rounded-full bg-white/15 px-3 py-1 text-xs font-bold uppercase tracking-[.16em]">Analytics</span><h2 class="mt-3 text-2xl font-black tracking-tight sm:text-3xl">Laporan Penjualan</h2><p class="mt-2 max-w-2xl text-sm leading-6 text-brand-50">Pantau omzet, transaksi, tren harian, metode pembayaran, dan progres target toko dalam satu tampilan.</p><p class="mt-3 text-xs font-semibold text-white/75">Periode aktif: {{ $periodLabel }}</p></div>
            <div class="grid gap-2 sm:grid-cols-[170px_170px_auto]"><label class="text-xs font-bold text-white/80">Dari<input wire:model.live="startDate" type="date" class="field-input mt-1 border-white/25 bg-white text-stone-800"></label><label class="text-xs font-bold text-white/80">Sampai<input wire:model.live="endDate" type="date" class="field-input mt-1 border-white/25 bg-white text-stone-800"></label><button wire:click="exportCsv" class="pressable mt-auto inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-white px-4 text-sm font-bold text-brand-700 shadow-lg hover:bg-brand-50"><svg viewBox="0 0 24 24" class="size-4" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3v12m0 0 4-4m-4 4-4-4M5 19h14"/></svg>Export CSV</button></div>
        </div>
    </section>

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Ringkasan laporan">
        @foreach($kpis as $index => $item)<article class="dashboard-card group relative overflow-hidden p-5"><div class="absolute -right-9 -top-9 size-28 rounded-full {{ $item['tone'] === 'brand' ? 'bg-brand-50' : 'bg-stone-50' }} transition group-hover:scale-110"></div><div class="relative flex items-start justify-between gap-3"><div class="min-w-0"><p class="text-sm font-medium text-stone-500">{{ $item['label'] }}</p><p class="mt-2 truncate text-2xl font-black tracking-tight">{{ $item['value'] }}</p></div><div class="grid size-11 shrink-0 place-items-center rounded-2xl {{ $item['tone'] === 'brand' ? 'bg-brand-100 text-brand-700' : 'bg-stone-100 text-stone-600' }}"><svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="1.8">@if($index === 0)<path d="m4 16 5-5 4 4 7-8M15 7h5v5"/>@elseif($index === 1)<path d="M6 3h12v18l-3-2-3 2-3-2-3 2V3Z"/><path d="M9 8h6M9 12h6"/>@elseif($index === 2)<path d="M4 19V9M10 19V5M16 19v-7M22 19H2"/>@else<path d="M4 6h16v12H4zM8 10h8M8 14h5"/>@endif</svg></div></div><p class="relative mt-4 text-xs font-semibold {{ $index === 0 && $revenueChange >= 0 ? 'text-emerald-700' : 'text-stone-500' }}">{{ $item['meta'] }}</p></article>@endforeach
    </section>

    <section class="grid gap-5 xl:grid-cols-[minmax(0,1.65fr)_minmax(320px,.75fr)]">
        <article class="dashboard-card min-w-0 p-5"><div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between"><div><div class="flex items-center gap-2"><h3 class="font-bold tracking-tight">Tren Penjualan</h3><span class="rounded-full {{ $revenueChange >= 0 ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }} px-2 py-1 text-[10px] font-bold">{{ $formatChange($revenueChange) }}</span></div><p class="mt-1 text-sm text-stone-500">Omzet harian pada periode terpilih.</p></div><span class="flex items-center gap-2 text-xs font-semibold text-stone-500"><span class="size-2 rounded-full bg-brand-500"></span>Penjualan bersih</span></div>
            <div class="relative h-[300px] overflow-hidden" aria-label="Grafik tren penjualan"><svg viewBox="0 0 {{ $chartWidth }} {{ $chartHeight }}" preserveAspectRatio="none" class="h-full w-full" role="img"> <defs><linearGradient id="reportSalesGradient" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stop-color="#f97316" stop-opacity=".32"/><stop offset="100%" stop-color="#f97316" stop-opacity=".02"/></linearGradient></defs>@foreach([.25,.5,.75,1] as $ratio)<line x1="{{ $paddingX }}" y1="{{ $paddingY + ($usableHeight * $ratio) }}" x2="{{ $chartWidth - $paddingX }}" y2="{{ $paddingY + ($usableHeight * $ratio) }}" stroke="#e7e5e4" stroke-width="1" stroke-dasharray="5 6"/>@endforeach<path d="{{ $areaPath }}" fill="url(#reportSalesGradient)"/><path d="{{ $linePath }}" fill="none" stroke="#f97316" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" vector-effect="non-scaling-stroke"/>@foreach($chartPoints as $point)<circle cx="{{ $point['x'] }}" cy="{{ $point['y'] }}" r="4" fill="white" stroke="#f97316" stroke-width="3"><title>{{ $point['label'] }}: {{ $formatMoney($point['value']) }}</title></circle>@endforeach</svg></div><div class="mt-2 flex justify-between gap-2 overflow-hidden text-[10px] font-medium text-stone-500">@foreach($chartData as $point)<span class="truncate">{{ $point['label'] }}</span>@endforeach</div>
        </article>

        <article class="dashboard-card p-5"><div class="flex items-start justify-between"><div><h3 class="font-bold tracking-tight">Target Bulanan</h3><p class="mt-1 text-sm text-stone-500">Progres omzet {{ now()->translatedFormat('F Y') }}.</p></div><svg viewBox="0 0 24 24" class="size-5 text-brand-600" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="8"/><circle cx="12" cy="12" r="3"/><path d="m14 10 6-6"/></svg></div><div class="mx-auto mt-6 grid size-44 place-items-center rounded-full" style="background:conic-gradient(#f97316 0deg {{ $targetDegrees }}deg,#f5f5f4 {{ $targetDegrees }}deg 360deg)"><div class="grid size-32 place-items-center rounded-full bg-white text-center shadow-inner"><div><p class="text-3xl font-black">{{ $targetProgress }}%</p><p class="text-xs text-stone-500">tercapai</p></div></div></div><div class="mt-6 space-y-2 rounded-2xl bg-stone-50 p-4 text-sm"><div class="flex justify-between gap-3"><span class="text-stone-500">Realisasi</span><strong>{{ $formatMoney($monthlyRevenue) }}</strong></div><div class="flex justify-between gap-3"><span class="text-stone-500">Target</span><strong>{{ $formatMoney($monthlyTarget) }}</strong></div><p class="border-t border-stone-200 pt-3 text-xs leading-5 text-stone-500">Perlu rata-rata <strong class="text-stone-800">{{ $formatMoney($dailyTargetNeeded) }}/hari</strong> untuk mencapai target.</p></div></article>
    </section>

    <section class="grid gap-5 lg:grid-cols-[.75fr_1.25fr]">
        <article class="dashboard-card p-5"><div><h3 class="font-bold">Metode Pembayaran</h3><p class="mt-1 text-sm text-stone-500">Kontribusi omzet per metode.</p></div><div class="mt-5 space-y-5">@forelse($paymentBreakdown as $row)@php($method = $row->method->value ?? $row->method)@php($label = $method === 'CASH' ? 'Tunai' : ($method === 'QRIS' ? 'QRIS' : str_replace('_',' ',$method)))<div><div class="mb-2 flex items-center justify-between gap-4"><div><p class="text-sm font-bold">{{ $label }}</p><p class="text-xs text-stone-500">{{ $row->count }} transaksi</p></div><div class="text-right"><p class="text-sm font-black">{{ $formatMoney($row->total) }}</p><p class="text-xs font-bold text-brand-700">{{ $row->percentage }}%</p></div></div><div class="h-2 overflow-hidden rounded-full bg-stone-100"><div class="h-full rounded-full bg-brand-500" style="width:{{ $row->percentage }}%"></div></div></div>@empty<p class="rounded-2xl bg-stone-50 py-10 text-center text-sm text-stone-500">Belum ada pembayaran pada periode ini.</p>@endforelse</div></article>

        <article class="dashboard-card min-w-0 overflow-hidden"><div class="border-b border-stone-200 p-5"><h3 class="font-bold">Detail Penjualan Harian</h3><p class="mt-1 text-sm text-stone-500">Breakdown omzet, diskon, pajak, dan transaksi per hari.</p></div><div class="overflow-x-auto"><table class="data-table"><thead><tr><th>Tanggal</th><th class="text-right">Transaksi</th><th class="text-right">Diskon</th><th class="text-right">Pajak</th><th class="text-right">Penjualan</th></tr></thead><tbody>@forelse($daily as $row)<tr><td class="font-bold">{{ \Carbon\Carbon::parse($row->sale_date)->translatedFormat('d M Y') }}</td><td class="text-right">{{ number_format($row->count) }}</td><td class="text-right text-rose-600">{{ $formatMoney($row->discount) }}</td><td class="text-right text-stone-600">{{ $formatMoney($row->tax) }}</td><td class="text-right font-black text-brand-700">{{ $formatMoney($row->total) }}</td></tr>@empty<tr><td colspan="5" class="py-12 text-center text-sm text-stone-500">Belum ada penjualan pada periode ini.</td></tr>@endforelse</tbody></table></div><div class="border-t border-stone-200 p-4">{{ $daily->links() }}</div></article>
    </section>
</div>
