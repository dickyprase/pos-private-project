<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#f97316">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="KopiPOS">
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <link rel="apple-touch-icon" href="{{ asset('icons/icon-192.png') }}">
    <title>Masuk · KopiPOS</title>
    @vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#fafaf9] text-stone-900 antialiased">
<main class="relative isolate grid min-h-screen overflow-hidden lg:grid-cols-[1.08fr_.92fr]">
    <div class="pointer-events-none absolute inset-0 -z-10 overflow-hidden">
        <div class="absolute -left-24 -top-24 size-72 rounded-full bg-orange-200/45 blur-3xl"></div>
        <div class="absolute -bottom-32 right-0 size-80 rounded-full bg-amber-100/60 blur-3xl"></div>
    </div>

    <section class="relative hidden overflow-hidden bg-stone-950 p-10 text-white lg:flex lg:flex-col lg:justify-between xl:p-14">
        <div class="absolute inset-0 opacity-80" style="background: radial-gradient(circle at 18% 18%, rgba(249,115,22,.38), transparent 34%), radial-gradient(circle at 82% 78%, rgba(251,191,36,.18), transparent 30%);"></div>
        <div class="relative flex items-center gap-3">
            <span class="grid size-11 place-items-center rounded-2xl bg-orange-500 text-lg font-black shadow-lg shadow-orange-950/40">K</span>
            <div>
                <p class="font-bold tracking-tight">KopiPOS</p>
                <p class="text-xs text-stone-400">Operasional kedai, satu tempat</p>
            </div>
        </div>

        <div class="relative max-w-xl">
            <span class="inline-flex rounded-full border border-white/10 bg-white/5 px-3 py-1.5 text-xs font-semibold text-orange-200">Cepat. Rapi. Siap melayani.</span>
            <h1 class="mt-6 text-4xl font-black leading-tight tracking-[-0.04em] xl:text-5xl">Kelola transaksi tanpa bikin antrean menunggu.</h1>
            <p class="mt-5 max-w-lg text-sm leading-7 text-stone-300">Akses kasir, riwayat transaksi, stok, dan laporan dari ruang kerja KopiPOS.</p>
        </div>

        <p class="relative text-xs text-stone-500">KopiPOS · Sistem operasional kedai</p>
    </section>

    <section class="flex min-h-screen items-center justify-center px-5 py-8 sm:px-8 lg:px-12">
        <div class="w-full max-w-md">
            <div class="mb-8 flex items-center justify-between lg:hidden">
                <div class="flex items-center gap-3">
                    <span class="grid size-11 place-items-center rounded-2xl bg-orange-500 text-lg font-black text-white shadow-lg shadow-orange-200">K</span>
                    <div>
                        <p class="font-bold tracking-tight">KopiPOS</p>
                        <p class="text-xs text-stone-500">Ruang kerja kedai</p>
                    </div>
                </div>
                <button type="button" data-install-app hidden class="rounded-xl border border-orange-200 bg-orange-50 px-3 py-2 text-xs font-bold text-orange-700 hover:bg-orange-100 focus:outline-none focus:ring-4 focus:ring-orange-100">Install aplikasi</button>
            </div>

            <div class="rounded-[1.75rem] border border-stone-200/80 bg-white/95 p-6 shadow-[0_24px_70px_-30px_rgba(28,25,23,.35)] backdrop-blur sm:p-8">
                <div class="mb-7">
                    <span class="inline-flex rounded-full bg-orange-50 px-3 py-1 text-[11px] font-bold uppercase tracking-[.16em] text-orange-700">Selamat datang</span>
                    <h2 class="mt-4 text-2xl font-black tracking-[-0.03em] text-stone-950">Masuk ke ruang kerja</h2>
                    <p class="mt-2 text-sm leading-6 text-stone-500">Gunakan akun yang sudah terdaftar untuk melanjutkan.</p>
                </div>

                <form method="POST" action="{{ route('login.store') }}" class="space-y-5">
                    @csrf
                    <div>
                        <label for="login" class="mb-2 block text-sm font-bold text-stone-700">Username atau email</label>
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 left-0 grid w-12 place-items-center text-stone-400" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" class="size-5" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0ZM4.5 21a7.5 7.5 0 0 1 15 0"/></svg>
                            </span>
                            <input id="login" class="h-12 w-full rounded-xl border border-stone-200 bg-stone-50/70 pl-12 pr-4 text-sm font-medium outline-none transition placeholder:text-stone-400 focus:border-orange-400 focus:bg-white focus:ring-4 focus:ring-orange-100" name="login" value="{{ old('login') }}" required autofocus autocomplete="username" inputmode="email" placeholder="Masukkan username atau email">
                        </div>
                    </div>

                    <div>
                        <label for="password" class="mb-2 block text-sm font-bold text-stone-700">Password</label>
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 left-0 grid w-12 place-items-center text-stone-400" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" class="size-5" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M7 10V8a5 5 0 0 1 10 0v2m-9 0h8a2 2 0 0 1 2 2v7H6v-7a2 2 0 0 1 2-2Z"/></svg>
                            </span>
                            <input id="password" class="h-12 w-full rounded-xl border border-stone-200 bg-stone-50/70 pl-12 pr-12 text-sm font-medium outline-none transition placeholder:text-stone-400 focus:border-orange-400 focus:bg-white focus:ring-4 focus:ring-orange-100" type="password" name="password" required autocomplete="current-password" placeholder="Masukkan password">
                            <button type="button" data-password-toggle aria-label="Tampilkan password" class="absolute inset-y-0 right-0 grid w-12 place-items-center rounded-r-xl text-stone-400 transition hover:text-orange-600 focus:outline-none focus:ring-4 focus:ring-inset focus:ring-orange-100">
                                <svg data-eye-open viewBox="0 0 24 24" fill="none" class="size-5" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"/><circle cx="12" cy="12" r="2.5"/></svg>
                            </button>
                        </div>
                    </div>

                    <label class="flex w-fit cursor-pointer items-center gap-2.5 text-sm text-stone-600">
                        <input type="checkbox" name="remember" class="size-4 rounded border-stone-300 text-orange-600 focus:ring-orange-500"> Ingat sesi di perangkat ini
                    </label>

                    @error('login')
                        <div role="alert" class="rounded-xl border border-rose-100 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700">{{ $message }}</div>
                    @enderror

                    <button class="flex h-12 w-full items-center justify-center rounded-xl bg-orange-500 px-4 text-sm font-black text-white shadow-lg shadow-orange-200 transition hover:bg-orange-600 focus:outline-none focus:ring-4 focus:ring-orange-200 active:scale-[.99]">Masuk ke KopiPOS</button>
                </form>
            </div>

            <div class="mt-5 hidden justify-center lg:flex">
                <button type="button" data-install-app hidden class="rounded-xl border border-orange-200 bg-orange-50 px-4 py-2.5 text-xs font-bold text-orange-700 transition hover:bg-orange-100 focus:outline-none focus:ring-4 focus:ring-orange-100">Install aplikasi KopiPOS</button>
            </div>
            <p class="mt-6 text-center text-xs leading-5 text-stone-400">Akses terbatas untuk pengguna terdaftar.</p>
        </div>
    </section>
</main>
<script>
document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-password-toggle]');
    if (!button) return;
    const input = document.getElementById('password');
    const visible = input.type === 'text';
    input.type = visible ? 'password' : 'text';
    button.setAttribute('aria-label', visible ? 'Tampilkan password' : 'Sembunyikan password');
});
</script>
</body>
</html>
