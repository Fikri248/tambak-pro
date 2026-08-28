<x-layouts.app title="Master Komoditas">
    <div class="space-y-6">
        <x-page-header title="Master Komoditas" description="Kelola jenis bibit atau komoditas yang dibudidayakan.">
            @if (auth()->user()->canAccess('commodities.manage'))
                <x-slot:actions>
                    <x-button :href="route('commodities.create')" data-crud-modal data-crud-modal-size="xl">
                        <x-icon name="plus" class="size-4" />
                        Tambah Komoditas
                    </x-button>
                </x-slot:actions>
            @endif
        </x-page-header>

        <x-flash-message />

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Ringkasan komoditas">
            <x-kpi-card label="Total Komoditas" :value="number_format($summary['total'], 0, ',', '.')" icon="package" />
            <x-kpi-card label="Komoditas Aktif" :value="number_format($summary['active'], 0, ',', '.')" icon="check" />
            <x-kpi-card label="Total Batch Aktif" :value="number_format($summary['active_batches'], 0, ',', '.')" icon="building" />
            <x-kpi-card label="Total Stok Saat Ini" :value="number_format((float) $summary['current_stock'], 0, ',', '.')" :suffix="$summary['stock_unit']" icon="seedling" />
        </section>

        @php
            $commodityCategoryOptions = $categories->mapWithKeys(fn ($category) => [$category => $category])->all();
            $commodityFilterCount = collect([$filters['category'], $filters['status']])->filter()->count();
        @endphp
        <x-card>
            <form method="GET" action="{{ route('commodities.index') }}" class="flex flex-col gap-3 lg:flex-row lg:items-start">
                <div class="min-w-0 flex-1">
                    <label for="search" class="sr-only">Cari komoditas</label>
                    <div class="relative">
                        <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-neutral-400" />
                        <input id="search" name="search" type="search" value="{{ $filters['search'] }}" placeholder="Cari komoditas..." class="h-10 w-full rounded-lg border border-neutral-200 bg-white pl-9 pr-3 text-sm placeholder:text-neutral-400 hover:border-neutral-300">
                    </div>
                </div>
                <x-filters.panel id="commodity-filters" :active-count="$commodityFilterCount" class="w-full lg:w-auto lg:shrink-0">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-filters.select name="category" label="Kategori" :options="$commodityCategoryOptions" :value="$filters['category']" placeholder="Semua Kategori" />
                        <x-filters.select name="status" label="Status" :options="['ACTIVE' => 'Aktif', 'INACTIVE' => 'Tidak Aktif']" :value="$filters['status']" placeholder="Semua Status" />
                    </div>
                    <div class="mt-5 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                        @if ($filters['search'] !== '' || $filters['category'] !== '' || $filters['status'])
                            <x-button variant="secondary" :href="route('commodities.index')">Reset</x-button>
                        @endif
                        <x-button type="submit">Terapkan Filter</x-button>
                    </div>
                </x-filters.panel>
                <x-page-size id="commodities-per-page" :value="$commodities->perPage()" />
            </form>
        </x-card>

        <div>
            <x-table-wrapper title="Daftar Komoditas" description="Jenis bibit dan komoditas yang tersedia untuk kegiatan budidaya.">
                @if ($commodities->isEmpty())
                    <x-empty-state title="Belum ada data komoditas" description="Tambahkan jenis bibit atau komoditas untuk mulai mengelola data budidaya." icon="package">
                        @if (auth()->user()->canAccess('commodities.manage'))
                            <x-button :href="route('commodities.create')" data-crud-modal data-crud-modal-size="xl">Tambah Komoditas</x-button>
                        @endif
                    </x-empty-state>
                @else
                    <table data-responsive-table="commodities" class="w-full min-w-[980px] text-left">
                        <thead>
                            <tr class="border-b border-neutral-200 bg-neutral-50/70 text-[11px] font-semibold uppercase tracking-wider text-neutral-500">
                                <th scope="col" class="px-5 py-3 sm:px-6">Kode</th>
                                <th scope="col" class="px-5 py-3">Nama Komoditas</th>
                                <th scope="col" class="px-5 py-3">Kategori</th>
                                <th scope="col" class="px-5 py-3">Satuan</th>
                                <th scope="col" class="px-5 py-3 text-right">Batch Aktif</th>
                                <th scope="col" class="px-5 py-3 text-right">Stok Saat Ini</th>
                                <th scope="col" class="px-5 py-3">Status</th>
                                <th scope="col" class="px-5 py-3 pr-6 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-100">
                            @foreach ($commodities as $commodity)
                                @php
                                    $stock = (float) $commodity->current_stock;
                                    $stockDecimals = floor($stock) === $stock ? 0 : 3;
                                @endphp
                                <tr class="transition-colors hover:bg-neutral-50/70">
                                    <td class="px-5 py-3.5 font-mono text-xs text-neutral-600 sm:px-6">{{ $commodity->code }}</td>
                                    <td class="px-5 py-3.5">
                                        <a href="{{ route('commodities.show', $commodity) }}" data-crud-modal data-crud-modal-size="lg" class="font-medium text-neutral-900 hover:underline">{{ $commodity->name }}</a>
                                        @if ($commodity->description)
                                            <p class="mt-0.5 max-w-[260px] truncate text-xs text-neutral-500">{{ $commodity->description }}</p>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3.5 text-center text-neutral-600">{{ $commodity->category ?: 'Tidak dikategorikan' }}</td>
                                    <td class="px-5 py-3.5 text-center text-neutral-600">{{ $commodity->unit }}</td>
                                    <td class="px-5 py-3.5 text-right tabular-nums text-neutral-700">{{ number_format($commodity->active_batches_count, 0, ',', '.') }} kelompok</td>
                                    <td class="px-5 py-3.5 text-right font-medium tabular-nums text-neutral-900">{{ number_format($stock, $stockDecimals, ',', '.') }} {{ $commodity->unit }}</td>
                                    <td class="px-5 py-3.5"><x-badge>{{ $commodity->status === 'ACTIVE' ? 'Aktif' : 'Tidak Aktif' }}</x-badge></td>
                                    <td class="px-5 py-3 pr-6">
                                        <div class="flex justify-end gap-1">
                                            <a href="{{ route('commodities.show', $commodity) }}" data-crud-modal data-crud-modal-size="lg" class="flex size-9 items-center justify-center rounded-lg text-neutral-500 hover:bg-neutral-100 hover:text-neutral-900" aria-label="Detail {{ $commodity->name }}" title="Detail">
                                                <x-icon name="eye" class="size-4" />
                                            </a>
                                            @if (auth()->user()->canAccess('commodities.manage'))
                                                <a href="{{ route('commodities.edit', $commodity) }}" data-crud-modal data-crud-modal-size="xl" class="flex size-9 items-center justify-center rounded-lg text-neutral-500 hover:bg-neutral-100 hover:text-neutral-900" aria-label="Edit {{ $commodity->name }}" title="Edit">
                                                    <x-icon name="edit" class="size-4" />
                                                </a>
                                                <form method="POST" action="{{ route('commodities.status', $commodity) }}" data-confirm="{{ $commodity->status === 'ACTIVE' ? 'Nonaktifkan komoditas ini?' : 'Aktifkan komoditas ini?' }}" data-confirm-title="{{ $commodity->status === 'ACTIVE' ? 'Nonaktifkan Komoditas' : 'Aktifkan Komoditas' }}" data-confirm-action="{{ $commodity->status === 'ACTIVE' ? 'Nonaktifkan' : 'Aktifkan' }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="flex size-9 items-center justify-center rounded-lg text-neutral-500 hover:bg-neutral-100 hover:text-neutral-900" aria-label="{{ $commodity->status === 'ACTIVE' ? 'Nonaktifkan' : 'Aktifkan' }} {{ $commodity->name }}" title="{{ $commodity->status === 'ACTIVE' ? 'Nonaktifkan' : 'Aktifkan' }}">
                                                        <x-icon name="power" class="size-4" />
                                                    </button>
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

            @if ($commodities->hasPages())
                <div class="mt-4">{{ $commodities->links() }}</div>
            @endif
        </div>
    </div>
</x-layouts.app>
