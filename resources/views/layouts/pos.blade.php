<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#f97316">
    <title>{{ $title ?? 'POS' }} · KopiPOS</title>
    @vite(['resources/css/app.css','resources/js/app.js'])
    @livewireStyles
</head>
<body class="h-dvh overflow-hidden bg-stone-100 text-stone-900 antialiased">
    {{ $slot }}
    @livewireScripts
    <script>
        const labels = { checking: 'Memeriksa printer tersimpan...', connected: 'Terhubung', connecting: 'Menghubungkan…', disconnected: 'Terputus', 'saved-disconnected': 'Terputus', 'saved-unavailable': 'Terputus', 'no-permission': 'Belum ada printer' };
        document.addEventListener('click', async event => {
            if (event.target.closest('[data-printer-pair]')) {
                try { await window.PrinterManager.pairNewPrinter(); }
                catch (error) { window.dispatchEvent(new CustomEvent('printer-notice', { detail: { message: error.message, type: 'error' } })); }
            }
            if (event.target.closest('[data-printer-forget]')) {
                try { await window.PrinterManager.forgetPrinter(); await window.PrinterManager.pairNewPrinter(); }
                catch (error) { window.dispatchEvent(new CustomEvent('printer-notice', { detail: { message: error.message, type: 'error' } })); }
            }
        });
        const bindPrinterUi = async () => {
            if (!window.PrinterManager) return;
            window.PrinterManager.onStatusChange((status, name, hasSavedDevice) => {
                const savedButOffline = hasSavedDevice && status !== 'connected';
                const text = status === 'connected' ? `Terhubung — ${name}` : savedButOffline ? `Terputus — ${name}` : labels[status];
                document.querySelectorAll('[data-printer-status]').forEach(el => el.textContent = text);
                document.querySelectorAll('[data-printer-dot]').forEach(el => {
                    el.classList.toggle('bg-emerald-500', status === 'connected');
                    el.classList.toggle('bg-orange-500', savedButOffline);
                    el.classList.toggle('bg-red-500', !hasSavedDevice);
                });
                document.querySelectorAll('[data-printer-forget]').forEach(el => el.classList.toggle('hidden', !hasSavedDevice));
            });
            await window.PrinterManager.init();
        };
        document.addEventListener('DOMContentLoaded', bindPrinterUi);
        window.addEventListener('printer-notice', event => { if (window.MobileUX?.toast) window.MobileUX.toast(event.detail.message, event.detail.type || 'info'); else alert(event.detail.message); });
    </script>
</body>
</html>
