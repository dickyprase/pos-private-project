import './bootstrap';
import './printer-bluetooth';

window.formatCurrency = (value) => new Intl.NumberFormat('id-ID', {
    maximumFractionDigits: 0,
}).format(Number(value) || 0);

document.addEventListener('alpine:init', () => {
    window.Alpine.data('currencyInput', (amount) => ({
        amount,
        display: '',
        init() {
            this.display = window.formatCurrency(this.amount);
            this.$watch('amount', (value) => {
                this.display = window.formatCurrency(value);
            });
        },
        update(event) {
            const digits = event.target.value.replace(/\D/g, '');
            this.amount = digits === '' ? 0 : Number(digits);
            this.display = digits === '' ? '' : window.formatCurrency(this.amount);
            this.$nextTick(() => { event.target.value = this.display; });
        },
    }));
});

const toggleMobileMenu = () => {
    document.querySelector('[data-mobile-nav]')?.classList.toggle('hidden');
};

document.addEventListener('click', (event) => {
    if (event.target.closest('[data-mobile-menu]')) toggleMobileMenu();

    const sidebar = document.querySelector('[data-sidebar]');
    const backdrop = document.querySelector('[data-sidebar-backdrop]');
    if (event.target.closest('[data-sidebar-open]')) {
        sidebar?.classList.remove('-translate-x-full');
        backdrop?.classList.remove('hidden');
    }
    if (event.target.closest('[data-sidebar-close]') || event.target.closest('[data-sidebar-backdrop]')) {
        sidebar?.classList.add('-translate-x-full');
        backdrop?.classList.add('hidden');
    }
});

window.addEventListener('resize', () => {
    const sidebar = document.querySelector('[data-sidebar]');
    const backdrop = document.querySelector('[data-sidebar-backdrop]');
    if (window.innerWidth >= 1024) {
        sidebar?.classList.remove('-translate-x-full');
        backdrop?.classList.add('hidden');
    } else {
        sidebar?.classList.add('-translate-x-full');
    }
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
        }).format(now);
    }
    if (date) {
        const formatted = new Intl.DateTimeFormat('id-ID', {
            weekday: 'long',
            day: 'numeric',
            month: 'long',
            year: 'numeric',
        }).format(now);
        date.textContent = formatted.charAt(0).toUpperCase() + formatted.slice(1);
    }
};

updatePosClock();
window.setInterval(updatePosClock, 1000);
