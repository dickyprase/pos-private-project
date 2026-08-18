<?php

namespace App\Livewire;

use App\Models\Category;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class CategoryManager extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    public ?int $editingId = null;

    public string $name = '';

    public int $sortOrder = 0;

    public bool $isActive = true;

    public bool $formOpen = false;

    public function updatedSearch(): void
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
        $category = Category::findOrFail($id);
        $this->editingId = $category->id;
        $this->name = $category->name;
        $this->sortOrder = $category->sort_order;
        $this->isActive = $category->is_active;
        $this->formOpen = true;
    }

    public function save(): void
    {
        $this->authorizeManager();
        $data = $this->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('categories', 'name')->ignore($this->editingId)],
            'sortOrder' => ['required', 'integer', 'min:0'],
            'isActive' => ['boolean'],
        ]);
        Category::updateOrCreate(['id' => $this->editingId], [
            'name' => $data['name'],
            'slug' => Str::slug($data['name']).($this->editingId ? '-'.$this->editingId : ''),
            'sort_order' => $data['sortOrder'],
            'is_active' => $data['isActive'],
        ]);
        $this->resetForm();
        session()->flash('success', 'Kategori tersimpan.');
    }

    public function toggleActive(int $id): void
    {
        $this->authorizeManager();
        $category = Category::findOrFail($id);
        $category->update(['is_active' => ! $category->is_active]);
    }

    public function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'sortOrder', 'formOpen']);
        $this->isActive = true;
        $this->resetErrorBag();
    }

    private function authorizeManager(): void
    {
        abort_unless(auth()->user()?->hasRole('OWNER', 'MANAGER'), 403);
    }

    public function render()
    {
        return view('livewire.category-manager', [
            'categories' => Category::query()->withCount('products')
                ->when($this->search, fn ($query) => $query->where('name', 'like', "%{$this->search}%"))
                ->when($this->statusFilter !== '', fn ($query) => $query->where('is_active', $this->statusFilter === 'active'))
                ->orderBy('sort_order')->orderBy('name')->paginate(12),
        ])->layout('layouts.app', ['title' => 'Kategori']);
    }
}
