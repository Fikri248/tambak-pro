@props([
    'name',
    'label',
    'required' => false,
    'help' => null,
    'id' => null,
    'disabled' => false,
])

@php
    // Form fragments can be mounted above an index page whose filters use the
    // same field names. Keep form-control IDs in their own namespace so every
    // label/listbox relationship remains unique while the canonical `name`
    // submitted to Laravel stays unchanged.
    $id = $id ?: "form-{$name}";
    $labelId = "{$id}-label";
    $valueId = "{$id}-selected-value";
    $listboxId = "{$id}-listbox";
    $helpId = "{$id}-help";
    $errorId = "{$id}-error";
    $describedBy = collect([
        $help ? $helpId : null,
        $errors->has($name) ? $errorId : null,
    ])->filter()->implode(' ');
@endphp

<div data-filter-select data-select-variant="form" data-open="false" class="relative min-w-0">
    <label id="{{ $labelId }}" for="{{ $id }}" class="mb-1.5 block text-sm font-medium text-neutral-800">
        {{ $label }}
        @if ($required)<span aria-hidden="true" class="text-neutral-500">*</span>@endif
    </label>
    <select
        id="{{ $id }}"
        name="{{ $name }}"
        data-filter-select-native
        @required($required)
        @disabled($disabled)
        {{ $attributes->class([
            'block h-10 w-full rounded-lg border bg-white px-3 text-sm text-neutral-900 transition-colors focus:border-neutral-400 focus:outline-none focus:ring-2 focus:ring-neutral-200',
            'border-neutral-400' => $errors->has($name),
            'border-neutral-200 hover:border-neutral-300' => ! $errors->has($name),
        ])->merge([
            'aria-describedby' => $describedBy !== '' ? $describedBy : null,
            'aria-invalid' => $errors->has($name) ? 'true' : null,
        ]) }}
    >
        {{ $slot }}
    </select>

    <button
        data-filter-select-trigger
        hidden
        type="button"
        class="flex h-10 w-full items-center justify-between gap-3 rounded-lg border bg-white px-3 text-left text-sm text-neutral-900 transition-colors hover:border-neutral-300 focus:border-neutral-400 focus:outline-none focus:ring-2 focus:ring-neutral-200 disabled:cursor-not-allowed disabled:bg-neutral-100 disabled:text-neutral-400 {{ $errors->has($name) ? 'border-neutral-400' : 'border-neutral-200' }}"
        aria-haspopup="listbox"
        aria-expanded="false"
        aria-controls="{{ $listboxId }}"
        aria-labelledby="{{ $labelId }} {{ $valueId }}"
        @if ($describedBy !== '') aria-describedby="{{ $describedBy }}" @endif
        @if ($required) aria-required="true" @endif
        @if ($errors->has($name)) aria-invalid="true" @endif
        @disabled($disabled)
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

    @if ($help)
        <p id="{{ $helpId }}" class="mt-1.5 text-xs text-neutral-500">{{ $help }}</p>
    @endif
    @error($name)
        <p id="{{ $errorId }}" class="mt-1.5 text-xs font-medium text-neutral-700">{{ $message }}</p>
    @enderror
</div>
