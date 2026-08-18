<div
    data-ui-alert-modal
    x-data="{
        open: false,
        shown: false,
        type: 'warning',
        title: '',
        message: '',
        confirmLabel: 'Ya, lanjutkan',
        action: null,
        actionArgs: [],
        openModal(detail) {
            this.type = detail.type || 'warning'
            this.title = detail.title || 'Konfirmasi'
            this.message = detail.message || ''
            this.confirmLabel = detail.confirmLabel || 'Ya, lanjutkan'
            this.action = detail.action || null
            this.actionArgs = Array.isArray(detail.actionArgs) ? detail.actionArgs : []
            this.open = true
            this.$nextTick(() => this.shown = true)
        },
        close() {
            this.shown = false
            setTimeout(() => this.open = false, 180)
        },
        confirm() {
            const action = this.action
            const args = this.actionArgs
            this.close()
            if (action) setTimeout(() => $wire.call(action, ...args), 180)
        }
    }"
    @ui-alert:open.window="openModal($event.detail)"
    @keydown.escape.window="if (open) close()"
>
    <template x-if="open">
        <div class="fixed inset-0 z-[120] grid place-items-center p-4" role="presentation">
            <div x-show="shown" x-transition.opacity.duration.200ms class="absolute inset-0 bg-stone-950/40 backdrop-blur-[2px]"></div>
            <button type="button" @click="close()" class="absolute inset-0 size-full" aria-label="Tutup popup"></button>
            <section
                x-show="shown"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95 translate-y-2"
                class="relative w-full max-w-md overflow-hidden rounded-3xl border border-stone-200 bg-[#fafafa] shadow-2xl"
                role="alertdialog"
                aria-modal="true"
            >
                <div class="border-b-2 bg-[#f7f7f5] p-5" :class="type === 'success' ? 'border-emerald-500' : type === 'error' ? 'border-rose-500' : 'border-brand-500'">
                    <div class="flex items-start gap-3">
                        <div class="grid size-11 shrink-0 place-items-center rounded-2xl" :class="type === 'success' ? 'bg-emerald-100 text-emerald-700' : type === 'error' ? 'bg-rose-100 text-rose-700' : 'bg-brand-100 text-brand-700'">
                            <svg x-show="type === 'success'" viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="2"><path d="m5 12 4 4L19 6"/></svg>
                            <svg x-show="type === 'warning'" viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 9v4M12 17h.01"/><path d="M10.3 4.2 2.7 18a2 2 0 0 0 1.8 3h15a2 2 0 0 0 1.8-3L13.7 4.2a2 2 0 0 0-3.4 0Z"/></svg>
                            <svg x-show="type === 'error'" viewBox="0 0 24 24" class="size-5" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="m9 9 6 6M15 9l-6 6"/></svg>
                        </div>
                        <div class="min-w-0 flex-1"><h2 class="text-lg font-black tracking-tight text-stone-900" x-text="title"></h2><p class="mt-1 text-sm leading-6 text-stone-500" x-text="message"></p></div>
                        <x-ui.modal-close @click="close()" label="Tutup popup" />
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-2 bg-white p-4">
                    <button type="button" @click="close()" class="btn-secondary min-h-11 rounded-xl">Batal</button>
                    <button type="button" @click="confirm()" class="inline-flex min-h-11 items-center justify-center rounded-xl px-4 text-sm font-bold text-white" :class="type === 'success' ? 'bg-emerald-600 hover:bg-emerald-700' : type === 'error' ? 'bg-rose-600 hover:bg-rose-700' : 'bg-brand-600 hover:bg-brand-700'" x-text="confirmLabel"></button>
                </div>
            </section>
        </div>
    </template>
</div>
