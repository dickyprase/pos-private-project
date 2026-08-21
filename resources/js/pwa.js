const registerPwa = () => {
    if (!('serviceWorker' in navigator) || !window.isSecureContext) return;

    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js', { scope: '/' })
            .catch((error) => console.warn('KopiPOS service worker registration failed', error));
    });
};

registerPwa();
