<x-layouts.app title="Dashboard">
    @php
        $selectedTambak = $filters['tambak_id'] ?? null;
        $currentChartFilters = array_filter(['tambak_id' => $selectedTambak]);
        $historicalFilters = array_filter([
            'tambak_id' => $selectedTambak,
            'date_from' => $period['dateFrom'],
            'date_to' => $period['dateTo'],
        ]);
        $dateFilters = [
            'date_from' => $period['dateFrom'],
            'date_to' => $period['dateTo'],
        ];
        $dashboardFilterCount = ($period['value'] !== '30d' ? 1 : 0) + ($selectedTambak ? 1 : 0);
    @endphp

    <div class="space-y-8">
        <x-page-header title="Dashboard" description="Ringkasan kondisi tambak dan aktivitas operasional.">
            <x-slot:actions>
                <div class="inline-flex items-center gap-2 text-xs text-neutral-500">
                    <x-icon name="calendar" class="size-4" />
                    <span>{{ now()->locale('id')->translatedFormat('l, d F Y') }}</span>
                </div>
            </x-slot:actions>
        </x-page-header>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Ringkasan utama">
            <x-kpi-card label="Total Tambak" :value="number_format($totalTambak, 0, ',', '.')" icon="building" />
            <x-kpi-card label="Total Komoditas" :value="number_format($totalCommodities, 0, ',', '.')" icon="package" />
            <x-kpi-card label="Total Bibit Aktif" :value="number_format((float) $totalStock, 0, ',', '.')" suffix="ekor" icon="seedling" />
            <x-kpi-card label="Total Vendor" :value="number_format($totalVendors, 0, ',', '.')" icon="truck" />
        </section>

        <x-card>
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div class="min-w-0 flex-1">
                    <h2 class="text-base font-semibold text-neutral-950">Analitik &amp; Aktivitas</h2>
                    <p class="mt-1 text-xs leading-5 text-neutral-500">
                        Periode hanya memengaruhi tren, aktivitas, dan aktivitas terakhir. Stok saat ini tetap menggunakan posisi stok terkini. Empat KPI utama tetap global.
                    </p>
                </div>
                <form method="GET" action="{{ route('dashboard') }}" class="flex shrink-0 sm:justify-end">
                    <x-filters.panel id="dashboard-filters" :active-count="$dashboardFilterCount" :open="$errors->any()" class="w-full sm:w-auto">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <x-filters.select name="period" label="Periode Tren" :options="$periodOptions" :value="$period['value']" :placeholder="null" />
                            <x-filters.select name="tambak_id" label="Tambak" :options="$tambakOptions" :value="$selectedTambak" placeholder="Semua Tambak" />

                            <div class="flex flex-col-reverse gap-2 sm:col-span-2 sm:flex-row sm:justify-end">
                                @if ($dashboardFilterCount > 0)
                                    <x-button variant="secondary" :href="route('dashboard')">Reset</x-button>
                                @endif
                                <x-button type="submit">Terapkan Filter</x-button>
                            </div>
                        </div>
                    </x-filters.panel>
                </form>
            </div>
        </x-card>

        <section aria-labelledby="current-stock-analytics-title">
            <div class="mb-3">
                <h2 id="current-stock-analytics-title" class="text-base font-semibold text-neutral-950">Posisi Stok Saat Ini</h2>
                <p class="mt-1 text-xs text-neutral-500">Stok positif terkini, tidak dipengaruhi periode historis.</p>
            </div>
            <div class="grid gap-6 xl:grid-cols-2">
                @include('dashboard.partials.chart-card', [
                    'title' => 'Stok per Tambak',
                    'description' => 'Jumlah stok aktif pada seluruh petak di setiap tambak.',
                    'chart' => $charts['stockByTambak'],
                    'chartKey' => 'stockByTambak',
                    'reportUrl' => route('reports.stock', $currentChartFilters),
                    'reportLabel' => 'Lihat laporan stok',
                    'emptyTitle' => 'Belum ada stok aktif',
                    'emptyDescription' => 'Stok per Tambak akan tampil setelah posisi stok tersedia.',
                ])

                @include('dashboard.partials.chart-card', [
                    'title' => 'Komposisi Stok per Komoditas',
                    'description' => 'Jumlah stok aktif berdasarkan komoditas dan satuannya.',
                    'chart' => $charts['stockByCommodity'],
                    'chartKey' => 'stockByCommodity',
                    'reportUrl' => route('reports.commodities'),
                    'reportLabel' => 'Lihat laporan komoditas',
                    'emptyTitle' => 'Belum ada stok aktif',
                    'emptyDescription' => 'Komposisi komoditas akan tampil setelah posisi stok tersedia.',
                ])
            </div>
        </section>

        <section aria-labelledby="trend-analytics-title">
            <div class="mb-3">
                <h2 id="trend-analytics-title" class="text-base font-semibold text-neutral-950">Tren Operasional · {{ $period['label'] }}</h2>
                <p class="mt-1 text-xs text-neutral-500">Periode {{ $period['dateFrom'] }} sampai {{ $period['dateTo'] }} dengan bucket {{ $period['bucket'] === 'day' ? 'harian' : 'bulanan' }}.</p>
            </div>
            <div class="grid gap-6 xl:grid-cols-2">
                @include('dashboard.partials.chart-card', [
                    'title' => 'Tren Pembibitan',
                    'description' => 'Jumlah bibit masuk berdasarkan transaksi pembibitan.',
                    'chart' => $charts['stockingTrend'],
                    'chartKey' => 'stockingTrend',
                    'reportUrl' => route('reports.stocking', $historicalFilters),
                    'reportLabel' => 'Lihat laporan',
                    'emptyTitle' => 'Belum ada data pada periode ini',
                    'emptyDescription' => 'Tidak ada pembibitan yang sesuai dengan filter.',
                    'cardClass' => 'xl:col-span-2',
                ])

                @include('dashboard.partials.chart-card', [
                    'title' => 'Tren Kematian',
                    'description' => 'Jumlah kematian yang tercatat melalui perubahan jumlah.',
                    'chart' => $charts['mortalityTrend'],
                    'chartKey' => 'mortalityTrend',
                    'reportUrl' => route('reports.adjustments', $dateFilters + ['type' => 'MORTALITY']),
                    'reportLabel' => 'Lihat laporan',
                    'emptyTitle' => 'Belum ada data pada periode ini',
                    'emptyDescription' => 'Tidak ada kematian yang sesuai dengan filter.',
                ])

                @include('dashboard.partials.chart-card', [
                    'title' => 'Tren Biaya Pakan, Nutrisi & Obat',
                    'description' => 'Biaya penggunaan pakan, nutrisi, obat, dan kebutuhan budidaya lain yang tercatat.',
                    'chart' => $charts['feedingCostTrend'],
                    'chartKey' => 'feedingCostTrend',
                    'reportUrl' => route('reports.feeding', $dateFilters),
                    'reportLabel' => 'Lihat laporan',
                    'emptyTitle' => 'Belum ada data pada periode ini',
                    'emptyDescription' => 'Tidak ada penggunaan pakan, nutrisi, obat, atau kebutuhan operasional yang sesuai.',
                ])

                @include('dashboard.partials.chart-card', [
                    'title' => 'Aktivitas Transaksi',
                    'description' => 'Jumlah Pembibitan, Pemindahan, Perubahan Jumlah, dan Pemberian Pakan.',
                    'chart' => $charts['transactionActivity'],
                    'chartKey' => 'transactionActivity',
                    'reportUrl' => route('history.index', $dateFilters),
                    'reportLabel' => 'Lihat riwayat',
                    'emptyTitle' => 'Belum ada data pada periode ini',
                    'emptyDescription' => 'Tidak ada transaksi operasional yang sesuai dengan filter.',
                    'cardClass' => 'xl:col-span-2',
                ])

                @include('dashboard.partials.chart-card', [
                    'title' => 'Aktivitas Akun Admin',
                    'description' => 'Jumlah aktivitas AuditLog per Admin pada periode terpilih. Grafik ini tidak dipengaruhi filter Tambak karena AuditLog tidak menyimpan relasi lokasi yang seragam.',
                    'chart' => $charts['adminActivity'],
                    'chartKey' => 'adminActivity',
                    'reportUrl' => route('history.index', $dateFilters),
                    'reportLabel' => 'Lihat riwayat',
                    'emptyTitle' => 'Belum ada aktivitas Admin',
                    'emptyDescription' => 'Seluruh akun Admin memiliki 0 aktivitas AuditLog pada periode ini.',
                    'cardClass' => 'xl:col-span-2',
                    'chartHeightClass' => 'h-[30rem] px-3 py-4 sm:h-[32rem] sm:px-5',
                ])
            </div>
        </section>

        <x-table-wrapper title="Ringkasan Petak" description="Maksimal 10 petak berstok, diurutkan dari aktivitas terbaru pada periode aktif lalu stok terbesar.">
            <x-slot:actions>
                <a href="{{ route('reports.locations', $dateFilters) }}" class="text-xs font-medium text-neutral-600 hover:text-neutral-950 hover:underline">Lihat laporan lokasi</a>
            </x-slot:actions>

            @if ($petakSummary->isEmpty())
                <x-empty-state title="Belum ada petak berisi stok" description="Ringkasan akan tampil setelah petak memiliki stok aktif." icon="map" />
            @else
                <table data-responsive-table="dashboard-petak" class="w-full min-w-[760px] text-left">
                    <thead>
                        <tr class="border-b border-neutral-200 bg-neutral-50/70 text-[11px] font-semibold uppercase tracking-wider text-neutral-500">
                            <th scope="col" class="px-5 py-3 sm:pl-6">Tambak</th>
                            <th scope="col" class="px-5 py-3">Petak</th>
                            <th scope="col" class="px-5 py-3 text-center">Batch</th>
                            <th scope="col" class="px-5 py-3">Komoditas</th>
                            <th scope="col" class="px-5 py-3 text-center">Stok Saat Ini</th>
                            <th scope="col" class="px-5 py-3 pr-6">Aktivitas Terakhir</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100">
                        @foreach ($petakSummary as $row)
                            @php
                                $stockDecimals = floor($row['stock']) === $row['stock'] ? 0 : 3;
                            @endphp
                            <tr class="transition-colors hover:bg-neutral-50/70">
                                <td class="px-5 py-3.5 sm:pl-6">{{ $row['tambak'] }}</td>
                                <td class="px-5 py-3.5 text-center"><a href="{{ $row['petakUrl'] }}" class="font-medium text-neutral-900 hover:underline">{{ $row['petak'] }}</a></td>
                                <td class="px-5 py-3.5 text-center tabular-nums">{{ number_format($row['batches'], 0, ',', '.') }}</td>
                                <td class="max-w-64 whitespace-normal px-5 py-3.5 text-neutral-600">{{ $row['commodities'] }}</td>
                                <td class="px-5 py-3.5 text-center font-medium tabular-nums text-neutral-900">{{ number_format($row['stock'], $stockDecimals, ',', '.') }} ekor</td>
                                <td class="whitespace-nowrap px-5 py-3.5 pr-6 text-center text-neutral-600">{{ $row['lastActivity'] ?? 'Tidak ada pada periode ini' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </x-table-wrapper>

        <x-card :padding="false">
            <div class="flex items-start justify-between gap-4 border-b border-neutral-200 px-5 py-4 sm:px-6">
                <div>
                    <h2 class="text-base font-semibold text-neutral-950">Aktivitas Terbaru</h2>
                    <p class="mt-1 text-xs text-neutral-500">Peristiwa AuditLog terbaru dari aktivitas aplikasi.</p>
                </div>
                <a href="{{ route('history.index') }}" class="shrink-0 text-xs font-medium text-neutral-600 hover:text-neutral-950 hover:underline">Lihat riwayat</a>
            </div>

            @if ($recentActivities->isEmpty())
                <x-empty-state title="Belum ada aktivitas" description="Aktivitas terbaru akan muncul di bagian ini." />
            @else
                <ol class="grid divide-y divide-neutral-100 lg:grid-cols-2 lg:divide-y-0">
                    @foreach ($recentActivities as $activity)
                        @php
                            $activityIcon = match ($activity->module) {
                                'STOCKING_TRANSACTION' => 'seedling',
                                'STOCK_ADJUSTMENT' => 'adjustment',
                                'STOCK_MOVEMENT' => 'transfer',
                                'FEEDING_TRANSACTION' => 'feed',
                                default => 'history',
                            };
                        @endphp
                        <li class="flex gap-3 border-neutral-100 px-5 py-4 lg:border-b lg:odd:border-r sm:px-6">
                            <span class="mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-lg border border-neutral-200 bg-neutral-50 text-neutral-500">
                                <x-icon :name="$activityIcon" class="size-4" />
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm leading-5 text-neutral-800">{{ $activity->description ?: 'Aktivitas operasional diperbarui.' }}</p>
                                <div class="mt-2 flex flex-wrap items-center gap-x-2 gap-y-1">
                                    <x-badge>{{ $activityLabels[$activity->module] ?? str($activity->module)->replace('_', ' ')->title() }}</x-badge>
                                    <span data-activity-meta class="text-xs text-neutral-500">{{ $activity->created_at->locale('id')->translatedFormat('d M Y, H:i') }}</span>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ol>
            @endif
        </x-card>
    </div>

    <script type="application/json" data-dashboard-chart-data>@json($charts, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)</script>
</x-layouts.app>
