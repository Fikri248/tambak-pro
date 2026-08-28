@props(['name', 'label', 'autocomplete', 'id' => null])

@php
    $id = $id ?: "form-{$name}";
    $errorId = "{$id}-error";
@endphp

<div>
    <label for="{{ $id }}" class="mb-1.5 block text-sm font-medium text-neutral-800">
        {{ $label }} <span aria-hidden="true" class="text-neutral-500">*</span>
    </label>
    <div class="relative">
        <input
            id="{{ $id }}"
            name="{{ $name }}"
            type="password"
            required
            autocomplete="{{ $autocomplete }}"
            data-password-input
            @error($name) aria-invalid="true" aria-describedby="{{ $errorId }}" @enderror
            {{ $attributes->class([
                'block h-10 w-full rounded-lg border bg-white py-2 pr-11 pl-3 text-sm text-neutral-900',
                'border-neutral-400' => $errors->has($name),
                'border-neutral-200 hover:border-neutral-300' => ! $errors->has($name),
            ]) }}
        >
        <button type="button" data-password-toggle aria-controls="{{ $id }}" aria-label="Tampilkan password" aria-pressed="false" class="absolute inset-y-0 right-0 flex w-10 items-center justify-center rounded-r-lg text-neutral-500 hover:text-neutral-900 focus-visible:outline-2 focus-visible:outline-offset-[-2px] focus-visible:outline-neutral-900">
            <x-icon name="eye" data-password-show-icon class="size-4" />
            <x-icon name="eye-off" data-password-hide-icon class="hidden size-4" />
        </button>
    </div>
    @error($name)
        <p id="{{ $errorId }}" class="mt-1.5 text-xs font-medium text-neutral-700">{{ $message }}</p>
    @enderror
</div>
