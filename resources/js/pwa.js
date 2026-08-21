const registerPwa = () => {
    if (!('serviceWorker' in navigator) || !window.isSecureContext) return;

    let installPrompt = null;
    const installButtons = () => document.querySelectorAll('[data-install-app]');
    const syncInstallButtons = (visible) => {
        installButtons().forEach((button) => { button.hidden = !visible; });
    };

    window.addEventListener('beforeinstallprompt', (event) => {
        event.preventDefault();
        installPrompt = event;
        syncInstallButtons(true);
    });

    document.addEventListener('click', async (event) => {
        const button = event.target.closest?.('[data-install-app]');
        if (!button || !installPrompt) return;
        await installPrompt.prompt();
        await installPrompt.userChoice;
        installPrompt = null;
        syncInstallButtons(false);
    });

    window.addEventListener('appinstalled', () => {
        installPrompt = null;
        syncInstallButtons(false);
    });

    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js', { scope: '/' })
            .catch((error) => console.warn('KopiPOS service worker registration failed', error));
    });
};

registerPwa();
