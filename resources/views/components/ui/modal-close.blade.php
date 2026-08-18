@props([
    'label' => 'Tutup modal',
])

<button
    data-modal-close
    type="button"
    {{ $attributes->class('group grid size-10 shrink-0 place-items-center rounded-xl border border-stone-200 bg-white text-stone-500 shadow-sm transition duration-150 hover:border-brand-300 hover:bg-brand-50 hover:text-brand-700 active:scale-95 focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2') }}
    aria-label="{{ $label }}"
    title="{{ $label }}"
>
    <svg viewBox="0 0 24 24" class="size-4 transition-transform duration-150 group-hover:rotate-90" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round">
        <path d="m7 7 10 10M17 7 7 17"/>
    </svg>
    <span class="sr-only">{{ $label }}</span>
</button>
