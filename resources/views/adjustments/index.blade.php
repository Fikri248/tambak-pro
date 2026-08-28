<x-layouts.app title="Perubahan Jumlah">
    <div class="space-y-6">
        <x-page-header title="Perubahan Jumlah" description="Kelola riwayat kematian, kehilangan, dan penyesuaian stok bibit.">
            @if (auth()->user()->canAccess('adjustments.create'))
                <x-slot:actions>
                    <x-button :href="route('adjustments.create')" data-crud-modal data-crud-modal-size="xl">
                        <x-icon name="plus" class="size-4" />
                        Tambah Perubahan
                    </x-button>
                </x-slot:actions>
            @endif
        </x-page-header>

        <x-flash-message />

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Ringkasan perubahan jumlah">
            <x-kpi-card label="Total Perubahan" :value="number_format($summary['total'], 0, ',', '.')" icon="adjustment" />
            <x-kpi-card label="Total Kematian" :value="number_format($summary['mortality'], 0, ',', '.')" :suffix="$summary['unit']" icon="warning" />
            <x-kpi-card label="Total Kehilangan" :value="number_format($summary['loss'], 0, ',', '.')" :suffix="$summary['unit']" icon="package" />
            <x-kpi-card label="Penyesuaian Tercatat" :value="number_format($summary['corrections'], 0, ',', '.')" suffix="transaksi" icon="check" />
        </section>

        @php
            $adjustmentLocationOptions = $locations->mapWithKeys(fn ($location) => [$location->id => $location->name])->all();
            $adjustmentCommodityOptions = $commodities->mapWithKeys(fn ($commodity) => [$commodity->id => $commodity->name])->all();
            $adjustmentFilterCount = collect([$filters['type'], $filters['locationId'], $filters['commodityId'], $filters['dateFrom'], $filters['dateTo']])
                ->filter(fn ($value) => $value !== null && $value !== '')
                ->count();
        @endphp
        <x-card>
            <form method="GET" action="{{ route('adjustments.index') }}" class="flex flex-col gap-3 lg:flex-row lg:items-start">
                <div class="min-w-0 flex-1">
                    <label for="search" class="sr-only">Cari perubahan</label>
                    <div class="relative">
                        <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-neutral-400" />
                        <input id="search" name="search" type="search" value="{{ $filters['search'] }}" placeholder="Cari perubahan..." class="h-10 w-full rounded-lg border border-neutral-200 bg-white pl-9 pr-3 text-sm placeholder:text-neutral-400 hover:border-neutral-300">
                    </div>
                </div>
                <x-filters.panel id="adjustment-filters" :active-count="$adjustmentFilterCount" :open="$errors->any()" class="w-full lg:w-auto lg:shrink-0">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-filters.select name="type" label="Jenis Perubahan" :options="$typeLabels" :value="$filters['type']" placeholder="Semua Jenis" />
                        <x-filters.select name="location_id" label="Petak" :options="$adjustmentLocationOptions" :value="$filters['locationId']" placeholder="Semua Petak" />
                        <x-filters.select name="commodity_id" label="Komoditas" :options="$adjustmentCommodityOptions" :value="$filters['commodityId']" placeholder="Semua Komoditas" class="sm:col-span-2" />
                        <x-filters.date-range class="sm:col-span-2" :from="$filters['dateFrom']" :to="$filters['dateTo']" />
                    </div>
                    <div class="mt-5 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                        @if (array_filter($filters, fn ($value) => $value !== null && $value !== ''))
                            <x-button variant="secondary" :href="route('adjustments.index')">Reset</x-button>
                        @endif
                        <x-button type="submit">Terapkan Filter</x-button>
                    </div>
                </x-filters.panel>
                <x-page-size id="adjustments-per-page" :value="$adjustments->perPage()" />
            </form>
        </x-card>

        <div>
            <x-table-wrapper title="Riwayat Perubahan Jumlah" description="Admin dapat memperbarui atau membatalkan perubahan yang belum memiliki aktivitas lanjutan.">
                @if ($adjustments->isEmpty())
                    <x-empty-state title="Belum ada perubahan jumlah" description="Catat kematian, kehilangan, atau penyesuaian stok agar riwayat tetap dapat ditelusuri." icon="adjustment">
                        @if (auth()->user()->canAccess('adjustments.create'))
                            <x-button :href="route('adjustments.create')" data-crud-modal data-crud-modal-size="xl">Tambah Perubahan</x-button>
                        @endif
                    </x-empty-state>
                @else
                    <table data-responsive-table="adjustments" class="w-full min-w-[1240px] text-left">
                        <thead>
                            <tr class="border-b border-neutral-200 bg-neutral-50/70 text-[11px] font-semibold uppercase tracking-wider text-neutral-500">
                                <th scope="col" class="px-5 py-3 text-center sm:px-6">No. Transaksi</th>
                                <th scope="col" class="px-5 py-3 text-center">Tanggal</th>
                                <th scope="col" class="px-5 py-3 text-center">Jenis</th>
                                <th scope="col" class="px-5 py-3 text-center">Batch</th>
                                <th scope="col" class="px-5 py-3 text-center">Komoditas</th>
                                <th scope="col" class="px-5 py-3 text-center">Petak</th>
                                <th scope="col" class="px-5 py-3 text-center">Sebelum</th>
                                <th scope="col" class="px-5 py-3 text-center">Perubahan</th>
                                <th scope="col" class="px-5 py-3 text-center">Sesudah</th>
                                <th scope="col" class="px-5 py-3 pr-6 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-100">
                            @foreach ($adjustments as $adjustment)
                                @php
                                    $before = (float) $adjustment->quantity_before;
                                    $change = (float) $adjustment->quantity_change;
                                    $after = (float) $adjustment->quantity_after;
                                    $unit = $adjustment->batch->commodity->unit;
                                    $formatQuantity = fn (float $value): string => number_format($value, floor($value) === $value ? 0 : 3, ',', '.');
                                @endphp
                                <tr class="transition-colors hover:bg-neutral-50/70">
                                    <td class="px-5 py-3.5 sm:px-6"><a href="{{ route('adjustments.show', $adjustment) }}" data-crud-modal data-crud-modal-size="lg" class="font-mono text-xs font-medium text-neutral-900 hover:underline">{{ $adjustment->transaction_number }}</a></td>
                                    <td class="whitespace-nowrap px-5 py-3.5 text-center text-neutral-600">{{ $adjustment->transaction_date->locale('id')->translatedFormat('d M Y, H:i') }}</td>
                                    <td class="px-5 py-3.5 text-center"><x-badge>{{ $typeLabels[$adjustment->adjustment_type] }}</x-badge></td>
                                    <td class="px-5 py-3.5 text-center"><x-badge>{{ $adjustment->batch->batch_code }}</x-badge></td>
                                    <td class="px-5 py-3.5 font-medium text-neutral-900">{{ $adjustment->batch->commodity->name }}</td>
                                    <td class="px-5 py-3.5 text-center text-neutral-600">{{ $adjustment->location->name }}</td>
                                    <td class="px-5 py-3.5 text-center tabular-nums text-neutral-700">{{ $formatQuantity($before) }} {{ $unit }}</td>
                                    <td class="px-5 py-3.5 text-center font-medium tabular-nums text-neutral-900">{{ $change > 0 ? '+' : '' }}{{ $formatQuantity($change) }} {{ $unit }}</td>
                                    <td class="px-5 py-3.5 text-center tabular-nums text-neutral-700">{{ $formatQuantity($after) }} {{ $unit }}</td>
                                    <td class="px-5 py-3.5 pr-6 text-center">
                                        <div class="flex items-center justify-center gap-1" data-transaction-actions>
                                            <a href="{{ route('adjustments.show', $adjustment) }}" data-crud-modal data-crud-modal-size="lg" class="inline-flex size-9 items-center justify-center gap-1.5 rounded-lg border border-neutral-200 bg-white text-neutral-700 transition-colors hover:bg-neutral-50 hover:text-neutral-950 xl:w-auto xl:px-2.5" aria-label="Lihat detail transaksi {{ $adjustment->transaction_number }}" title="Detail"><x-icon name="eye" class="size-4" /><span class="hidden text-xs font-medium xl:inline">Detail</span></a>
                                            @if (auth()->user()->canAccess('adjustments.update'))
                                                <a href="{{ route('adjustments.edit', $adjustment) }}" data-crud-modal data-crud-modal-size="xl" class="inline-flex size-9 items-center justify-center gap-1.5 rounded-lg border border-neutral-200 bg-white text-neutral-700 transition-colors hover:bg-neutral-50 hover:text-neutral-950 xl:w-auto xl:px-2.5" aria-label="Edit transaksi {{ $adjustment->transaction_number }}" title="Edit"><x-icon name="edit" class="size-4" /><span class="hidden text-xs font-medium xl:inline">Edit</span></a>
                                            @endif
                                            @if (auth()->user()->canAccess('adjustments.delete'))
                                                <form method="POST" action="{{ route('adjustments.destroy', $adjustment) }}" class="inline-flex" data-confirm="Hapus transaksi Perubahan Jumlah {{ $adjustment->transaction_number }}?" data-confirm-title="Hapus Transaksi Perubahan Jumlah" data-confirm-description="Perubahan {{ $adjustment->batch->batch_code }} di {{ $adjustment->location->name }} akan dibatalkan dan dampak stok dipulihkan." data-confirm-action="Hapus Transaksi" data-confirm-tone="danger">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="inline-flex size-9 items-center justify-center gap-1.5 rounded-lg border border-red-200 bg-white text-red-700 transition-colors hover:bg-red-50 xl:w-auto xl:px-2.5" aria-label="Hapus transaksi {{ $adjustment->transaction_number }}" title="Hapus"><x-icon name="trash" class="size-4" /><span class="hidden text-xs font-medium xl:inline">Hapus</span></button>
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

            @if ($adjustments->hasPages())
                <div class="mt-4">{{ $adjustments->links() }}</div>
            @endif
        </div>
    </div>
</x-layouts.app>
