<x-layouts.app title="Edit Lokasi">
    <div class="mx-auto max-w-3xl space-y-6">
        <div>
            <a href="{{ route('tambak.show', $location) }}" class="mb-4 inline-flex items-center gap-2 text-sm text-neutral-500 hover:text-neutral-900">
                <x-icon name="arrow-left" class="size-4" />
                {{ $location->name }}
            </a>
            <x-page-header title="Edit Lokasi" description="Perbarui informasi {{ $location->name }}." />
        </div>

        <x-flash-message />

        <x-card>
            @include('tambak.partials.form')
        </x-card>
    </div>
</x-layouts.app>
