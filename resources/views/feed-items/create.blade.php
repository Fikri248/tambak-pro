<x-layouts.app title="Tambah Barang/Item">
    <div class="mx-auto max-w-4xl space-y-6">
        <div>
            <a href="{{ route('feed-items.index') }}" class="mb-4 inline-flex items-center gap-2 text-sm text-neutral-500 hover:text-neutral-900">
                <x-icon name="arrow-left" class="size-4" />
                Barang/Item
            </a>
            <x-page-header title="Tambah Barang/Item" description="Tambahkan Barang/Item baru untuk kegiatan budidaya." />
        </div>

        <x-flash-message />

        <x-card>
            @include('feed-items.partials.form')
        </x-card>
    </div>
</x-layouts.app>
