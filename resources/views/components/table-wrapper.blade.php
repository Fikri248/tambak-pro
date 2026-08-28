@props(['title', 'description' => null])

<x-card :padding="false" {{ $attributes }}>
    <div class="flex items-start justify-between gap-4 border-b border-neutral-200 px-5 py-4 sm:px-6">
        <div>
            <h2 class="text-base font-semibold text-neutral-950">{{ $title }}</h2>
            @if ($description)
                <p class="mt-1 text-xs text-neutral-500">{{ $description }}</p>
            @endif
        </div>
        @isset($actions)
            <div class="shrink-0">{{ $actions }}</div>
        @endisset
    </div>
    <div data-table-container class="min-w-0 overflow-x-auto">
        {{ $slot }}
    </div>
</x-card>
