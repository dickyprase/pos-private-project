<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login · KopiPOS</title>
    @vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body class="min-h-screen bg-stone-100 text-stone-900 antialiased">
<main class="grid min-h-screen place-items-center p-4">
    <div class="w-full max-w-xs">
        <div class="mb-4 flex items-center gap-2">
            <span class="grid size-9 place-items-center rounded-md bg-brand-600 text-sm font-black text-white">K</span>
            <div>
                <b class="block text-sm">KopiPOS</b>
                <span class="text-[11px] text-stone-500">Login kasir</span>
            </div>
        </div>
        <section class="rounded-lg border border-stone-200 bg-white p-4">
            <h1 class="text-lg font-bold tracking-tight">Masuk</h1>
            <p class="mt-0.5 text-xs text-stone-500">Pakai akun owner/manager/kasir.</p>
            <form method="POST" action="{{ route('login.store') }}" class="mt-4 space-y-3">@csrf
                <label class="field-label">Username / email
                    <input class="field-input mt-1" name="login" value="{{ old('login') }}" required autofocus autocomplete="username" placeholder="owner">
                </label>
                <label class="field-label">Password
                    <input class="field-input mt-1" type="password" name="password" required autocomplete="current-password">
                </label>
                <label class="flex min-h-8 items-center gap-2 text-xs text-stone-600">
                    <input type="checkbox" name="remember" class="size-3.5 rounded border-stone-300"> Ingat sesi
                </label>
                @error('login')
                    <div class="rounded-md bg-rose-50 px-2.5 py-2 text-xs text-rose-700">{{ $message }}</div>
                @enderror
                <button class="btn-primary w-full">Masuk</button>
            </form>
        </section>
    </div>
</main>
</body>
</html>
