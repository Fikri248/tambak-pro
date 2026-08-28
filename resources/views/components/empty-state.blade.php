@props(['title', 'description' => null, 'icon' => 'history'])

<div {{ $attributes->class('flex flex-col items-center justify-center px-6 py-10 text-center') }}>
    <span class="flex size-10 items-center justify-center rounded-lg border border-neutral-200 bg-neutral-50 text-neutral-400">
        <x-icon :name="$icon" class="size-5" />
    </span>
    <p class="mt-3 text-sm font-medium text-neutral-800">{{ $title }}</p>
    @if ($description)
        <p class="mt-1 max-w-sm text-xs leading-5 text-neutral-500">{{ $description }}</p>
    @endif
    @if (trim((string) $slot) !== '')
        <div class="mt-4">{{ $slot }}</div>
    @endif
</div>
