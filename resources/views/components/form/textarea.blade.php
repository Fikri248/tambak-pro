@props(['name', 'label', 'value' => null, 'rows' => 4, 'id' => null])

@php
    $id = $id ?: "form-{$name}";
    $errorId = "{$id}-error";
@endphp

<div>
    <label for="{{ $id }}" class="mb-1.5 block text-sm font-medium text-neutral-800">{{ $label }}</label>
    <textarea
        id="{{ $id }}"
        name="{{ $name }}"
        rows="{{ $rows }}"
        {{ $attributes->class([
            'block w-full resize-y rounded-lg border bg-white px-3 py-2.5 text-sm text-neutral-900 placeholder:text-neutral-400',
            'border-neutral-400' => $errors->has($name),
            'border-neutral-200 hover:border-neutral-300' => ! $errors->has($name),
        ])->merge(['aria-describedby' => $errors->has($name) ? $errorId : null]) }}
    >{{ old($name, $value) }}</textarea>
    @error($name)
        <p id="{{ $errorId }}" class="mt-1.5 text-xs font-medium text-neutral-700">{{ $message }}</p>
    @enderror
</div>
