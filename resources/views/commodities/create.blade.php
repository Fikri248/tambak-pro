<x-layouts.app title="Tambah Komoditas">
    <div class="mx-auto max-w-3xl space-y-6">
        <div>
            <a href="{{ route('commodities.index') }}" class="mb-4 inline-flex items-center gap-2 text-sm text-neutral-500 hover:text-neutral-900">
                <x-icon name="arrow-left" class="size-4" />
                Master Komoditas
            </a>
            <x-page-header title="Tambah Komoditas" description="Tambahkan jenis bibit atau komoditas baru." />
        </div>

        <x-card>
            @include('commodities.partials.form')
        </x-card>
    </div>
</x-layouts.app>
