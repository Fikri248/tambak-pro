<x-layouts.app title="Edit Pakan, Nutrisi, atau Obat">
    <div class="mx-auto max-w-4xl space-y-6">
        <div>
            <a href="{{ route('feed-items.show', $feedItem) }}" class="mb-4 inline-flex items-center gap-2 text-sm text-neutral-500 hover:text-neutral-900">
                <x-icon name="arrow-left" class="size-4" />
                {{ $feedItem->name }}
            </a>
            <x-page-header title="Edit Pakan, Nutrisi, atau Obat" description="Perbarui informasi kebutuhan budidaya." />
        </div>

        <x-flash-message />

        <x-card>
            @include('feed-items.partials.form')
        </x-card>
    </div>
</x-layouts.app>
