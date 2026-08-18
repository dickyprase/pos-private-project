<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>403 · KopiPOS</title>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-stone-100 text-stone-900 antialiased">
<main class="grid min-h-screen place-items-center p-4">
    <div class="card w-full max-w-sm p-5 text-center">
        <p class="text-[10px] font-bold uppercase tracking-wide text-rose-600">403</p>
        <h1 class="mt-1 text-xl font-bold">Akses ditolak</h1>
        <p class="mt-2 text-sm text-stone-500">
            Role kamu tidak punya izin ke halaman ini.
            Minta owner/manager jika butuh akses.
        </p>
        <div class="mt-4 grid gap-2">
            @auth
                <a href="{{ route('dashboard') }}" class="btn-primary">Ke Dashboard</a>
                <a href="{{ route('pos') }}" class="btn-secondary">Ke POS</a>
            @else
                <a href="{{ route('login') }}" class="btn-primary">Login</a>
            @endauth
        </div>
    </div>
</main>
</body>
</html>
