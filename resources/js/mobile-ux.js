const Capacitor = window.Capacitor || {};
const native = Boolean(Capacitor.isNativePlatform?.());
if (native) document.documentElement.classList.add('capacitor-native');

const haptic = async (style = 'LIGHT') => {
    try { await Capacitor.Plugins?.Haptics?.impact({ style }); } catch (_) { /* optional plugin */ }
};

const toast = (message, type = 'info') => {
    let host = document.querySelector('[data-mobile-toast-host]');
    if (!host) {
        host = document.createElement('div');
        host.dataset.mobileToastHost = '';
        host.className = 'mobile-toast-host';
        document.body.appendChild(host);
    }
    const item = document.createElement('div');
    item.className = `mobile-toast mobile-toast-${type}`;
    item.setAttribute('role', type === 'error' ? 'alert' : 'status');
    item.textContent = message;
    host.appendChild(item);
    requestAnimationFrame(() => item.classList.add('is-visible'));
    window.setTimeout(() => {
        item.classList.remove('is-visible');
        window.setTimeout(() => item.remove(), 220);
    }, type === 'error' ? 4200 : 2800);
};

const closeTopLayer = () => {
    const close = document.querySelector('[data-close-modal], [data-modal-close]');
    if (close) { close.click(); return true; }
    const cartClose = document.querySelector('[aria-label="Tutup keranjang"]');
    if (cartClose) { cartClose.click(); return true; }
    return false;
};

const init = () => {
    if (window.__kopiposMobileUX) return;
    window.__kopiposMobileUX = true;
    window.MobileUX = { native, toast, haptic };

    const syncFullscreenControl = (enabled) => {
        document.querySelectorAll('[data-fullscreen-icon]').forEach((el) => { el.textContent = enabled ? '⤢' : '⛶'; });
        document.querySelectorAll('[data-fullscreen-toggle]').forEach((el) => {
            el.setAttribute('aria-label', enabled ? 'Matikan layar penuh' : 'Aktifkan layar penuh');
            el.title = enabled ? 'Keluar layar penuh' : 'Layar penuh';
        });
    };
    const initFullscreenControl = async () => {
        const plugin = Capacitor.Plugins?.SystemUi;
        if (!native || !plugin) return;
        const current = await plugin.getFullscreen();
        syncFullscreenControl(Boolean(current.fullscreen));
        document.querySelectorAll('[data-fullscreen-toggle]').forEach((button) => {
            if (button.dataset.fullscreenBound) return;
            button.dataset.fullscreenBound = '1';
            button.addEventListener('click', async () => {
                const next = !(await plugin.getFullscreen()).fullscreen;
                await plugin.setFullscreen({ enabled: next });
                syncFullscreenControl(next);
                haptic('MEDIUM');
            });
        });
    };
    initFullscreenControl();

    document.addEventListener('click', (event) => {
        const target = event.target.closest('button, a, [role="button"]');
        if (target && !target.hasAttribute('disabled') && !target.getAttribute('aria-disabled')) haptic('LIGHT');
        if (event.target.closest('[data-product-card]')) haptic('MEDIUM');
    }, { passive: true });

    window.addEventListener('printer-notice', (event) => toast(event.detail?.message || 'Printer diperbarui.', event.detail?.type || 'info'));
    document.addEventListener('livewire:navigated', () => document.documentElement.classList.toggle('capacitor-native', native));

    const appPlugin = Capacitor.Plugins?.App;
    if (appPlugin?.addListener) {
        appPlugin.addListener('backButton', ({ canGoBack }) => {
            if (closeTopLayer()) return;
            if (canGoBack) window.history.back();
        });
    }
};

if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init, { once: true });
else init();

export { native, toast, haptic };
window.MobileUX = window.MobileUX || { native, toast, haptic };
