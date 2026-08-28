<x-layouts.app :title="$title">
    @php
        $currentRouteName = (string) request()->route()?->getName();
        $responsiveReport = match ($currentRouteName) {
            'reports.stock' => 'report-stock',
            'reports.stocking' => 'report-stocking',
            'reports.movements' => 'report-movements',
            'reports.adjustments' => 'report-adjustments',
            'reports.feeding' => 'report-feeding',
            'reports.vendors' => 'report-vendors',
            'reports.commodities' => 'report-commodities',
            'reports.locations' => 'report-locations',
            default => 'report',
        };
        $isStockReport = $currentRouteName === 'reports.stock';
        $filterFieldCollection = collect($filterFields);
        $toolbarSearchField = $isStockReport
            ? $filterFieldCollection->firstWhere('name', 'search')
            : null;
        $panelFilterFields = $isStockReport
            ? $filterFieldCollection->reject(fn (array $field): bool => $field['name'] === 'search')->values()
            : $filterFieldCollection;
        $activeFilterCount = collect($filters)
            ->when($isStockReport, fn ($values) => $values->except('search'))
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->count();
        $resetFilters = $isStockReport && ($filters['search'] ?? '') !== ''
            ? ['search' => $filters['search']]
            : [];
        $resetUrl = $isStockReport ? route($currentRouteName, $resetFilters) : $route;
        $panelHasErrors = $isStockReport
            ? $errors->hasAny($panelFilterFields->pluck('name')->all())
            : $errors->any();
    @endphp

    <div class="space-y-6">
        <a href="{{ route('reports.index') }}" class="inline-flex items-center gap-2 text-xs font-medium text-neutral-500 hover:text-neutral-900">
            <x-icon name="arrow-left" class="size-4" />
            Laporan Operasional
        </a>

        @php
            $exportFilters = collect(request()->query())->except(['page', 'per_page', 'format'])->all();
        @endphp

        <x-page-header :title="$title" :description="$description">
            <x-slot:actions>
                <div class="flex flex-wrap items-center gap-2">
                    <x-button variant="secondary" :href="route($printRoute, $exportFilters)">
                        <x-icon name="printer" class="size-4" />
                        Cetak
                    </x-button>
                    <x-button variant="secondary" :href="route($pdfRoute, $exportFilters)">
                        <x-icon name="download" class="size-4" />
                        Download PDF
                    </x-button>
                    <x-button variant="secondary" :href="route($exportRoute, [...$exportFilters, 'format' => 'csv'])">
                        <x-icon name="download" class="size-4" />
                        Export CSV
                    </x-button>
                    <x-button variant="secondary" :href="route($exportRoute, [...$exportFilters, 'format' => 'xlsx'])">
                        <x-icon name="download" class="size-4" />
                        Export Excel
                    </x-button>
                </div>
            </x-slot:actions>
        </x-page-header>

        <p class="-mt-3 text-xs text-neutral-500">Cetak dan file ekspor mengikuti filter aktif serta memuat seluruh hasil, bukan hanya halaman saat ini.</p>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Ringkasan laporan">
            @foreach ($summaryCards as $card)
                <x-kpi-card :label="$card['label']" :value="$card['value']" :suffix="$card['suffix']" :icon="$card['icon']" />
            @endforeach
        </section>

        @if ($notice)
            <div class="rounded-lg border border-neutral-200 bg-neutral-50 px-4 py-3 text-xs leading-5 text-neutral-600">
                {{ $notice }}
            </div>
        @endif

        <x-card>
            <form
                method="GET"
                action="{{ $route }}"
                data-report-filter-toolbar
                class="flex min-w-0 flex-col gap-3 sm:flex-row sm:items-start {{ $isStockReport ? '' : 'sm:justify-end' }}"
            >
                @if ($toolbarSearchField)
                    <div data-report-stock-search class="min-w-0 flex-1">
                        <label for="report-stock-search" class="sr-only">Cari Laporan Stok</label>
                        <div class="relative">
                            <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-neutral-400" />
                            <input
                                id="report-stock-search"
                                name="search"
                                type="search"
                                value="{{ old('search', $filters['search'] ?? '') }}"
                                placeholder="{{ $toolbarSearchField['placeholder'] }}"
                                class="h-10 w-full rounded-lg border border-neutral-200 bg-white pl-9 pr-3 text-sm text-neutral-800 placeholder:text-neutral-400 hover:border-neutral-300 focus:border-neutral-400 focus:outline-none focus:ring-2 focus:ring-neutral-200"
                            >
                        </div>
                        @error('search')
                            <p class="mt-1.5 text-xs font-medium text-neutral-700">{{ $message }}</p>
                        @enderror
                    </div>
                @endif

                <x-filters.panel
                    id="{{ $responsiveReport }}-filters"
                    :active-count="$activeFilterCount"
                    :open="$panelHasErrors"
                    class="{{ $isStockReport ? 'shrink-0' : 'w-full sm:w-auto' }}"
                >
                    <div class="grid gap-4 sm:grid-cols-2">
                        @foreach ($panelFilterFields as $field)
                            @if ($field['type'] === 'select')
                                @php
                                    $selectLabel = preg_replace('/^Semua\s+/u', '', $field['label']) ?: $field['label'];
                                @endphp
                                <x-filters.select
                                    :name="$field['name']"
                                    :label="$selectLabel"
                                    :options="$field['options']"
                                    :value="$filters[$field['name']] ?? null"
                                    :placeholder="$field['label']"
                                />
                            @elseif ($field['type'] === 'date')
                                @if ($field['name'] === 'date_from')
                                    <x-filters.date-range class="sm:col-span-2" :from="$filters['date_from'] ?? null" :to="$filters['date_to'] ?? null" />
                                @endif
                            @else
                                <div class="sm:col-span-2">
                                    <label for="{{ $field['name'] }}" class="mb-1.5 block text-xs font-medium text-neutral-700">{{ $field['label'] }}</label>
                                    <div class="relative">
                                        <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-neutral-400" />
                                        <input id="{{ $field['name'] }}" name="{{ $field['name'] }}" type="search" value="{{ old($field['name'], $filters[$field['name']] ?? '') }}" placeholder="{{ $field['placeholder'] }}" class="h-10 w-full rounded-lg border border-neutral-200 bg-white pl-9 pr-3 text-sm text-neutral-800 placeholder:text-neutral-400 hover:border-neutral-300 focus:border-neutral-400 focus:outline-none focus:ring-2 focus:ring-neutral-200">
                                    </div>
                                    @error($field['name'])
                                        <p class="mt-1.5 text-xs font-medium text-neutral-700">{{ $message }}</p>
                                    @enderror
                                </div>
                            @endif
                        @endforeach

                        <div class="flex flex-col-reverse gap-2 sm:col-span-2 sm:flex-row sm:justify-end">
                            @if ($activeFilterCount > 0)
                                <x-button variant="secondary" :href="$resetUrl">{{ $isStockReport ? 'Reset Filter' : 'Reset' }}</x-button>
                            @endif
                            <x-button type="submit">Terapkan Filter</x-button>
                        </div>
                    </div>
                </x-filters.panel>

                <x-page-size id="report-per-page" :value="$rows->perPage()" />
            </form>
        </x-card>

        @if (isset($secondary))
            <x-table-wrapper :title="$secondary['title']" :description="$secondary['description']">
                @if (empty($secondary['rows']))
                    <x-empty-state title="Belum ada data penggunaan" description="Ringkasan akan tampil setelah transaksi yang sesuai tersedia." icon="feed" />
                @else
                    <table data-responsive-table="report-feeding-summary" class="w-full min-w-[760px] text-left">
                        <thead>
                            <tr class="border-b border-neutral-200 bg-neutral-50/70 text-[11px] font-semibold uppercase tracking-wider text-neutral-500">
                                @foreach ($secondary['columns'] as $column)
                                    <th scope="col" class="px-5 py-3 first:pl-6 last:pr-6">{{ $column }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-100">
                            @foreach ($secondary['rows'] as $row)
                                @include('reports.partials.row', ['row' => $row])
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </x-table-wrapper>
        @endif

        <div>
            <x-table-wrapper :title="$tableTitle" description="Data laporan bersifat hanya-baca dan berasal langsung dari sumber operasional.">
                @if ($rows->isEmpty())
                    @if (array_filter($filters, fn ($value) => $value !== null && $value !== ''))
                        <x-empty-state title="Tidak ada data yang sesuai dengan filter" description="Coba ubah filter atau rentang tanggal." icon="search" />
                    @else
                        <x-empty-state title="Belum ada data untuk laporan ini" description="Data akan muncul setelah aktivitas operasional dicatat." icon="report" />
                    @endif
                @else
                    <table data-responsive-table="{{ $responsiveReport }}" class="w-full min-w-[980px] text-left">
                        <thead>
                            <tr class="border-b border-neutral-200 bg-neutral-50/70 text-[11px] font-semibold uppercase tracking-wider text-neutral-500">
                                @foreach ($columns as $column)
                                    <th scope="col" class="whitespace-nowrap px-5 py-3 first:pl-6 last:pr-6">{{ $column }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-100">
                            @foreach ($rows as $row)
                                @include('reports.partials.row', ['row' => $row])
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </x-table-wrapper>

            @if ($rows->hasPages())
                <div class="mt-4">{{ $rows->links() }}</div>
            @endif
        </div>
    </div>
</x-layouts.app>
