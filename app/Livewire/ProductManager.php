<?php

namespace App\Livewire;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class ProductManager extends Component
{
    use WithFileUploads, WithPagination;

    public bool $formOpen = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $sku = '';

    public int $categoryId = 0;

    public int $basePrice = 0;

    public int $costEstimate = 0;

    public bool $isActive = true;

    public bool $isAvailable = true;

    public bool $isFavorite = false;

    public $image = null;

    public ?string $existingImagePath = null;

    public bool $removeImage = false;

    public string $search = '';

    public string $categoryFilter = '';

    public string $statusFilter = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedCategoryFilter(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        $this->authorizeManager();
        $this->resetForm();
        $this->formOpen = true;
    }

    public function edit(int $id): void
    {
        $this->authorizeManager();
        $product = Product::findOrFail($id);
        $this->editingId = $id;
        $this->name = $product->name;
        $this->sku = $product->sku;
        $this->categoryId = $product->category_id;
        $this->basePrice = $product->base_price;
        $this->costEstimate = $product->cost_estimate;
        $this->isActive = $product->is_active;
        $this->isAvailable = $product->is_available;
        $this->isFavorite = $product->is_favorite;
        $this->existingImagePath = $product->image_path;
        $this->removeImage = false;
        $this->formOpen = true;
    }

    public function save(): void
    {
        $this->authorizeManager();
        $data = $this->validate([
            'name' => ['required', 'string', 'max:150'], 'sku' => ['required', 'string', 'max:80', 'unique:products,sku,'.($this->editingId ?: 'NULL')],
            'categoryId' => ['required', 'exists:categories,id'], 'basePrice' => ['required', 'integer', 'min:0'],
            'costEstimate' => ['required', 'integer', 'min:0'], 'isActive' => ['boolean'],
            'isAvailable' => ['boolean'], 'isFavorite' => ['boolean'],
            'image' => ['nullable', 'image', 'max:2048'], 'removeImage' => ['boolean'],
        ]);

        $product = $this->editingId ? Product::findOrFail($this->editingId) : null;
        $imagePath = $product?->image_path;
        if ($data['removeImage'] && $imagePath) {
            Storage::disk('public')->delete($imagePath);
            $imagePath = null;
        }
        if ($this->image) {
            if ($imagePath) {
                Storage::disk('public')->delete($imagePath);
            }
            $imagePath = $this->image->store('products', 'public');
        }

        Product::updateOrCreate(['id' => $this->editingId], [
            'category_id' => $data['categoryId'], 'name' => $data['name'],
            'slug' => Str::slug($data['name']).($this->editingId ? '-'.$this->editingId : '-'.Str::lower(Str::random(4))),
            'sku' => strtoupper($data['sku']), 'base_price' => $data['basePrice'],
            'cost_estimate' => $data['costEstimate'], 'is_active' => $data['isActive'],
            'is_available' => $data['isAvailable'], 'is_favorite' => $data['isFavorite'],
            'image_path' => $imagePath,
        ]);
        $this->resetForm();
        session()->flash('success', 'Produk tersimpan.');
    }

    public function toggleAvailability(int $id): void
    {
        $this->authorizeManager();
        $product = Product::findOrFail($id);
        $product->update(['is_available' => ! $product->is_available]);
    }

    public function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'sku', 'categoryId', 'basePrice', 'costEstimate', 'isFavorite', 'image', 'existingImagePath', 'removeImage', 'formOpen']);
        $this->isActive = $this->isAvailable = true;
        $this->resetErrorBag();
    }

    private function authorizeManager(): void
    {
        abort_unless(auth()->user()?->hasRole('OWNER', 'MANAGER'), 403);
    }

    public function render()
    {
        return view('livewire.product-manager', [
            'categories' => Category::where('is_active', true)->orderBy('sort_order')->get(),
            'products' => Product::with('category')
                ->when($this->search, fn ($q) => $q->where(fn ($x) => $x->where('name', 'like', "%{$this->search}%")->orWhere('sku', 'like', "%{$this->search}%")))
                ->when($this->categoryFilter, fn ($q) => $q->where('category_id', $this->categoryFilter))
                ->when($this->statusFilter === 'active', fn ($q) => $q->where('is_active', true))
                ->when($this->statusFilter === 'inactive', fn ($q) => $q->where('is_active', false))
                ->latest()->paginate(12),
        ])->layout('layouts.app', ['title' => 'Menu Produk']);
    }
}
