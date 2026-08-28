@props([
    'name',
    'label',
    'options' => [],
    'value' => null,
    'placeholder' => 'Pilih opsi',
    'id' => null,
    'help' => null,
    'required' => false,
    'disabled' => false,
])

@php
    $id = $id ?: $name;
    $labelId = "{$id}-label";
    $valueId = "{$id}-selected-value";
    $listboxId = "{$id}-listbox";
    $selectedValue = old($name, $value);
    $selectedString = $selectedValue === null ? '' : (string) $selectedValue;
    $errorId = "{$id}-error";
    $helpId = "{$id}-help";
    $describedBy = collect([
        $help ? $helpId : null,
        $errors->has($name) ? $errorId : null,
    ])->filter()->implode(' ');
    $normalizedOptions = collect($options)->map(function ($option, $key): array {
        if (is_array($option) && array_key_exists('value', $option)) {
            return [
                'value' => $option['value'],
                'label' => (string) ($option['label'] ?? $option['value']),
                'disabled' => (bool) ($option['disabled'] ?? false),
            ];
        }

        return [
            'value' => $key,
            'label' => (string) $option,
            'disabled' => false,
        ];
    });
@endphp

<div data-filter-select data-open="false" {{ $attributes->class('relative min-w-0') }}>
    <label id="{{ $labelId }}" for="{{ $id }}" class="mb-1.5 block text-xs font-medium text-neutral-700">
        {{ $label }}
        @if ($required)<span aria-hidden="true" class="text-neutral-500">*</span>@endif
    </label>

    <select
        id="{{ $id }}"
        name="{{ $name }}"
        data-filter-select-native
        class="block h-10 w-full rounded-lg border bg-white px-3 text-sm text-neutral-800 transition-colors focus:border-neutral-400 focus:outline-none focus:ring-2 focus:ring-neutral-200 {{ $errors->has($name) ? 'border-neutral-400' : 'border-neutral-200 hover:border-neutral-300' }}"
        @required($required)
        @disabled($disabled)
        @if ($describedBy !== '') aria-describedby="{{ $describedBy }}" @endif
        @if ($errors->has($name)) aria-invalid="true" @endif
    >
        @if ($placeholder !== null)
            <option value="" @selected($selectedString === '')>{{ $placeholder }}</option>
        @endif
        @foreach ($normalizedOptions as $option)
            <option
                value="{{ $option['value'] }}"
                @selected($selectedString === (string) $option['value'])
                @disabled($option['disabled'])
            >{{ $option['label'] }}</option>
        @endforeach
    </select>

    <button
        data-filter-select-trigger
        hidden
        type="button"
        class="flex h-10 w-full items-center justify-between gap-3 rounded-lg border bg-white px-3 text-left text-sm text-neutral-800 transition-colors hover:border-neutral-300 disabled:cursor-not-allowed disabled:bg-neutral-100 disabled:text-neutral-400 {{ $errors->has($name) ? 'border-neutral-400' : 'border-neutral-200' }}"
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
        <p id="{{ $helpId }}" class="mt-1.5 text-xs leading-5 text-neutral-500">{{ $help }}</p>
    @endif
    @error($name)
        <p id="{{ $errorId }}" class="mt-1.5 text-xs font-medium text-neutral-700">{{ $message }}</p>
    @enderror
</div>
