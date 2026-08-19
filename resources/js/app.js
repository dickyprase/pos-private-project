import './bootstrap';
import './mobile-ux';
import './printer-bluetooth';

window.formatCurrency = (value) => new Intl.NumberFormat('id-ID', {
    maximumFractionDigits: 0,
}).format(Number(value) || 0);

document.addEventListener('alpine:init', () => {
    window.Alpine.data('currencyInput', (amount, total = 0) => ({
        amount,
        total: Number(total) || 0,
        display: '',
        get change() { return Math.max(0, Number(this.amount) - this.total); },
        init() {
            this.display = window.formatCurrency(this.amount);
            this.$watch('amount', (value) => { this.display = window.formatCurrency(value); });
        },
        update(event) {
            const digits = event.target.value.replace(/\D/g, '');
            this.amount = digits === '' ? 0 : Number(digits);
            this.display = digits === '' ? '' : window.formatCurrency(this.amount);
            this.$wire.set('receivedAmount', this.amount);
            this.$nextTick(() => { event.target.value = this.display; });
        },
    }));
});

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
