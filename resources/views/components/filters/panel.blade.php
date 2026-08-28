@props([
    'id',
    'label' => 'Filter',
    'activeCount' => 0,
    'open' => false,
    'align' => 'right',
])

@php
    $triggerId = "{$id}-trigger";
    $activeCount = max(0, (int) $activeCount);
    $initiallyOpen = (bool) $open;
    $align = in_array($align, ['left', 'right'], true) ? $align : 'right';
@endphp

<div
    data-filter-panel
    data-initial-open="{{ $initiallyOpen ? 'true' : 'false' }}"
    data-filter-align="{{ $align }}"
    {{ $attributes->class('relative') }}
>
    <button
        id="{{ $triggerId }}"
        data-filter-panel-trigger
        hidden
        type="button"
        class="inline-flex min-h-10 items-center justify-center gap-2 rounded-lg border border-neutral-200 bg-white px-3.5 py-2 text-sm font-medium text-neutral-700 transition-colors hover:border-neutral-300 hover:bg-neutral-50 hover:text-neutral-950"
        aria-expanded="{{ $initiallyOpen ? 'true' : 'false' }}"
        aria-controls="{{ $id }}"
    >
        <x-icon name="filter" class="size-4" />
        <span>{{ $label }}</span>
        @if ($activeCount > 0)
            <span class="inline-flex min-w-5 items-center justify-center rounded-full bg-neutral-900 px-1.5 py-0.5 text-[10px] font-semibold leading-none text-white" aria-label="{{ $activeCount }} filter aktif">
                {{ $activeCount }}
            </span>
        @endif
        <x-icon data-filter-panel-chevron name="chevron-down" class="size-4 text-neutral-500" />
    </button>

    <div
        id="{{ $id }}"
        data-filter-panel-content
        class="mt-3 w-full rounded-xl border border-neutral-200 bg-white p-4 shadow-lg shadow-neutral-950/5 sm:p-5"
        role="region"
        aria-labelledby="{{ $triggerId }}"
        aria-hidden="false"
    >
        {{ $slot }}
    </div>
</div>
