@props([
    'model',
    'options' => [],
    'placeholder' => 'Pilih opsi',
])

<div
    x-data="{
        open: false,
        value: $wire.entangle(@js($model), true),
        options: @js(array_values($options)),
        label() {
            return this.options.find(option => String(option.value) === String(this.value))?.label || @js($placeholder)
        },
        choose(option) {
            this.value = option.value
            this.open = false
        }
    }"
    class="relative"
    @click.outside="open = false"
    @keydown.escape.window="open = false"
>
    <button
        type="button"
        class="field-input flex items-center justify-between gap-3 text-left"
        @click="open = !open"
        :aria-expanded="open"
        aria-haspopup="listbox"
    >
        <span class="truncate" x-text="label()"></span>
        <svg viewBox="0 0 24 24" class="size-4 shrink-0 text-stone-500 transition" :class="open && 'rotate-180'" fill="none" stroke="currentColor" stroke-width="2"><path d="m8 10 4 4 4-4"/></svg>
    </button>
    <div
        x-cloak
        x-show="open"
        x-transition.origin.top
        class="absolute z-50 mt-2 max-h-60 w-full overflow-y-auto rounded-xl border border-stone-200 bg-white p-1.5 shadow-xl shadow-stone-950/10"
        role="listbox"
    >
        <template x-for="option in options" :key="String(option.value)">
            <button
                type="button"
                class="flex min-h-10 w-full items-center justify-between gap-3 rounded-lg px-3 text-left text-sm text-stone-700 hover:bg-brand-50 hover:text-brand-800"
                :class="String(option.value) === String(value) && 'bg-brand-50 font-semibold text-brand-800'"
                @click="choose(option)"
                role="option"
            >
                <span x-text="option.label"></span>
                <svg x-show="String(option.value) === String(value)" viewBox="0 0 24 24" class="size-4" fill="none" stroke="currentColor" stroke-width="2"><path d="m5 12 4 4L19 6"/></svg>
            </button>
        </template>
    </div>
</div>