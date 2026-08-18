<div class="mx-auto max-w-3xl space-y-3">
    <div>
        <p class="text-xs text-stone-500">Identitas toko & struk</p>
        <h2 class="text-xl font-bold">Pengaturan</h2>
    </div>
    <form wire:submit="save" class="card grid gap-3 p-4 md:grid-cols-2">
        <label class="field-label">Nama toko
            <input wire:model="form.store_name" class="field-input mt-1">
        </label>
        <label class="field-label">Telepon
            <input wire:model="form.phone" class="field-input mt-1">
        </label>
        <label class="field-label md:col-span-2">Alamat
            <textarea wire:model="form.address" class="field-input mt-1 min-h-20 py-2"></textarea>
        </label>
        <label class="field-label">Timezone
            <input wire:model="form.timezone" class="field-input mt-1">
        </label>
        <label class="field-label">Prefix transaksi
            <input wire:model="form.transaction_prefix" class="field-input mt-1">
        </label>
        <section class="rounded-2xl border border-brand-200 bg-brand-50/60 p-4">
            <div class="flex items-center justify-between gap-3"><div><h3 class="text-sm font-bold">Aktifkan Pajak</h3><p class="mt-1 text-xs text-stone-500">Terapkan pajak ke transaksi POS.</p></div><label class="relative inline-flex cursor-pointer items-center"><input wire:model.live="form.tax_enabled" type="checkbox" class="peer sr-only"><span class="h-7 w-12 rounded-full bg-stone-300 transition peer-checked:bg-brand-600 after:absolute after:left-1 after:top-1 after:size-5 after:rounded-full after:bg-white after:shadow after:transition peer-checked:after:translate-x-5"></span><span class="sr-only">Aktifkan Pajak</span></label></div>
            <label class="mt-4 block text-xs font-semibold text-stone-700">Persentase pajak (%)<input wire:model="form.tax_rate" type="number" min="0" max="100" step="0.01" class="field-input mt-1 text-right font-bold tabular-nums" @disabled(! ($form['tax_enabled'] ?? false))></label>
            @unless($form['tax_enabled'] ?? false)<p class="mt-2 text-xs font-medium text-stone-500">Nilai persen tetap disimpan, tetapi pajak efektif menjadi 0%.</p>@endunless
        </section>

        <label class="field-label md:col-span-2">Footer struk
            <textarea wire:model="form.receipt_footer" class="field-input mt-1 min-h-16 py-2"></textarea>
        </label>
        <section class="rounded-2xl border border-brand-200 bg-brand-50/60 p-4 md:col-span-2">
            <div class="flex items-center justify-between gap-4"><div><h3 class="text-sm font-bold">Aktifkan QRIS</h3><p class="mt-1 text-xs text-stone-500">Tampilkan QRIS statis sebagai metode pembayaran kasir.</p></div><label class="relative inline-flex cursor-pointer items-center"><input wire:model="form.qris_enabled" type="checkbox" class="peer sr-only"><span class="h-7 w-12 rounded-full bg-stone-300 transition peer-checked:bg-brand-600 after:absolute after:left-1 after:top-1 after:size-5 after:rounded-full after:bg-white after:shadow after:transition peer-checked:after:translate-x-5"></span><span class="sr-only">Aktifkan QRIS</span></label></div>
            <label class="mt-4 block text-xs font-semibold">Upload gambar QRIS<input wire:model="qrisImage" type="file" accept="image/jpeg,image/png,image/webp" class="mt-2 block w-full rounded-xl border border-stone-200 bg-white p-2 text-sm"></label>
            <div wire:loading wire:target="qrisImage" class="mt-2 text-xs font-semibold text-brand-700">Memproses gambar…</div>
            @if($qrisImage || ($form['qris_image_path'] ?? null))<div class="mt-4"><p class="mb-2 text-xs font-bold text-stone-600">Preview QRIS</p><img src="{{ $qrisImage ? $qrisImage->temporaryUrl() : asset('storage/'.$form['qris_image_path']) }}" class="size-52 rounded-2xl border border-brand-200 bg-white object-contain p-2 shadow-sm" alt="Preview QRIS"></div>@endif
        </section>

        <div class="md:col-span-2">
            @if($errors->any())
                <div class="mb-2 rounded-md bg-rose-50 p-2 text-xs text-rose-700">{{ $errors->first() }}</div>
            @endif
            <button class="btn-primary">Simpan</button>
        </div>
    </form>
</div>
