<x-layouts.app title="Pemindahan Stok">
    <div class="space-y-6">
        <x-page-header title="Pemindahan Stok" description="Lihat riwayat perpindahan stok antarpetak.">
            @if (auth()->user()->canAccess('movements.create'))
                <x-slot:actions>
                    <x-button :href="route('movements.create')" data-crud-modal data-crud-modal-size="xl">
                        <x-icon name="plus" class="size-4" />
                        Catat Pemindahan
                    </x-button>
                </x-slot:actions>
            @endif
        </x-page-header>

        <x-flash-message />

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Ringkasan pemindahan stok">
            <x-kpi-card label="Total Pemindahan" :value="number_format($summary['total'], 0, ',', '.')" icon="transfer" />
            <x-kpi-card label="Total Stok Dipindahkan" :value="number_format((float) $summary['quantity'], 0, ',', '.')" :suffix="$summary['unit']" icon="seedling" />
            <x-kpi-card label="Pemindahan Bulan Ini" :value="number_format($summary['this_month'], 0, ',', '.')" icon="calendar" />
            <x-kpi-card label="Petak Terlibat" :value="number_format($summary['locations'], 0, ',', '.')" suffix="petak" icon="map" />
        </section>

        @php
            $movementLocationOptions = $locations->mapWithKeys(fn ($location) => [$location->id => $location->name])->all();
            $movementCommodityOptions = $commodities->mapWithKeys(fn ($commodity) => [$commodity->id => $commodity->name])->all();
            $movementFilterCount = collect([$filters['fromLocationId'], $filters['toLocationId'], $filters['commodityId'], $filters['dateFrom'], $filters['dateTo']])
                ->filter(fn ($value) => $value !== null && $value !== '')
                ->count();
        @endphp
        <x-card>
            <form method="GET" action="{{ route('movements.index') }}" class="flex flex-col gap-3 lg:flex-row lg:items-start">
                <div class="min-w-0 flex-1">
                    <label for="search" class="sr-only">Cari pemindahan stok</label>
                    <div class="relative">
                        <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-neutral-400" />
                        <input id="search" name="search" type="search" value="{{ $filters['search'] }}" placeholder="Cari pemindahan..." class="h-10 w-full rounded-lg border border-neutral-200 bg-white pl-9 pr-3 text-sm placeholder:text-neutral-400 hover:border-neutral-300">
                    </div>
                </div>
                <x-filters.panel id="movement-filters" :active-count="$movementFilterCount" :open="$errors->any()" class="w-full lg:w-auto lg:shrink-0">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-filters.select name="from_location_id" label="Petak Asal" :options="$movementLocationOptions" :value="$filters['fromLocationId']" placeholder="Semua Asal" />
                        <x-filters.select name="to_location_id" label="Petak Tujuan" :options="$movementLocationOptions" :value="$filters['toLocationId']" placeholder="Semua Tujuan" />
                        <x-filters.select name="commodity_id" label="Komoditas" :options="$movementCommodityOptions" :value="$filters['commodityId']" placeholder="Semua Komoditas" class="sm:col-span-2" />
                        <x-filters.date-range class="sm:col-span-2" :from="$filters['dateFrom']" :to="$filters['dateTo']" />
                    </div>
                    <div class="mt-5 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                        @if (array_filter($filters, fn ($value) => $value !== null && $value !== ''))
                            <x-button variant="secondary" :href="route('movements.index')">Reset</x-button>
                        @endif
                        <x-button type="submit">Terapkan Filter</x-button>
                    </div>
                </x-filters.panel>
                <x-page-size id="movements-per-page" :value="$movements->perPage()" />
            </form>
        </x-card>

        <div>
            <x-table-wrapper title="Riwayat Pemindahan Stok" description="Admin dapat memperbarui atau membatalkan pemindahan yang masih aman terhadap riwayat stok.">
                @if ($movements->isEmpty())
                    <x-empty-state title="Belum ada Pemindahan Stok" description="Pindahkan stok Batch dari satu petak aktif ke petak aktif lainnya." icon="transfer">
                        @if (auth()->user()->canAccess('movements.create'))
                            <x-button :href="route('movements.create')" data-crud-modal data-crud-modal-size="xl">Catat Pemindahan</x-button>
                        @endif
                    </x-empty-state>
                @else
                    <table data-responsive-table="movements" class="w-full min-w-[1120px] text-left">
                        <thead>
                            <tr class="border-b border-neutral-200 bg-neutral-50/70 text-[11px] font-semibold uppercase tracking-wider text-neutral-500">
                                <th scope="col" class="px-5 py-3 text-center sm:px-6">No. Transaksi</th>
                                <th scope="col" class="px-5 py-3 text-center">Tanggal</th>
                                <th scope="col" class="px-5 py-3 text-center">Batch</th>
                                <th scope="col" class="px-5 py-3 text-center">Komoditas</th>
                                <th scope="col" class="px-5 py-3 text-center">Petak Asal</th>
                                <th scope="col" class="px-5 py-3 text-center">Petak Tujuan</th>
                                <th scope="col" class="px-5 py-3 text-center">Jumlah Dipindahkan</th>
                                <th scope="col" class="px-5 py-3 pr-6 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-100">
                            @foreach ($movements as $movement)
                                @php
                                    $quantity = (float) $movement->quantity;
                                    $decimals = floor($quantity) === $quantity ? 0 : 3;
                                @endphp
                                <tr class="transition-colors hover:bg-neutral-50/70">
                                    <td class="px-5 py-3.5 sm:px-6"><a href="{{ route('movements.show', $movement) }}" data-crud-modal data-crud-modal-size="lg" class="font-mono text-xs font-medium text-neutral-900 hover:underline">{{ $movement->transaction_number }}</a></td>
                                    <td class="whitespace-nowrap px-5 py-3.5 text-center text-neutral-600">{{ $movement->transaction_date->locale('id')->translatedFormat('d M Y, H:i') }}</td>
                                    <td class="px-5 py-3.5 text-center"><x-badge>{{ $movement->batch->batch_code }}</x-badge></td>
                                    <td class="px-5 py-3.5 font-medium text-neutral-900">{{ $movement->batch->commodity->name }}</td>
                                    <td class="px-5 py-3.5 text-center text-neutral-600">{{ $movement->fromLocation->name }}</td>
                                    <td class="px-5 py-3.5 text-center text-neutral-600">{{ $movement->toLocation->name }}</td>
                                    <td class="px-5 py-3.5 text-center font-medium tabular-nums text-neutral-900">{{ number_format($quantity, $decimals, ',', '.') }} {{ $movement->batch->commodity->unit }}</td>
                                    <td class="px-5 py-3.5 pr-6 text-center">
                                        <div class="flex items-center justify-center gap-1" data-transaction-actions>
                                            <a href="{{ route('movements.show', $movement) }}" data-crud-modal data-crud-modal-size="lg" class="inline-flex size-9 items-center justify-center gap-1.5 rounded-lg border border-neutral-200 bg-white text-neutral-700 transition-colors hover:bg-neutral-50 hover:text-neutral-950 xl:w-auto xl:px-2.5" aria-label="Lihat detail transaksi {{ $movement->transaction_number }}" title="Detail"><x-icon name="eye" class="size-4" /><span class="hidden text-xs font-medium xl:inline">Detail</span></a>
                                            @if (auth()->user()->canAccess('movements.update'))
                                                <a href="{{ route('movements.edit', $movement) }}" data-crud-modal data-crud-modal-size="xl" class="inline-flex size-9 items-center justify-center gap-1.5 rounded-lg border border-neutral-200 bg-white text-neutral-700 transition-colors hover:bg-neutral-50 hover:text-neutral-950 xl:w-auto xl:px-2.5" aria-label="Edit transaksi {{ $movement->transaction_number }}" title="Edit"><x-icon name="edit" class="size-4" /><span class="hidden text-xs font-medium xl:inline">Edit</span></a>
                                            @endif
                                            @if (auth()->user()->canAccess('movements.delete'))
                                                <form method="POST" action="{{ route('movements.destroy', $movement) }}" class="inline-flex" data-confirm="Hapus transaksi Pemindahan Stok {{ $movement->transaction_number }}?" data-confirm-title="Hapus Transaksi Pemindahan Stok" data-confirm-description="Pemindahan {{ $movement->batch->batch_code }} dari {{ $movement->fromLocation->name }} ke {{ $movement->toLocation->name }} akan dibatalkan dan dampak stok dipulihkan." data-confirm-action="Hapus Transaksi" data-confirm-tone="danger">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="inline-flex size-9 items-center justify-center gap-1.5 rounded-lg border border-red-200 bg-white text-red-700 transition-colors hover:bg-red-50 xl:w-auto xl:px-2.5" aria-label="Hapus transaksi {{ $movement->transaction_number }}" title="Hapus"><x-icon name="trash" class="size-4" /><span class="hidden text-xs font-medium xl:inline">Hapus</span></button>
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

            @if ($movements->hasPages())
                <div class="mt-4">{{ $movements->links() }}</div>
            @endif
        </div>
    </div>
</x-layouts.app>
