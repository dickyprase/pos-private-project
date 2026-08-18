<?php

namespace App\Actions\Auth;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class AuthenticateUser
{
    public function handle(string $login, string $password, Request $request): User
    {
        $key = 'login:'.strtolower($login).'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['login' => 'Terlalu banyak percobaan. Coba lagi dalam satu menit.']);
        }
        $user = User::query()->where('is_active', true)
            ->where(fn ($query) => $query->where('username', $login)->orWhere('email', $login))->first();
        if (! $user || ! Hash::check($password, $user->password)) {
            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages(['login' => 'Username/email atau password salah.']);
        }
        RateLimiter::clear($key);
        $user->update(['last_login_at' => now()]);
        AuditLog::create(['user_id' => $user->id, 'action' => 'LOGIN', 'ip_address' => $request->ip(), 'user_agent' => $request->userAgent(), 'created_at' => now()]);
        return $user;
    }
}
