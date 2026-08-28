<x-layouts.app title="Tambah Lokasi">
    <div class="mx-auto max-w-3xl space-y-6">
        <div>
            <a href="{{ route('tambak.index') }}" class="mb-4 inline-flex items-center gap-2 text-sm text-neutral-500 hover:text-neutral-900">
                <x-icon name="arrow-left" class="size-4" />
                Master Tambak
            </a>
            <x-page-header title="Tambah Lokasi" description="Tambahkan area, tambak, atau petak baru." />
        </div>

        <x-card>
            @include('tambak.partials.form')
        </x-card>
    </div>
</x-layouts.app>
