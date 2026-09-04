<x-layouts.app title="Penggunaan Barang/Item">
    <div class="space-y-6">
        <x-page-header title="Penggunaan Barang/Item" description="Kelola riwayat penggunaan Barang/Item pada petak budidaya.">
            @if (auth()->user()->canAccess('feeding.create'))
                <x-slot:actions>
                    <x-button :href="route('feeding.create')" data-crud-modal data-crud-modal-size="xl">
                        <x-icon name="plus" class="size-4" />
                        Tambah Penggunaan
                    </x-button>
                </x-slot:actions>
            @endif
        </x-page-header>

        <x-flash-message />

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Ringkasan penggunaan Barang/Item">
            <x-kpi-card label="Total Transaksi" :value="number_format($summary['total'], 0, ',', '.')" icon="feed" />
            <x-kpi-card label="Total Biaya" :value="'Rp'.number_format($summary['cost'], 0, ',', '.')" icon="coins" />
            <x-kpi-card label="Kebutuhan Terpakai" :value="number_format($summary['items'], 0, ',', '.')" icon="package" />
            <x-kpi-card label="Transaksi Bulan Ini" :value="number_format($summary['currentMonth'], 0, ',', '.')" icon="calendar" />
        </section>

        @php
            $feedingLocationOptions = $locations->mapWithKeys(fn ($location) => [$location->id => $location->name])->all();
            $feedingItemOptions = $feedItems->mapWithKeys(fn ($item) => [$item->id => $item->name])->all();
            $feedingFilterCount = collect([$filters['type'], $filters['locationId'], $filters['feedItemId'], $filters['dateFrom'], $filters['dateTo']])
                ->filter(fn ($value) => $value !== null && $value !== '')
                ->count();
        @endphp
        <x-card>
            <form method="GET" action="{{ route('feeding.index') }}" class="flex flex-col gap-3 lg:flex-row lg:items-start">
                <div class="min-w-0 flex-1">
                    <label for="search" class="sr-only">Cari penggunaan Barang/Item</label>
                    <div class="relative">
                        <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-neutral-400" />
                        <input id="search" name="search" type="search" value="{{ $filters['search'] }}" placeholder="Cari penggunaan Barang/Item..." class="h-10 w-full rounded-lg border border-neutral-200 bg-white pl-9 pr-3 text-sm placeholder:text-neutral-400 hover:border-neutral-300">
                    </div>
                </div>
                <x-filters.panel id="feeding-filters" :active-count="$feedingFilterCount" :open="$errors->any()" class="w-full lg:w-auto lg:shrink-0">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-filters.select name="type" label="Jenis Barang/Item" :options="$typeLabels" :value="$filters['type']" placeholder="Semua Jenis" />
                        <x-filters.select name="location_id" label="Petak" :options="$feedingLocationOptions" :value="$filters['locationId']" placeholder="Semua Petak" />
                        <x-filters.select name="feed_item_id" label="Barang/Item" :options="$feedingItemOptions" :value="$filters['feedItemId']" placeholder="Semua Barang/Item" class="sm:col-span-2" />
                        <x-filters.date-range class="sm:col-span-2" :from="$filters['dateFrom']" :to="$filters['dateTo']" />
                    </div>
                    <div class="mt-5 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                        @if (array_filter($filters, fn ($value) => $value !== null && $value !== ''))
                            <x-button variant="secondary" :href="route('feeding.index')">Reset</x-button>
                        @endif
                        <x-button type="submit">Terapkan Filter</x-button>
                    </div>
                </x-filters.panel>
                <x-page-size id="feeding-per-page" :value="$transactions->perPage()" />
            </form>
        </x-card>

        <div>
            <x-table-wrapper title="Riwayat Penggunaan Barang/Item" description="Penggunaan dan biaya dapat diperbarui oleh Admin dan Manager; transaksi ini tidak mengubah stok.">
                @if ($transactions->isEmpty())
                    <x-empty-state title="Belum ada Penggunaan Barang/Item" description="Catat penggunaan Barang/Item untuk aktivitas budidaya." icon="feed">
                        @if (auth()->user()->canAccess('feeding.create'))
                            <x-button :href="route('feeding.create')" data-crud-modal data-crud-modal-size="xl">Tambah Penggunaan</x-button>
                        @endif
                    </x-empty-state>
                @else
                    <table data-responsive-table="feeding" class="w-full min-w-[1180px] text-left">
                        <thead>
                            <tr class="border-b border-neutral-200 bg-neutral-50/70 text-[11px] font-semibold uppercase tracking-wider text-neutral-500">
                                <th scope="col" class="px-5 py-3 text-center sm:px-6">No. Transaksi</th>
                                <th scope="col" class="px-5 py-3 text-center">Tanggal</th>
                                <th scope="col" class="px-5 py-3 text-center">Petak</th>
                                <th scope="col" class="px-5 py-3 text-center">Batch</th>
                                <th scope="col" class="px-5 py-3 text-center">Barang/Item</th>
                                <th scope="col" class="px-5 py-3 text-center">Jumlah</th>
                                <th scope="col" class="px-5 py-3 text-center">Harga per Satuan</th>
                                <th scope="col" class="px-5 py-3 text-center">Total</th>
                                <th scope="col" class="px-5 py-3 pr-6 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-100">
                            @foreach ($transactions as $transaction)
                                @php
                                    $quantity = (float) $transaction->feed_quantity;
                                    $decimals = floor($quantity) === $quantity ? 0 : 3;
                                @endphp
                                <tr class="transition-colors hover:bg-neutral-50/70">
                                    <td class="px-5 py-3.5 sm:px-6"><a href="{{ route('feeding.show', $transaction) }}" data-crud-modal data-crud-modal-size="lg" class="font-mono text-xs font-medium text-neutral-900 hover:underline">{{ $transaction->transaction_number }}</a></td>
                                    <td class="whitespace-nowrap px-5 py-3.5 text-center text-neutral-600">{{ $transaction->transaction_date->locale('id')->translatedFormat('d M Y, H:i') }}</td>
                                    <td class="px-5 py-3.5 text-center font-medium text-neutral-900">{{ $transaction->location->name }}</td>
                                    <td class="px-5 py-3.5 text-center">
                                        @if ($transaction->batch)
                                            <x-badge>{{ $transaction->batch->batch_code }}</x-badge>
                                            <p class="mt-1 text-xs text-neutral-500">{{ $transaction->batch->commodity->name }}</p>
                                        @else
                                            <span class="text-sm text-neutral-500">Seluruh Petak</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3.5">
                                        <p class="font-medium text-neutral-900">{{ $transaction->feedItem->name }}</p>
                                        <p class="mt-0.5 text-xs text-neutral-500">{{ $transaction->feedItem->itemType->name }}</p>
                                    </td>
                                    <td class="px-5 py-3.5 text-center font-medium tabular-nums text-neutral-900">{{ number_format($quantity, $decimals, ',', '.') }} {{ $transaction->feedItem->unit }}</td>
                                    <td class="px-5 py-3.5 text-center tabular-nums text-neutral-600">Rp{{ number_format((float) $transaction->unit_cost, 0, ',', '.') }}</td>
                                    <td class="px-5 py-3.5 text-center font-medium tabular-nums text-neutral-900">Rp{{ number_format((float) $transaction->total_cost, 0, ',', '.') }}</td>
                                    <td class="px-5 py-3.5 pr-6 text-center">
                                        <div class="flex items-center justify-center gap-1" data-transaction-actions>
                                            <a href="{{ route('feeding.show', $transaction) }}" data-crud-modal data-crud-modal-size="lg" class="inline-flex size-9 items-center justify-center gap-1.5 rounded-lg border border-neutral-200 bg-white text-neutral-700 transition-colors hover:bg-neutral-50 hover:text-neutral-950 xl:w-auto xl:px-2.5" aria-label="Lihat detail transaksi {{ $transaction->transaction_number }}" title="Detail"><x-icon name="eye" class="size-4" /><span class="hidden text-xs font-medium xl:inline">Detail</span></a>
                                            @if (auth()->user()->canAccess('feeding.update'))
                                                <a href="{{ route('feeding.edit', $transaction) }}" data-crud-modal data-crud-modal-size="xl" class="inline-flex size-9 items-center justify-center gap-1.5 rounded-lg border border-neutral-200 bg-white text-neutral-700 transition-colors hover:bg-neutral-50 hover:text-neutral-950 xl:w-auto xl:px-2.5" aria-label="Edit transaksi {{ $transaction->transaction_number }}" title="Edit"><x-icon name="edit" class="size-4" /><span class="hidden text-xs font-medium xl:inline">Edit</span></a>
                                            @endif
                                            @if (auth()->user()->canAccess('feeding.delete'))
                                                <form method="POST" action="{{ route('feeding.destroy', $transaction) }}" class="inline-flex" data-confirm="Hapus transaksi Penggunaan Barang/Item {{ $transaction->transaction_number }}?" data-confirm-title="Hapus Transaksi Penggunaan Barang/Item" data-confirm-description="Catatan penggunaan {{ $transaction->feedItem->name }} di {{ $transaction->location->name }} akan dihapus. Stok tidak berubah." data-confirm-action="Hapus Transaksi" data-confirm-tone="danger">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="inline-flex size-9 items-center justify-center gap-1.5 rounded-lg border border-red-200 bg-white text-red-700 transition-colors hover:bg-red-50 xl:w-auto xl:px-2.5" aria-label="Hapus transaksi {{ $transaction->transaction_number }}" title="Hapus"><x-icon name="trash" class="size-4" /><span class="hidden text-xs font-medium xl:inline">Hapus</span></button>
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
