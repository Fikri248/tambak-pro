<x-layouts.app title="Transaksi Pembibitan">
    <div class="space-y-6">
        <x-page-header title="Transaksi Pembibitan" description="Kelola riwayat bibit yang pertama kali masuk ke lokasi tambak.">
            @if (auth()->user()->canAccess('stocking.create'))
                <x-slot:actions>
                    <x-button :href="route('stocking.create')" data-crud-modal data-crud-modal-size="xl">
                        <x-icon name="plus" class="size-4" />
                        Tambah Pembibitan
                    </x-button>
                </x-slot:actions>
            @endif
        </x-page-header>

        <x-flash-message />

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Ringkasan pembibitan">
            <x-kpi-card label="Total Transaksi" :value="number_format($summary['total'], 0, ',', '.')" icon="history" />
            <x-kpi-card label="Total Bibit Masuk" :value="number_format((float) $summary['quantity'], 0, ',', '.')" :suffix="$summary['unit']" icon="seedling" />
            <x-kpi-card label="Batch Aktif" :value="number_format($summary['active_batches'], 0, ',', '.')" icon="package" />
            <x-kpi-card label="Nilai Pembibitan" :value="'Rp'.number_format((float) $summary['total_cost'], 0, ',', '.')" icon="coins" />
        </section>

        @php
            $stockingLocationOptions = $locations->mapWithKeys(fn ($location) => [$location->id => $location->name])->all();
            $stockingCommodityOptions = $commodities->mapWithKeys(fn ($commodity) => [$commodity->id => $commodity->name])->all();
            $stockingFilterCount = collect([$filters['locationId'], $filters['commodityId'], $filters['dateFrom'], $filters['dateTo']])
                ->filter(fn ($value) => $value !== null && $value !== '')
                ->count();
        @endphp
        <x-card>
            <form method="GET" action="{{ route('stocking.index') }}" class="flex flex-col gap-3 lg:flex-row lg:items-start">
                <div class="min-w-0 flex-1">
                    <label for="search" class="sr-only">Cari transaksi</label>
                    <div class="relative">
                        <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-neutral-400" />
                        <input id="search" name="search" type="search" value="{{ $filters['search'] }}" placeholder="Cari transaksi..." class="h-10 w-full rounded-lg border border-neutral-200 bg-white pl-9 pr-3 text-sm placeholder:text-neutral-400 hover:border-neutral-300">
                    </div>
                </div>
                <x-filters.panel id="stocking-filters" :active-count="$stockingFilterCount" :open="$errors->any()" class="w-full lg:w-auto lg:shrink-0">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-filters.select name="location_id" label="Petak" :options="$stockingLocationOptions" :value="$filters['locationId']" placeholder="Semua Petak" />
                        <x-filters.select name="commodity_id" label="Komoditas" :options="$stockingCommodityOptions" :value="$filters['commodityId']" placeholder="Semua Komoditas" />
                        <x-filters.date-range class="sm:col-span-2" :from="$filters['dateFrom']" :to="$filters['dateTo']" />
                    </div>
                    <div class="mt-5 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                        @if (array_filter($filters, fn ($value) => $value !== null && $value !== ''))
                            <x-button variant="secondary" :href="route('stocking.index')">Reset</x-button>
                        @endif
                        <x-button type="submit">Terapkan Filter</x-button>
                    </div>
                </x-filters.panel>
                <x-page-size id="stocking-per-page" :value="$transactions->perPage()" />
            </form>
        </x-card>

        <div>
            <x-table-wrapper title="Riwayat Pembibitan" description="Admin dan Manager dapat memperbarui atau menghapus transaksi selama belum memiliki aktivitas lanjutan.">
                @if ($transactions->isEmpty())
                    <x-empty-state title="Belum ada transaksi pembibitan" description="Catat bibit baru yang pertama kali masuk ke petak budidaya." icon="seedling">
                        @if (auth()->user()->canAccess('stocking.create'))
                            <x-button :href="route('stocking.create')" data-crud-modal data-crud-modal-size="xl">Tambah Pembibitan</x-button>
                        @endif
                    </x-empty-state>
                @else
                    <table data-responsive-table="stocking" class="w-full min-w-[1120px] text-left">
                        <thead>
                            <tr class="border-b border-neutral-200 bg-neutral-50/70 text-[11px] font-semibold uppercase tracking-wider text-neutral-500">
                                <th scope="col" class="px-5 py-3 text-center sm:px-6">No. Transaksi</th>
                                <th scope="col" class="px-5 py-3 text-center">Tanggal</th>
                                <th scope="col" class="px-5 py-3 text-center">Batch</th>
                                <th scope="col" class="px-5 py-3 text-center">Komoditas</th>
                                <th scope="col" class="px-5 py-3 text-center">Lokasi</th>
                                <th scope="col" class="px-5 py-3 text-center">Vendor</th>
                                <th scope="col" class="px-5 py-3 text-center">Jumlah</th>
                                <th scope="col" class="px-5 py-3 text-center">Total Biaya</th>
                                <th scope="col" class="px-5 py-3 pr-6 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-100">
                            @foreach ($transactions as $transaction)
                                @php
                                    $quantity = (float) $transaction->quantity;
                                    $quantityDecimals = floor($quantity) === $quantity ? 0 : 3;
                                @endphp
                                <tr class="transition-colors hover:bg-neutral-50/70">
                                    <td class="px-5 py-3.5 sm:px-6"><a href="{{ route('stocking.show', $transaction) }}" data-crud-modal data-crud-modal-size="lg" class="font-mono text-xs font-medium text-neutral-900 hover:underline">{{ $transaction->transaction_number }}</a></td>
                                    <td class="whitespace-nowrap px-5 py-3.5 text-center text-neutral-600">{{ $transaction->transaction_date->locale('id')->translatedFormat('d M Y, H:i') }}</td>
                                    <td class="px-5 py-3.5 text-center"><x-badge>{{ $transaction->batch->batch_code }}</x-badge></td>
                                    <td class="px-5 py-3.5 font-medium text-neutral-900">{{ $transaction->batch->commodity->name }}</td>
                                    <td class="px-5 py-3.5 text-center text-neutral-600">{{ $transaction->location->name }}</td>
                                    <td class="px-5 py-3.5 text-neutral-600">{{ $transaction->batch->vendor?->name ?? 'Tidak tercatat' }}</td>
                                    <td class="px-5 py-3.5 text-center font-medium tabular-nums text-neutral-900">{{ number_format($quantity, $quantityDecimals, ',', '.') }} {{ $transaction->batch->commodity->unit }}</td>
                                    <td class="px-5 py-3.5 text-center tabular-nums text-neutral-700">Rp{{ number_format((float) $transaction->total_cost, 0, ',', '.') }}</td>
                                    <td class="px-5 py-3.5 pr-6 text-center">
                                        <div class="flex items-center justify-center gap-1" data-transaction-actions>
                                            <a href="{{ route('stocking.show', $transaction) }}" data-crud-modal data-crud-modal-size="lg" class="inline-flex size-9 items-center justify-center gap-1.5 rounded-lg border border-neutral-200 bg-white text-neutral-700 transition-colors hover:bg-neutral-50 hover:text-neutral-950 xl:w-auto xl:px-2.5" aria-label="Lihat detail transaksi {{ $transaction->transaction_number }}" title="Detail">
                                                <x-icon name="eye" class="size-4" />
                                                <span class="hidden text-xs font-medium xl:inline">Detail</span>
                                            </a>
                                            @if (auth()->user()->canAccess('stocking.update'))
                                                <a href="{{ route('stocking.edit', $transaction) }}" data-crud-modal data-crud-modal-size="xl" class="inline-flex size-9 items-center justify-center gap-1.5 rounded-lg border border-neutral-200 bg-white text-neutral-700 transition-colors hover:bg-neutral-50 hover:text-neutral-950 xl:w-auto xl:px-2.5" aria-label="Edit transaksi {{ $transaction->transaction_number }}" title="Edit">
                                                    <x-icon name="edit" class="size-4" />
                                                    <span class="hidden text-xs font-medium xl:inline">Edit</span>
                                                </a>
                                            @endif
                                            @if (auth()->user()->canAccess('stocking.delete'))
                                                <form
                                                    method="POST"
                                                    action="{{ route('stocking.destroy', $transaction) }}"
                                                    class="inline-flex"
                                                    data-confirm="Hapus transaksi Pembibitan {{ $transaction->transaction_number }}?"
                                                    data-confirm-title="Hapus Transaksi Pembibitan"
                                                    data-confirm-description="Batch {{ $transaction->batch->batch_code }} di {{ $transaction->location->name }} akan dibatalkan. Tindakan ini hanya dapat dilakukan jika belum ada aktivitas lanjutan."
                                                    data-confirm-action="Hapus Transaksi"
                                                    data-confirm-tone="danger"
                                                >
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="inline-flex size-9 items-center justify-center gap-1.5 rounded-lg border border-red-200 bg-white text-red-700 transition-colors hover:bg-red-50 xl:w-auto xl:px-2.5" aria-label="Hapus transaksi {{ $transaction->transaction_number }}" title="Hapus">
                                                        <x-icon name="trash" class="size-4" />
                                                        <span class="hidden text-xs font-medium xl:inline">Hapus</span>
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

            @if ($transactions->hasPages())
                <div class="mt-4">{{ $transactions->links() }}</div>
            @endif
        </div>
    </div>
</x-layouts.app>
