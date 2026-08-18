<?php

namespace App\Livewire;

use App\Models\StoreSetting;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class SettingsManager extends Component
{
    use WithFileUploads;

    public array $form = [];

    public $qrisImage;

    public function mount(): void
    {
        $store = StoreSetting::current();
        $this->form = $store->only(['store_name', 'address', 'phone', 'currency', 'timezone', 'tax_enabled', 'tax_rate', 'receipt_footer', 'transaction_prefix', 'qris_enabled', 'qris_image_path']);
    }

    public function save(): void
    {
        $data = $this->validate([
            'form.store_name' => ['required', 'string', 'max:150'], 'form.address' => ['nullable', 'string'],
            'form.phone' => ['nullable', 'string', 'max:50'], 'form.currency' => ['required', 'in:IDR'],
            'form.timezone' => ['required', 'timezone'], 'form.tax_enabled' => ['boolean'],
            'form.tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'form.receipt_footer' => ['nullable', 'string'],
            'form.transaction_prefix' => ['required', 'string', 'max:10'], 'form.qris_enabled' => ['boolean'],
            'qrisImage' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);
        $store = StoreSetting::current();
        if ($this->qrisImage) {
            if ($store->qris_image_path) {
                Storage::disk('public')->delete($store->qris_image_path);
            }
            $data['form']['qris_image_path'] = $this->qrisImage->store('qris', 'public');
        }
        $store->update($data['form'] + ['service_charge_rate' => 0, 'allow_negative_stock' => false]);
        $this->form['qris_image_path'] = $store->fresh()->qris_image_path;
        $this->reset('qrisImage');
        session()->flash('success', 'Pengaturan toko tersimpan.');
    }

    public function render()
    {
        return view('livewire.settings-manager')->layout('layouts.app', ['title' => 'Pengaturan Toko']);
    }
}
