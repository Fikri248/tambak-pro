@props(['label', 'value', 'suffix' => null, 'icon'])

<x-card class="min-w-0">
    <div class="flex items-start justify-between gap-4">
        <div class="min-w-0">
            <p class="truncate text-xs font-medium text-neutral-500">{{ $label }}</p>
            <p class="mt-3 truncate text-2xl font-semibold tracking-tight text-neutral-950">
                {{ $value }}
                @if ($suffix)
                    <span class="text-sm font-medium text-neutral-500">{{ $suffix }}</span>
                @endif
            </p>
        </div>
        <span class="flex size-9 shrink-0 items-center justify-center rounded-lg border border-neutral-200 bg-neutral-50 text-neutral-600">
            <x-icon :name="$icon" class="size-[18px]" />
        </span>
    </div>
</x-card>
