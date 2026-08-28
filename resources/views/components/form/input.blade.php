@props(['name', 'label', 'type' => 'text', 'value' => null, 'required' => false, 'help' => null, 'id' => null])

@php
    $id = $id ?: "form-{$name}";
    $helpId = "{$id}-help";
    $errorId = "{$id}-error";
    $describedBy = collect([
        $help ? $helpId : null,
        $errors->has($name) ? $errorId : null,
    ])->filter()->implode(' ');
@endphp

<div>
    <label for="{{ $id }}" class="mb-1.5 block text-sm font-medium text-neutral-800">
        {{ $label }}
        @if ($required)<span aria-hidden="true" class="text-neutral-500">*</span>@endif
    </label>
    <input
        id="{{ $id }}"
        name="{{ $name }}"
        type="{{ $type }}"
        value="{{ old($name, $value) }}"
        @required($required)
        {{ $attributes->class([
            'block h-10 w-full rounded-lg border bg-white px-3 text-sm text-neutral-900 placeholder:text-neutral-400',
            'border-neutral-400' => $errors->has($name),
            'border-neutral-200 hover:border-neutral-300' => ! $errors->has($name),
        ])->merge(['aria-describedby' => $describedBy !== '' ? $describedBy : null]) }}
    >
    @if ($help)
        <p id="{{ $helpId }}" class="mt-1.5 text-xs text-neutral-500">{{ $help }}</p>
    @endif
    @error($name)
        <p id="{{ $errorId }}" class="mt-1.5 text-xs font-medium text-neutral-700">{{ $message }}</p>
    @enderror
</div>
