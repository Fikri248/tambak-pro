<x-layouts.app title="Riwayat Transaksi">
    @php
        $activeFilterCount = collect($filters)->except(['search'])->filter(fn ($value) => $value !== null && $value !== '')->count();
        $locationFilterOptions = $locations->map(fn ($location) => ['value' => $location->id, 'label' => $location->name])->all();
        $commodityFilterOptions = $commodities->map(fn ($commodity) => ['value' => $commodity->id, 'label' => $commodity->name])->all();
        $userFilterOptions = $users->map(fn ($user) => ['value' => $user->id, 'label' => $user->name])->all();
    @endphp

    <div class="space-y-6">
        <x-page-header title="Riwayat Transaksi" description="Lihat seluruh aktivitas operasional tambak dalam satu riwayat terpadu." />

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Ringkasan riwayat transaksi">
            <x-kpi-card label="Total Transaksi" :value="number_format($summary['total'], 0, ',', '.')" icon="history" />
            <x-kpi-card label="Transaksi Stok" :value="number_format($summary['stock'], 0, ',', '.')" icon="transfer" />
            <x-kpi-card label="Penggunaan Barang/Item" :value="number_format($summary['feeding'], 0, ',', '.')" icon="feed" />
            <x-kpi-card label="Aktivitas Bulan Ini" :value="number_format($summary['currentMonth'], 0, ',', '.')" icon="calendar" />
        </section>

        <x-card>
            <form method="GET" action="{{ route('history.index') }}" class="w-full">
                <div data-history-toolbar class="flex flex-col gap-3 sm:flex-row sm:items-start">
                    <div data-history-search class="min-w-0 flex-1">
                        <label for="history-search" class="sr-only">Cari Riwayat Transaksi</label>
                        <div class="relative">
                            <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-neutral-400" />
                            <input id="history-search" name="search" type="search" value="{{ old('search', $filters['search']) }}" placeholder="Cari nomor transaksi, Batch, komoditas, atau aktivitas..." class="h-10 w-full rounded-lg border border-neutral-200 bg-white pl-9 pr-3 text-sm text-neutral-800 placeholder:text-neutral-400 hover:border-neutral-300 focus:border-neutral-400 focus:outline-none focus:ring-2 focus:ring-neutral-200">
                        </div>
                        @error('search')
                            <p class="mt-1.5 text-xs font-medium text-neutral-700">{{ $message }}</p>
                        @enderror
                    </div>

                    <x-filters.panel id="history-filters" :active-count="$activeFilterCount" :open="$errors->any()" class="w-full sm:ml-auto sm:w-auto sm:shrink-0">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <x-filters.select name="type" label="Tipe Transaksi" :options="$typeLabels" :value="$filters['type']" placeholder="Semua Tipe" />
                            <x-filters.select name="location_id" label="Petak" :options="$locationFilterOptions" :value="$filters['location_id']" placeholder="Semua Petak" />
                            <x-filters.select name="commodity_id" label="Komoditas" :options="$commodityFilterOptions" :value="$filters['commodity_id']" placeholder="Semua Komoditas" />
                            <x-filters.select name="user_id" label="Dicatat Oleh" :options="$userFilterOptions" :value="$filters['user_id']" placeholder="Semua Pencatat" />

                            <x-filters.date-range class="sm:col-span-2" :from="$filters['date_from']" :to="$filters['date_to']" />

                            <div class="flex flex-col-reverse gap-2 sm:col-span-2 sm:flex-row sm:justify-end">
                                @if ($activeFilterCount > 0)
                                    <x-button variant="secondary" :href="route('history.index', $filters['search'] !== '' ? ['search' => $filters['search']] : [])">Reset Filter</x-button>
                                @endif
                                <x-button type="submit">Terapkan Filter</x-button>
                            </div>
                        </div>
                    </x-filters.panel>
                    <x-page-size id="history-per-page" :value="$history->perPage()" />
                </div>
            </form>
        </x-card>

        <div>
            <x-table-wrapper title="Aktivitas Operasional" description="Data berasal langsung dari lima modul transaksi dan hanya dapat dibaca.">
                @if ($history->isEmpty())
                    @if (array_filter($filters, fn ($value) => $value !== null && $value !== ''))
                        <x-empty-state title="Tidak ada transaksi yang sesuai dengan filter" description="Coba ubah kata kunci atau filter yang digunakan." icon="search" />
                    @else
                        <x-empty-state title="Belum ada riwayat transaksi" description="Aktivitas operasional akan tampil di sini setelah transaksi dicatat." icon="history" />
                    @endif
                @else
                    <table data-responsive-table="history" class="w-full min-w-[1180px] text-left">
                        <thead>
                            <tr class="border-b border-neutral-200 bg-neutral-50/70 text-[11px] font-semibold uppercase tracking-wider text-neutral-500">
                                <th scope="col" class="px-5 py-3 sm:px-6">No. Transaksi</th>
                                <th scope="col" class="px-5 py-3">Tanggal</th>
                                <th scope="col" class="px-5 py-3">Tipe</th>
                                <th scope="col" class="px-5 py-3">Aktivitas</th>
                                <th scope="col" class="px-5 py-3">Lokasi</th>
                                <th scope="col" class="px-5 py-3 text-right">Jumlah</th>
                                <th scope="col" class="px-5 py-3 text-right">Nilai</th>
                                <th scope="col" class="px-5 py-3">Dicatat Oleh</th>
                                <th scope="col" class="px-5 py-3 pr-6 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-100">
                            @foreach ($history as $row)
                                @php
                                    $quantityDecimals = floor(abs($row->quantity)) === abs($row->quantity) ? 0 : 3;
                                    $quantityPrefix = $row->type === 'ADJUSTMENT' && $row->quantity > 0 ? '+' : '';
                                @endphp
                                <tr class="transition-colors hover:bg-neutral-50/70">
                                    <td class="px-5 py-3.5 sm:px-6"><a href="{{ $row->detail_url }}" class="font-mono text-xs font-medium text-neutral-900 hover:underline">{{ $row->transaction_number }}</a></td>
                                    <td class="whitespace-nowrap px-5 py-3.5 text-center text-neutral-600">{{ $row->transaction_date->locale('id')->translatedFormat('d M Y, H:i') }}</td>
                                    <td class="px-5 py-3.5 text-center"><x-badge>{{ $row->type_label }}</x-badge></td>
                                    <td class="px-5 py-3.5 font-medium text-neutral-900">{{ $row->activity }}</td>
                                    <td class="px-5 py-3.5 text-center text-neutral-600">{{ $row->location_display }}</td>
                                    <td class="px-5 py-3.5 text-center font-medium tabular-nums text-neutral-900">{{ $quantityPrefix }}{{ number_format($row->quantity, $quantityDecimals, ',', '.') }} {{ $row->unit }}</td>
                                    <td class="px-5 py-3.5 text-center tabular-nums text-neutral-700">{{ $row->amount !== null ? 'Rp'.number_format($row->amount, 0, ',', '.') : '—' }}</td>
                                    <td class="px-5 py-3.5 text-neutral-600">{{ $row->user_name ?: 'Sistem / Tidak tersedia' }}</td>
                                    <td class="px-5 py-3.5 pr-6 text-center"><x-button variant="secondary" :href="$row->detail_url">Detail</x-button></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </x-table-wrapper>

            @if ($history->hasPages())
                <div class="mt-4">{{ $history->links() }}</div>
            @endif
        </div>
    </div>
</x-layouts.app>
