import './bootstrap';
import './mobile-ux';
import './printer-bluetooth';
import './pwa';

window.formatCurrency = (value) => new Intl.NumberFormat('id-ID', {
    maximumFractionDigits: 0,
}).format(Number(value) || 0);

window.formatCashInput = (input) => {
    const digits = String(input.value || '').replace(/\D/g, '');
    input.value = digits ? window.formatCurrency(digits) : '0';
};

document.addEventListener('input', (event) => {
    const input = event.target.closest?.('[data-currency-input]');
    if (input) window.formatCashInput(input);
}, true);

const registerCurrencyInput = () => {
    if (!window.Alpine || window.Alpine.__kopiposCurrencyInput) return;
    window.Alpine.__kopiposCurrencyInput = true;
    window.Alpine.data('currencyInput', (initialAmount = 0, total = 0) => ({
        received: Number(initialAmount) || 0,
        grandTotal: Number(total) || 0,
        displayReceived: '',
        init() {
            this.setReceived(this.received, false);
        },
        format(value) {
            return value > 0 ? window.formatCurrency(value) : '';
        },
        setReceived(value, sync = true) {
            const digits = String(value ?? '').replace(/\D/g, '');
            this.received = Number(digits || 0);
            this.displayReceived = this.format(this.received);
            if (this.$refs.receivedInput) this.$refs.receivedInput.value = this.displayReceived;
            if (sync) this.$wire.set('receivedAmount', this.received);
        },
        handleReceivedInput(event) {
            this.setReceived(event.target.value);
        },
        get change() {
            return Math.max(0, this.received - this.grandTotal);
        },
        get displayChange() {
            return window.formatCurrency(this.change);
        },
    }));
};

if (window.Alpine) registerCurrencyInput();
document.addEventListener('alpine:init', registerCurrencyInput);

const toggleMobileMenu = () => {
    const nav = document.querySelector('[data-mobile-nav]');
    if (!nav) return;
    const open = nav.classList.toggle('hidden') === false;
    document.querySelectorAll('[data-mobile-menu]').forEach((button) => button.setAttribute('aria-expanded', String(open)));
};

const syncSidebar = (open) => {
    const sidebar = document.querySelector('[data-sidebar]');
    const backdrop = document.querySelector('[data-sidebar-backdrop]');
    sidebar?.classList.toggle('-translate-x-full', !open);
    backdrop?.classList.toggle('hidden', !open);
    document.body.classList.toggle('overflow-hidden', open && window.innerWidth < 1024);
};

const bindUi = () => {
    document.querySelectorAll('[data-sidebar-open]').forEach((button) => { button.onclick = () => syncSidebar(true); });
    document.querySelectorAll('[data-sidebar-close], [data-sidebar-backdrop]').forEach((button) => { button.onclick = () => syncSidebar(false); });
    updatePosClock();
};

document.addEventListener('click', (event) => {
    if (event.target.closest('[data-mobile-menu]')) toggleMobileMenu();

    const sidebar = document.querySelector('[data-sidebar]');
    const backdrop = document.querySelector('[data-sidebar-backdrop]');
    if (event.target.closest('[data-sidebar-open]')) {
        syncSidebar(true);
    }
    if (event.target.closest('[data-sidebar-close]') || event.target.closest('[data-sidebar-backdrop]')) {
        syncSidebar(false);
    }
});

window.addEventListener('resize', () => {
    const sidebar = document.querySelector('[data-sidebar]');
    const backdrop = document.querySelector('[data-sidebar-backdrop]');
    if (window.innerWidth >= 1024) syncSidebar(true);
    else syncSidebar(false);
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'F2') {
        event.preventDefault();
        document.querySelector('[data-product-search]')?.focus();
    }
    if (event.key === 'F8') {
        event.preventDefault();
        document.querySelector('[data-open-payment]')?.click();
    }
    if (event.key === 'Escape') {
        document.querySelectorAll('[data-close-modal]').forEach((button) => button.click());
    }
    if (event.key === '/' && !['INPUT', 'TEXTAREA'].includes(document.activeElement?.tagName)) {
        event.preventDefault();
        document.querySelector('[data-product-search]')?.focus();
    }
});

const updatePosClock = () => {
    const clock = document.querySelector('[data-pos-clock]');
    const date = document.querySelector('[data-pos-date]');
    const now = new Date();
    if (clock) {
        clock.textContent = new Intl.DateTimeFormat('id-ID', {
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: false,
            timeZone: 'Asia/Jakarta',
        }).format(now);
    }
    if (date) {
        const formatted = new Intl.DateTimeFormat('id-ID', {
            weekday: 'long',
            day: 'numeric',
            month: 'long',
            year: 'numeric',
            timeZone: 'Asia/Jakarta',
        }).format(now);
        date.textContent = formatted.charAt(0).toUpperCase() + formatted.slice(1);
    }
};

updatePosClock();
window.setInterval(updatePosClock, 1000);
document.addEventListener('livewire:navigated', bindUi);
document.addEventListener('livewire:initialized', bindUi);
document.addEventListener('livewire:load', bindUi);
bindUi();

document.addEventListener('livewire:init', () => {
    window.Livewire.on('open-receipt', ({ url }) => { if (url) window.open(url, '_blank', 'noopener'); });
});
