@props([
    'value' => \App\Support\PageSize::DEFAULT,
    'id' => 'per-page',
])

@php
    $labelId = "{$id}-label";
    $valueId = "{$id}-selected-value";
    $listboxId = "{$id}-listbox";
@endphp

<div data-page-size-control {{ $attributes->class('flex w-full items-end gap-2 sm:w-auto sm:shrink-0') }}>
    <label id="{{ $labelId }}" for="{{ $id }}" class="pb-2.5 text-xs font-medium text-neutral-600">Tampilkan</label>

    <div data-filter-select data-open="false" class="relative w-24 min-w-0">
        <select
            id="{{ $id }}"
            name="per_page"
            data-page-size-select
            data-filter-select-native
            class="block h-10 w-full rounded-lg border border-neutral-200 bg-white px-3 text-sm text-neutral-800 transition-colors hover:border-neutral-300 focus:border-neutral-400 focus:outline-none focus:ring-2 focus:ring-neutral-200"
        >
            @foreach (\App\Support\PageSize::OPTIONS as $option)
                <option value="{{ $option }}" @selected((int) $value === $option)>{{ $option }}</option>
            @endforeach
        </select>

        <button
            data-filter-select-trigger
            hidden
            type="button"
            class="flex h-10 w-full items-center justify-between gap-3 rounded-lg border border-neutral-200 bg-white px-3 text-left text-sm text-neutral-800 transition-colors hover:border-neutral-300"
            aria-haspopup="listbox"
            aria-expanded="false"
            aria-controls="{{ $listboxId }}"
            aria-labelledby="{{ $labelId }} {{ $valueId }}"
        >
            <span id="{{ $valueId }}" data-filter-select-value class="min-w-0 truncate"></span>
            <x-icon data-filter-select-chevron name="chevron-down" class="size-4 shrink-0 text-neutral-500" />
        </button>

        <div
            id="{{ $listboxId }}"
            data-filter-select-listbox
            hidden
            popover="manual"
            role="listbox"
            tabindex="-1"
            aria-labelledby="{{ $labelId }}"
            aria-hidden="true"
        ></div>
    </div>

    <span class="pb-2.5 text-xs text-neutral-500">data</span>
</div>
