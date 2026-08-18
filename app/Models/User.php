<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $guarded = [];

    protected $hidden = [
        'password',
        'pin',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'is_active' => 'boolean',
        ];
    }

    public function shifts()
    {
        return $this->hasMany(Shift::class, 'cashier_id');
    }

    public function activeShift(): ?Shift
    {
        return $this->shifts()->where('status', 'OPEN')->latest('opened_at')->first();
    }

    public function hasRole(UserRole|string ...$roles): bool
    {
        $values = array_map(fn (UserRole|string $role) => $role instanceof UserRole ? $role->value : $role, $roles);

        return in_array($this->role->value, $values, true);
    }
}
