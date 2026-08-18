<?php

namespace App\Livewire;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class UserManager extends Component
{
    use WithPagination;

    public string $search = '';

    public string $roleFilter = '';

    public string $statusFilter = '';

    public string $name = '';

    public string $username = '';

    public string $email = '';

    public string $password = '';

    public string $role = 'CASHIER';

    public bool $editOpen = false;

    public ?int $editingId = null;

    public string $editName = '';

    public string $editUsername = '';

    public string $editEmail = '';

    public string $editRole = 'CASHIER';

    public bool $editIsActive = true;

    public string $newPassword = '';

    public string $newPasswordConfirmation = '';

    public function createUser(): void
    {
        $this->authorizeOwner();
        $data = $this->validate([
            'name' => ['required', 'string', 'max:150'], 'username' => ['required', 'alpha_dash', 'unique:users,username'],
            'email' => ['nullable', 'email', 'unique:users,email'], 'password' => ['required', 'min:8'],
            'role' => ['required', 'in:OWNER,MANAGER,CASHIER'],
        ]);
        User::create([...$data, 'email' => $data['email'] ?: null, 'password' => Hash::make($data['password']), 'role' => UserRole::from($data['role']), 'is_active' => true]);
        $this->reset(['name', 'username', 'email', 'password']);
        $this->role = 'CASHIER';
        session()->flash('success', 'Pengguna dibuat.');
    }

    public function toggleActive(int $id): void
    {
        $this->authorizeOwner();
        abort_if($id === auth()->id(), 422, 'Tidak dapat menonaktifkan akun sendiri.');
        $user = User::findOrFail($id);
        $user->update(['is_active' => ! $user->is_active]);
    }

    public function editUser(int $id): void
    {
        $this->authorizeOwner();
        $user = User::findOrFail($id);
        $this->editingId = $user->id;
        $this->editName = $user->name;
        $this->editUsername = $user->username;
        $this->editEmail = $user->email ?? '';
        $this->editRole = $user->role->value;
        $this->editIsActive = $user->is_active;
        $this->newPassword = $this->newPasswordConfirmation = '';
        $this->editOpen = true;
        $this->resetErrorBag();
    }

    public function saveUser(): void
    {
        $this->authorizeOwner();
        $user = User::findOrFail($this->editingId);
        if ((int) $user->id === (int) auth()->id() && ($this->editRole !== UserRole::OWNER->value || ! $this->editIsActive)) {
            if ($this->editRole !== UserRole::OWNER->value) {
                $this->addError('editRole', 'Owner tidak dapat menurunkan role akun sendiri.');
            }
            if (! $this->editIsActive) {
                $this->addError('editIsActive', 'Owner tidak dapat menonaktifkan akun sendiri.');
            }

            return;
        }
        $data = $this->validate([
            'editName' => ['required', 'string', 'max:150'],
            'editUsername' => ['required', 'alpha_dash', Rule::unique('users', 'username')->ignore($user->id)],
            'editEmail' => ['nullable', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'editRole' => ['required', 'in:OWNER,MANAGER,CASHIER'],
            'editIsActive' => ['boolean'],
            'newPassword' => ['nullable', 'min:8', 'same:newPasswordConfirmation'],
            'newPasswordConfirmation' => ['nullable', 'required_with:newPassword'],
        ]);

        $update = ['name' => $data['editName'], 'username' => $data['editUsername'], 'email' => $data['editEmail'] ?: null, 'role' => UserRole::from($data['editRole']), 'is_active' => $data['editIsActive']];
        if ($data['newPassword'] ?? '') {
            $update['password'] = Hash::make($data['newPassword']);
        }
        $user->update($update);
        $this->closeEdit();
        session()->flash('success', 'Pengguna diperbarui.');
    }

    public function closeEdit(): void
    {
        $this->reset(['editOpen', 'editingId', 'editName', 'editUsername', 'editEmail', 'newPassword', 'newPasswordConfirmation']);
        $this->editRole = 'CASHIER';
        $this->editIsActive = true;
        $this->resetErrorBag();
    }

    private function authorizeOwner(): void
    {
        abort_unless(auth()->user()?->hasRole('OWNER'), 403);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedRoleFilter(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        return view('livewire.user-manager', ['users' => User::query()
            ->when($this->search, fn ($q) => $q->where(fn ($x) => $x->where('name', 'like', "%{$this->search}%")->orWhere('username', 'like', "%{$this->search}%")->orWhere('email', 'like', "%{$this->search}%")))
            ->when($this->roleFilter, fn ($q) => $q->where('role', $this->roleFilter))
            ->when($this->statusFilter !== '', fn ($q) => $q->where('is_active', $this->statusFilter === 'active'))
            ->orderBy('name')->paginate(12)])->layout('layouts.app', ['title' => 'Pengguna']);
    }
}
