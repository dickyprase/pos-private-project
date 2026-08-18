@php
    $categoryOptions = $categories->map(fn ($category) => ['value' => $category->id, 'label' => $category->name])->values()->all();
    $productIcons = ['coffee' => '☕', 'non-coffee' => '🍵', 'tea' => '🫖', 'food' => '🥐', 'dessert' => '🍰'];
    $productTones = ['coffee' => 'bg-amber-100', 'non-coffee' => 'bg-emerald-100', 'tea' => 'bg-pink-100', 'food' => 'bg-orange-100', 'dessert' => 'bg-stone-200'];
@endphp

<div class="space-y-4">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs text-stone-500">Katalog dan harga jual</p>
            <h2 class="text-xl font-bold tracking-tight">Menu Produk</h2>
        </div>
        <button type="button" wire:click="create" class="btn-primary gap-2">
            <svg viewBox="0 0 24 24" class="size-4" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
            Produk baru
        </button>
    </div>

    <section class="grid gap-2 rounded-2xl border border-stone-200 bg-white p-3 shadow-sm md:grid-cols-[1fr_220px_220px]">
        <div class="relative"><svg viewBox="0 0 24 24" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-stone-400" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="7"/><path d="m16.5 16.5 4 4"/></svg><input wire:model.live.debounce.300ms="search" class="field-input pl-10" placeholder="Cari produk atau SKU"></div>
        <x-ui.dropdown model="categoryFilter" :options="collect([['value'=>'','label'=>'Semua kategori']])->concat($categoryOptions)->all()" />
        <x-ui.dropdown model="statusFilter" :options="[['value'=>'','label'=>'Semua status'],['value'=>'active','label'=>'Aktif'],['value'=>'inactive','label'=>'Nonaktif']]" />
    </section>

    <div class="table-shell overflow-x-auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Produk</th>
                    <th>Kategori</th>
                    <th class="text-right">Harga</th>
                    <th class="text-right">Modal</th>
                    <th>Status</th>
                    <th class="text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($products as $product)
                    @php
                        $slug = $product->category->slug;
                        $icon = $productIcons[$slug] ?? '•';
                        $tone = $productTones[$slug] ?? 'bg-stone-100';
                    @endphp
                    <tr wire:key="product-row-{{ $product->id }}">
                        <td>
                            <div class="flex min-w-56 items-center gap-3">
                                @if($product->image_path)
                                    <img src="{{ asset('storage/'.$product->image_path) }}" alt="{{ $product->name }}" class="size-12 shrink-0 rounded-xl object-cover">
                                @else
                                    <span class="grid size-12 shrink-0 place-items-center rounded-xl {{ $tone }} text-2xl" aria-hidden="true">{{ $icon }}</span>
                                @endif
                                <div class="min-w-0">
                                    <p class="truncate font-bold text-stone-800">{{ $product->name }}</p>
                                    <p class="mt-0.5 text-xs text-stone-500">{{ $product->sku }} @if($product->is_favorite) · Favorit ★ @endif</p>
                                </div>
                            </div>
                        </td>
                        <td><span class="inline-flex rounded-lg bg-stone-100 px-2.5 py-1 text-xs font-semibold text-stone-600">{{ $product->category->name }}</span></td>
                        <td class="text-right font-bold text-brand-700">Rp {{ number_format($product->base_price, 0, ',', '.') }}</td>
                        <td class="text-right text-stone-600">Rp {{ number_format($product->cost_estimate, 0, ',', '.') }}</td>
                        <td>
                            <div class="flex flex-wrap gap-1.5">
                                <span class="status-badge {{ $product->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-stone-100 text-stone-600' }}">{{ $product->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                                <span class="status-badge {{ $product->is_available ? 'bg-sky-50 text-sky-700' : 'bg-rose-50 text-rose-700' }}">{{ $product->is_available ? 'Tersedia' : 'Habis' }}</span>
                            </div>
                        </td>
                        <td>
                            <div class="table-actions">
                                <button type="button" wire:click="edit({{ $product->id }})" class="table-action-button">
                                    <svg viewBox="0 0 24 24" class="size-4" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m4 20 4.5-1 10-10a2.1 2.1 0 0 0-3-3l-10 10L4 20ZM13.5 7.5l3 3"/></svg>
                                    Edit
                                </button>
                                <button type="button" wire:click="toggleAvailability({{ $product->id }})" class="table-action-button {{ $product->is_available ? 'text-rose-700 hover:bg-rose-50' : 'text-emerald-700 hover:bg-emerald-50' }}">
                                    {{ $product->is_available ? 'Tandai habis' : 'Aktifkan' }}
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-12 text-center text-sm text-stone-500">Produk tidak ditemukan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div data-pagination>{{ $products->links() }}</div>

    @if($formOpen)
        <div class="fixed inset-0 z-50 grid place-items-end bg-stone-950/40 backdrop-blur-[2px] sm:place-items-center sm:p-4">
            <button type="button" wire:click="resetForm" class="absolute inset-0 size-full" aria-label="Tutup form produk"></button>
            <section class="relative flex max-h-[92dvh] w-full max-w-xl flex-col rounded-t-3xl bg-white shadow-2xl sm:rounded-3xl" role="dialog" aria-modal="true" aria-labelledby="productFormTitle">
                <div class="flex items-start gap-3 border-b border-stone-200 p-5">
                    <div class="grid size-11 shrink-0 place-items-center rounded-xl bg-brand-100 text-brand-700">
                        <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M5 4h14v16H5zM8 8h8M8 12h8M8 16h5"/></svg>
                    </div>
                    <div class="min-w-0 flex-1"><h3 id="productFormTitle" class="font-bold">{{ $editingId ? 'Edit produk' : 'Produk baru' }}</h3><p class="mt-0.5 text-sm text-stone-500">Atur detail, harga, gambar, dan status menu.</p></div>
                    <x-ui.modal-close wire:click="resetForm" label="Tutup form produk" />
                </div>

                <form wire:submit="save" class="scrollbar-thin min-h-0 space-y-4 overflow-y-auto p-5">
                    <div class="grid gap-4 sm:grid-cols-[120px_1fr]">
                        <div>
                            @if($image)
                                <img src="{{ $image->temporaryUrl() }}" alt="Preview gambar" class="aspect-square w-full rounded-2xl object-cover">
                            @elseif($existingImagePath && ! $removeImage)
                                <img src="{{ asset('storage/'.$existingImagePath) }}" alt="Gambar produk" class="aspect-square w-full rounded-2xl object-cover">
                            @else
                                <div class="grid aspect-square w-full place-items-center rounded-2xl bg-amber-100 text-4xl">☕</div>
                            @endif
                        </div>
                        <div class="space-y-2">
                            <label class="field-label">Gambar produk</label>
                            <label class="flex min-h-10 cursor-pointer items-center justify-center rounded-xl border border-dashed border-stone-300 bg-stone-50 px-3 text-sm font-semibold text-stone-600 hover:border-brand-400 hover:bg-brand-50">
                                <input wire:model="image" type="file" accept="image/*" class="sr-only">
                                Pilih gambar
                            </label>
                            <p class="text-xs text-stone-500">JPG, PNG, atau WebP. Maksimal 2 MB. Tanpa gambar memakai visual default POS.</p>
                            @if($existingImagePath || $image)
                                <label class="flex items-center gap-2 text-xs font-semibold text-rose-700"><input wire:model="removeImage" type="checkbox"> Hapus gambar tersimpan</label>
                            @endif
                            @error('image')<p class="text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2">
                        <label class="field-label">Nama produk<input wire:model="name" class="field-input mt-1.5" placeholder="Nama produk"></label>
                        <label class="field-label">SKU<input wire:model="sku" class="field-input mt-1.5" placeholder="SKU"></label>
                    </div>
                    <div>
                        <label class="field-label mb-1.5">Kategori</label>
                        <x-ui.dropdown model="categoryId" :options="$categoryOptions" placeholder="Pilih kategori" />
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <label class="field-label">Harga jual<input wire:model="basePrice" type="number" min="0" class="field-input mt-1.5" placeholder="Harga jual"></label>
                        <label class="field-label">Estimasi modal<input wire:model="costEstimate" type="number" min="0" class="field-input mt-1.5" placeholder="Estimasi modal"></label>
                    </div>
                    <div class="grid gap-2 sm:grid-cols-3">
                        <label class="choice-toggle"><input type="checkbox" wire:model="isActive"> Aktif</label>
                        <label class="choice-toggle"><input type="checkbox" wire:model="isAvailable"> Tersedia</label>
                        <label class="choice-toggle"><input type="checkbox" wire:model="isFavorite"> Favorit</label>
                    </div>
                    @if($errors->any())<div class="rounded-xl bg-rose-50 p-3 text-sm font-semibold text-rose-700">{{ $errors->first() }}</div>@endif
                    <div class="sticky bottom-0 grid grid-cols-2 gap-2 border-t border-stone-200 bg-white pt-4">
                        <button type="button" wire:click="resetForm" class="btn-secondary">Batal</button>
                        <button class="btn-primary" wire:loading.attr="disabled"><span wire:loading.remove wire:target="save">Simpan produk</span><span wire:loading wire:target="save">Menyimpan...</span></button>
                    </div>
                </form>
            </section>
        </div>
    @endif
</div>
