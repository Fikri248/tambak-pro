<x-layouts.app title="Edit Komoditas">
    <div class="mx-auto max-w-3xl space-y-6">
        <div>
            <a href="{{ route('commodities.show', $commodity) }}" class="mb-4 inline-flex items-center gap-2 text-sm text-neutral-500 hover:text-neutral-900">
                <x-icon name="arrow-left" class="size-4" />
                {{ $commodity->name }}
            </a>
            <x-page-header title="Edit Komoditas" description="Perbarui informasi {{ $commodity->name }}." />
        </div>

        <x-flash-message />

        <x-card>
            @include('commodities.partials.form')
        </x-card>
    </div>
</x-layouts.app>
