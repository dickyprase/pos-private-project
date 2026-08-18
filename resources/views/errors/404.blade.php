<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>404 · KopiPOS</title>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-stone-100 text-stone-900 antialiased">
<main class="grid min-h-screen place-items-center p-4">
    <div class="card w-full max-w-sm p-5 text-center">
        <p class="text-[10px] font-bold uppercase tracking-wide text-stone-500">404</p>
        <h1 class="mt-1 text-xl font-bold">Halaman tidak ada</h1>
        <p class="mt-2 text-sm text-stone-500">URL yang dibuka tidak ditemukan.</p>
        <a href="{{ url('/dashboard') }}" class="btn-primary mt-4 inline-flex">Dashboard</a>
    </div>
</main>
</body>
</html>
