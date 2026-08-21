<!DOCTYPE html>
<html lang="id" class="h-full bg-stone-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#f97316">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="KopiPOS">
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <link rel="apple-touch-icon" href="{{ asset('icons/icon-192.png') }}">
    <title>{{ $title ?? 'KopiPOS' }} · {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="h-full overflow-hidden bg-stone-100 text-stone-900 antialiased">
<div data-sidebar-backdrop class="fixed inset-0 z-40 hidden bg-stone-950/40 backdrop-blur-[2px] lg:hidden"></div>

<div class="flex h-dvh min-h-0 bg-stone-100">
    <aside data-sidebar class="fixed inset-y-0 left-0 z-50 flex w-[286px] -translate-x-full flex-col border-r border-stone-200 bg-[#fafafa] text-stone-900 shadow-2xl transition-transform duration-200 lg:static lg:z-auto lg:translate-x-0 lg:shadow-none" aria-label="Navigasi utama">
        <div class="flex h-20 shrink-0 items-center gap-3 border-b-2 border-brand-500 px-5">
            <a href="{{ route('dashboard') }}" class="grid size-11 place-items-center rounded-2xl bg-brand-500 text-white shadow-lg shadow-brand-950/20" aria-label="Dashboard">
                <svg viewBox="0 0 24 24" class="size-6" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M5 8h11v5.5a5.5 5.5 0 0 1-11 0V8Z"/><path d="M16 10h1.5a2.5 2.5 0 0 1 0 5H16M8 4c0 1 1 1 1 2M12 4c0 1 1 1 1 2"/></svg>
            </a>
            <div class="min-w-0">
                <p class="truncate font-bold tracking-tight">KopiKita</p>
                <p class="truncate text-xs text-stone-500">Coffee Shop Management</p>
            </div>
            <button data-sidebar-close type="button" class="pressable ml-auto grid size-10 place-items-center rounded-xl text-stone-500 hover:bg-brand-50 hover:text-brand-800 lg:hidden" aria-label="Tutup menu">
                <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m6 6 12 12M18 6 6 18"/></svg>
            </button>
        </div>

        <div class="border-b border-stone-200 p-4">
            <div class="rounded-xl border border-brand-200 bg-brand-50 px-3 py-2.5">
                <span class="block text-[10px] font-semibold uppercase tracking-[0.16em] text-stone-500">Mode tampilan</span>
                <span class="mt-0.5 block text-sm font-semibold text-brand-800">{{ auth()->user()->role->value === 'OWNER' ? 'Owner' : (auth()->user()->role->value === 'MANAGER' ? 'Manager' : 'Kasir') }}</span>
            </div>
        </div>

        <nav class="scrollbar-thin min-h-0 flex-1 space-y-1 overflow-y-auto px-3 py-4">
            <p class="px-3 pb-2 text-[11px] font-semibold uppercase tracking-[0.16em] text-stone-500">Workspace</p>

            <a href="{{ route('dashboard') }}" class="admin-nav-link {{ request()->routeIs('dashboard') ? 'admin-nav-link-active' : '' }}">
                <svg viewBox="0 0 24 24" class="size-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 13h6V4H4v9ZM14 20h6v-9h-6v9ZM4 20h6v-3H4v3ZM14 7h6V4h-6v3Z"/></svg><span>Dashboard</span>
            </a>
            <a href="{{ route('pos') }}" class="admin-nav-link {{ request()->routeIs('pos') ? 'admin-nav-link-active' : '' }}">
                <svg viewBox="0 0 24 24" class="size-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 5h16v14H4zM8 9h8M8 13h3M15 13h1"/></svg><span>Point of Sale</span>
            </a>
            <a href="{{ route('orders') }}" class="admin-nav-link {{ request()->routeIs('orders*') ? 'admin-nav-link-active' : '' }}">
                <svg viewBox="0 0 24 24" class="size-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 3h12v18l-3-2-3 2-3-2-3 2V3Z"/><path d="M9 8h6M9 12h6M9 16h3"/></svg><span>Transaksi</span>
            </a>
            <a href="{{ route('shifts') }}" class="admin-nav-link {{ request()->routeIs('shifts') ? 'admin-nav-link-active' : '' }}">
                <svg viewBox="0 0 24 24" class="size-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg><span>Shift</span>
            </a>

            @if(auth()->user()->hasRole('OWNER', 'MANAGER'))
                <a href="{{ route('products') }}" class="admin-nav-link {{ request()->routeIs('products') ? 'admin-nav-link-active' : '' }}">
                    <svg viewBox="0 0 24 24" class="size-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M5 4h14v16H5zM8 8h8M8 12h8M8 16h5"/></svg><span>Menu & Harga</span>
                </a>
                <a href="{{ route('categories') }}" class="admin-nav-link {{ request()->routeIs('categories') ? 'admin-nav-link-active' : '' }}">
                    <svg viewBox="0 0 24 24" class="size-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 4h7v7H4zM13 4h7v7h-7zM4 13h7v7H4zM13 13h7v7h-7z"/></svg><span>Kategori</span>
                </a>
                <a href="{{ route('inventory') }}" class="admin-nav-link {{ request()->routeIs('inventory') ? 'admin-nav-link-active' : '' }}">
                    <svg viewBox="0 0 24 24" class="size-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m4 7 8-4 8 4-8 4-8-4Z"/><path d="m4 7 8 4 8-4M4 12l8 4 8-4M4 17l8 4 8-4"/></svg><span>Inventori</span>
                </a>
                <a href="{{ route('reports.sales') }}" class="admin-nav-link {{ request()->routeIs('reports.*') ? 'admin-nav-link-active' : '' }}">
                    <svg viewBox="0 0 24 24" class="size-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M5 20V10M12 20V4M19 20v-7"/></svg><span>Laporan</span>
                </a>
            @endif

            @if(auth()->user()->hasRole('OWNER'))
                <div class="pt-4">
                    <p class="px-3 pb-2 text-[11px] font-semibold uppercase tracking-[0.16em] text-stone-500">Owner</p>
                    <span class="admin-nav-link cursor-not-allowed opacity-50" title="Modul biaya operasional belum tersedia">
                        <svg viewBox="0 0 24 24" class="size-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 7h16v12H4zM7 7V5h10v2M8 12h8M8 16h4"/></svg><span>Biaya Operasional</span>
                    </span>
                    <a href="{{ route('users') }}" class="admin-nav-link {{ request()->routeIs('users') ? 'admin-nav-link-active' : '' }}">
                        <svg viewBox="0 0 24 24" class="size-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="9" cy="8" r="3"/><path d="M3 20a6 6 0 0 1 12 0M16 5a3 3 0 0 1 0 6M17 14a5 5 0 0 1 4 6"/></svg><span>Pengguna &amp; Akses</span>
                    </a>
                    <a href="{{ route('settings') }}" class="admin-nav-link {{ request()->routeIs('settings') ? 'admin-nav-link-active' : '' }}">
                        <svg viewBox="0 0 24 24" class="size-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="3"/><path d="M19 12a7 7 0 0 0-.1-1l2-1.5-2-3.5-2.4 1a7 7 0 0 0-1.7-1L14.5 3h-5L9 6a7 7 0 0 0-1.7 1L5 6 3 9.5 5.1 11a7 7 0 0 0 0 2L3 14.5 5 18l2.3-1a7 7 0 0 0 1.7 1l.5 3h5l.5-3a7 7 0 0 0 1.7-1l2.3 1 2-3.5-2.1-1.5a7 7 0 0 0 .1-1Z"/></svg><span>Pengaturan</span>
                    </a>
                </div>
            @endif
        </nav>

        <div class="shrink-0 border-t border-stone-200 p-4">
            <div class="flex items-center gap-3 rounded-2xl bg-stone-100 p-3">
                <div class="grid size-10 shrink-0 place-items-center rounded-xl bg-brand-500 text-xs font-bold">{{ strtoupper(substr(auth()->user()->name, 0, 2)) }}</div>
                <div class="min-w-0 flex-1"><p class="truncate text-sm font-semibold">{{ auth()->user()->name }}</p><p class="truncate text-xs text-stone-500">{{ ucfirst(strtolower(auth()->user()->role->value)) }}</p></div>
                <form method="POST" action="{{ route('logout') }}">@csrf<button class="pressable grid size-9 place-items-center rounded-xl text-stone-500 hover:bg-brand-50 hover:text-brand-800" aria-label="Keluar"><svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M10 5H5v14h5M14 8l4 4-4 4M8 12h10"/></svg></button></form>
            </div>
        </div>
    </aside>

    <div class="flex min-w-0 flex-1 flex-col">
        <header class="z-30 flex min-h-20 shrink-0 items-center gap-3 border-b border-stone-200 bg-white/95 px-4 backdrop-blur sm:px-6 lg:px-8">
            <button data-sidebar-open type="button" class="pressable grid size-11 shrink-0 place-items-center rounded-xl border border-stone-200 bg-white text-stone-600 hover:bg-stone-50 lg:hidden" aria-label="Buka menu">
                <svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
            </button>
            <div class="min-w-0">
                <div class="flex items-center gap-2"><h1 class="truncate text-lg font-bold tracking-tight sm:text-xl">{{ $title ?? 'Dashboard' }}</h1><span class="hidden rounded-full {{ auth()->user()->hasRole('OWNER') ? 'bg-brand-100 text-brand-800' : 'bg-sky-100 text-sky-800' }} px-2.5 py-1 text-[11px] font-bold sm:inline-flex">{{ auth()->user()->role->value }}</span></div>
                <p class="truncate text-xs text-stone-500 sm:text-sm">{{ auth()->user()->hasRole('OWNER') ? 'Selamat pagi, berikut performa dan kondisi toko hari ini.' : 'Selamat pagi, berikut ringkasan operasional toko hari ini.' }}</p>
            </div>
            <div class="ml-auto flex items-center gap-2">
                <a href="{{ route('profile') }}" class="pressable grid size-11 place-items-center rounded-xl border border-brand-200 bg-brand-50 text-brand-700 hover:bg-brand-100" aria-label="Buka profil" title="Profil Saya"><svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="8" r="3.5"/><path d="M5 20a7 7 0 0 1 14 0"/></svg></a>
                <a href="{{ route('dashboard') }}" class="pressable hidden min-h-11 items-center gap-2 rounded-xl border border-stone-200 bg-white px-3 text-sm font-semibold text-stone-600 hover:bg-stone-50 sm:inline-flex"><svg viewBox="0 0 24 24" class="size-4" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 13h6V4H4v9ZM14 20h6v-9h-6v9ZM4 20h6v-3H4v3ZM14 7h6V4h-6v3Z"/></svg>Dashboard</a>
                <a href="{{ route('dashboard') }}" class="pressable grid size-11 place-items-center rounded-xl border border-stone-200 bg-white text-stone-600 hover:bg-stone-50 sm:hidden" aria-label="Buka dashboard"><svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 13h6V4H4v9ZM14 20h6v-9h-6v9ZM4 20h6v-3H4v3ZM14 7h6V4h-6v3Z"/></svg></a>
                @if(auth()->user()->hasRole('OWNER', 'MANAGER'))
                    <a href="{{ route('inventory') }}" class="pressable grid size-11 place-items-center rounded-xl border border-stone-200 bg-white text-stone-600 hover:bg-stone-50" aria-label="Buka inventori" title="Inventori"><svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m4 7 8-4 8 4-8 4-8-4Z"/><path d="m4 7 8 4 8-4M4 12l8 4 8-4M4 17l8 4 8-4"/></svg></a>
                @else
                    <a href="{{ route('shifts') }}" class="pressable grid size-11 place-items-center rounded-xl border border-stone-200 bg-white text-stone-600 hover:bg-stone-50" aria-label="Buka shift" title="Shift"><svg viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg></a>
                @endif
            </div>
        </header>

        <main class="scrollbar-thin min-h-0 flex-1 overflow-y-auto">
            @if(session('success'))<div class="fixed right-4 top-4 z-[100] max-w-sm rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-900 shadow-lg">{{ session('success') }}</div>@endif
            <div class="{{ ($title ?? '') === 'Dashboard' ? '' : 'p-4 sm:p-6 lg:p-8' }}">{{ $slot }}</div>
        </main>
    </div>
</div>
@livewireScripts
</body>
</html>
