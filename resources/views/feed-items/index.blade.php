<x-layouts.app title="Barang/Item">
    <div class="space-y-6">
        <x-page-header title="Barang/Item" description="Kelola Barang/Item untuk kebutuhan operasional budidaya.">
            @if (auth()->user()->canAccess('feed-items.manage'))
                <x-slot:actions>
                    <x-button :href="route('feed-items.create')" data-crud-modal data-crud-modal-size="xl">
                        <x-icon name="plus" class="size-4" />
                        Tambah Barang/Item
                    </x-button>
                </x-slot:actions>
            @endif
        </x-page-header>

        <x-flash-message />

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Ringkasan Barang/Item">
            <x-kpi-card label="Total Barang/Item" :value="number_format($summary['total'], 0, ',', '.')" icon="feed" />
            <x-kpi-card label="Barang/Item Aktif" :value="number_format($summary['active'], 0, ',', '.')" icon="check" />
            <x-kpi-card label="Pakan" :value="number_format($summary['feed'], 0, ',', '.')" icon="package" />
            <x-kpi-card label="Nutrisi & Obat" :value="number_format($summary['nutritionMedicine'], 0, ',', '.')" icon="adjustment" />
        </section>

        @php
            $feedItemFilterCount = collect([$filters['type'], $filters['status']])->filter()->count();
        @endphp
        <x-card>
            <form method="GET" action="{{ route('feed-items.index') }}" class="flex flex-col gap-3 lg:flex-row lg:items-start">
                <div class="min-w-0 flex-1">
                    <label for="search" class="sr-only">Cari Barang/Item</label>
                    <div class="relative">
                        <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-neutral-400" />
                        <input id="search" name="search" type="search" value="{{ $filters['search'] }}" placeholder="Cari Barang/Item..." class="h-10 w-full rounded-lg border border-neutral-200 bg-white pl-9 pr-3 text-sm placeholder:text-neutral-400 hover:border-neutral-300">
                    </div>
                </div>
                <x-filters.panel id="feed-item-filters" :active-count="$feedItemFilterCount" class="w-full lg:w-auto lg:shrink-0">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-filters.select name="type" label="Jenis Barang/Item" :options="$typeLabels" :value="$filters['type']" placeholder="Semua Jenis" />
                        <x-filters.select name="status" label="Status" :options="['ACTIVE' => 'Aktif', 'INACTIVE' => 'Tidak Aktif']" :value="$filters['status']" placeholder="Semua Status" />
                    </div>
                    <div class="mt-5 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                        @if ($filters['search'] !== '' || $filters['type'] || $filters['status'])
                            <x-button variant="secondary" :href="route('feed-items.index')">Reset</x-button>
                        @endif
                        <x-button type="submit">Terapkan Filter</x-button>
                    </div>
                </x-filters.panel>
                <x-page-size id="feed-items-per-page" :value="$feedItems->perPage()" />
            </form>
        </x-card>

        <div>
            <x-table-wrapper title="Daftar Barang/Item" description="Barang/Item yang dapat digunakan pada aktivitas budidaya.">
                @if ($feedItems->isEmpty())
                    <x-empty-state title="Belum ada Barang/Item" description="Tambahkan Barang/Item untuk mulai mencatat aktivitas budidaya." icon="feed">
                        @if (auth()->user()->canAccess('feed-items.manage'))
                            <x-button :href="route('feed-items.create')" data-crud-modal data-crud-modal-size="xl">Tambah Barang/Item</x-button>
                        @endif
                    </x-empty-state>
                @else
                    <table data-responsive-table="feed-items" class="w-full min-w-[980px] text-left">
                        <thead>
                            <tr class="border-b border-neutral-200 bg-neutral-50/70 text-[11px] font-semibold uppercase tracking-wider text-neutral-500">
                                <th scope="col" class="px-5 py-3 sm:px-6">Kode</th>
                                <th scope="col" class="px-5 py-3">Nama</th>
                                <th scope="col" class="px-5 py-3">Jenis</th>
                                <th scope="col" class="px-5 py-3">Vendor Utama</th>
                                <th scope="col" class="px-5 py-3">Satuan</th>
                                <th scope="col" class="px-5 py-3 text-right">Harga Default</th>
                                <th scope="col" class="px-5 py-3">Status</th>
                                <th scope="col" class="px-5 py-3 pr-6 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-100">
                            @foreach ($feedItems as $feedItem)
                                <tr class="transition-colors hover:bg-neutral-50/70">
                                    <td class="px-5 py-3.5 font-mono text-xs text-neutral-600 sm:px-6">{{ $feedItem->code }}</td>
                                    <td class="px-5 py-3.5">
                                        <a href="{{ route('feed-items.show', $feedItem) }}" data-crud-modal data-crud-modal-size="lg" class="font-medium text-neutral-900 hover:underline">{{ $feedItem->name }}</a>
                                        @if ($feedItem->description)
                                            <p class="mt-0.5 max-w-[230px] truncate text-xs text-neutral-500">{{ $feedItem->description }}</p>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3.5 text-center"><x-badge>{{ $feedItem->itemType->name }}</x-badge></td>
                                    <td class="px-5 py-3.5 text-sm text-neutral-600">
                                        @if ($feedItem->defaultVendor)
                                            <span>{{ $feedItem->defaultVendor->name }}</span>
                                            @if ($feedItem->defaultVendor->status !== 'ACTIVE')
                                                <p class="mt-0.5 text-xs text-neutral-500">Vendor tidak aktif</p>
                                            @endif
                                        @else
                                            <span class="text-neutral-400">Tanpa Vendor utama</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3.5 text-center text-neutral-600">{{ $feedItem->unit }}</td>
                                    <td class="px-5 py-3.5 text-right font-medium tabular-nums text-neutral-900">Rp{{ \App\Support\DecimalDisplay::localized($feedItem->default_price) }}</td>
                                    <td class="px-5 py-3.5"><x-badge>{{ $feedItem->status === 'ACTIVE' ? 'Aktif' : 'Tidak Aktif' }}</x-badge></td>
                                    <td class="px-5 py-3 pr-6">
                                        <div class="flex justify-end gap-1">
                                            <a href="{{ route('feed-items.show', $feedItem) }}" data-crud-modal data-crud-modal-size="lg" class="flex size-9 items-center justify-center rounded-lg text-neutral-500 hover:bg-neutral-100 hover:text-neutral-900" aria-label="Detail {{ $feedItem->name }}" title="Detail"><x-icon name="eye" class="size-4" /></a>
                                            @if (auth()->user()->canAccess('feed-items.manage'))
                                                <a href="{{ route('feed-items.edit', $feedItem) }}" data-crud-modal data-crud-modal-size="xl" class="flex size-9 items-center justify-center rounded-lg text-neutral-500 hover:bg-neutral-100 hover:text-neutral-900" aria-label="Edit {{ $feedItem->name }}" title="Edit"><x-icon name="edit" class="size-4" /></a>
                                                <form method="POST" action="{{ route('feed-items.status', $feedItem) }}" data-confirm="{{ $feedItem->status === 'ACTIVE' ? 'Nonaktifkan kebutuhan ini?' : 'Aktifkan kebutuhan ini?' }}" data-confirm-title="{{ $feedItem->status === 'ACTIVE' ? 'Nonaktifkan Kebutuhan' : 'Aktifkan Kebutuhan' }}" data-confirm-action="{{ $feedItem->status === 'ACTIVE' ? 'Nonaktifkan' : 'Aktifkan' }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="flex size-9 items-center justify-center rounded-lg text-neutral-500 hover:bg-neutral-100 hover:text-neutral-900" aria-label="{{ $feedItem->status === 'ACTIVE' ? 'Nonaktifkan' : 'Aktifkan' }} {{ $feedItem->name }}" title="{{ $feedItem->status === 'ACTIVE' ? 'Nonaktifkan' : 'Aktifkan' }}"><x-icon name="power" class="size-4" /></button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </x-table-wrapper>

            @if ($feedItems->hasPages())
                <div class="mt-4">{{ $feedItems->links() }}</div>
            @endif
        </div>
    </div>
</x-layouts.app>
