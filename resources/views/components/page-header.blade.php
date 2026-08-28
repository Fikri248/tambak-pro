@props(['title', 'description' => null])

<div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
    <div>
        <h1 class="text-2xl font-semibold tracking-tight text-neutral-950 sm:text-[26px]">{{ $title }}</h1>
        @if ($description)
            <p class="mt-1 text-sm text-neutral-500">{{ $description }}</p>
        @endif
    </div>

    @isset($actions)
        <div class="shrink-0">{{ $actions }}</div>
    @endisset
</div>
