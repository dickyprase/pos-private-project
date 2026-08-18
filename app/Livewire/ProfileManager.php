<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Component;

class ProfileManager extends Component
{
    public string $name = '';

    public string $email = '';

    public string $currentPassword = '';

    public string $password = '';

    public string $passwordConfirmation = '';

    public function mount(): void
    {
        $this->name = auth()->user()->name;
        $this->email = auth()->user()->email ?? '';
    }

    public function save(): void
    {
        $user = auth()->user();
        $data = $this->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['nullable', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'currentPassword' => ['nullable', 'required_with:password', 'current_password'],
            'password' => ['nullable', 'min:8', 'same:passwordConfirmation'],
            'passwordConfirmation' => ['nullable', 'required_with:password'],
        ]);
        $update = ['name' => $data['name'], 'email' => $data['email'] ?: null];
        if ($data['password'] ?? '') {
            $update['password'] = Hash::make($data['password']);
        }
        $user->update($update);
        $this->reset(['currentPassword', 'password', 'passwordConfirmation']);
        session()->flash('success', 'Profil berhasil diperbarui.');
    }

    public function render()
    {
        return view('livewire.profile-manager')->layout('layouts.app', ['title' => 'Profil Saya']);
    }
}
