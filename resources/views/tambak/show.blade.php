<x-layouts.app :title="'Detail Lokasi · '.$location->name">
    <div class="space-y-6">
        <div>
            <a href="{{ route('tambak.index') }}" class="mb-4 inline-flex items-center gap-2 text-sm text-neutral-500 hover:text-neutral-900">
                <x-icon name="arrow-left" class="size-4" />
                Master Tambak
            </a>

            <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-3">
                        <h1 class="text-2xl font-semibold tracking-tight text-neutral-950 sm:text-[26px]">{{ $location->name }}</h1>
                        <x-badge>{{ $location->status === 'ACTIVE' ? 'Aktif' : 'Tidak Aktif' }}</x-badge>
                    </div>
                    <p class="mt-1 text-sm text-neutral-500">{{ $location->code }} · {{ $typeLabels[$location->location_type] ?? 'Lainnya' }}</p>
                    <nav class="mt-2 flex flex-wrap items-center gap-1.5 text-xs text-neutral-500" aria-label="Hierarki lokasi">
                        @foreach ($hierarchy as $item)
                            @if (! $loop->first)<span aria-hidden="true">/</span>@endif
                            @if ($item->is($location))
                                <span class="font-medium text-neutral-700">{{ $item->name }}</span>
                            @else
                                <a href="{{ route('tambak.show', $item) }}" class="hover:text-neutral-900 hover:underline">{{ $item->name }}</a>
                            @endif
                        @endforeach
                    </nav>
                </div>

                @if (auth()->user()->canAccess('locations.manage'))
                    <div class="flex flex-wrap gap-2">
                        <x-button variant="secondary" :href="route('tambak.edit', $location)" data-crud-modal data-crud-modal-size="xl">
                            <x-icon name="edit" class="size-4" />
                            Edit Lokasi
                        </x-button>
                        <form method="POST" action="{{ route('tambak.status', $location) }}" data-confirm="{{ $location->status === 'ACTIVE' ? 'Nonaktifkan lokasi ini?' : 'Aktifkan lokasi ini?' }}" data-confirm-title="{{ $location->status === 'ACTIVE' ? 'Nonaktifkan Lokasi' : 'Aktifkan Lokasi' }}" data-confirm-action="{{ $location->status === 'ACTIVE' ? 'Nonaktifkan' : 'Aktifkan' }}">
                            @csrf
                            @method('PATCH')
                            <x-button type="submit">
                                <x-icon name="power" class="size-4" />
                                {{ $location->status === 'ACTIVE' ? 'Nonaktifkan' : 'Aktifkan' }}
                            </x-button>
                        </form>
                    </div>
                @endif
            </div>
        </div>

        <x-flash-message />

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Ringkasan {{ $location->name }}">
            @foreach ($summaryCards as $card)
                <x-kpi-card :label="$card['label']" :value="$card['value']" :suffix="$card['suffix']" :icon="$card['icon']" />
            @endforeach
        </section>

        <x-card>
            <h2 class="text-base font-semibold text-neutral-950">Informasi Lokasi</h2>
            <dl class="mt-5 grid gap-x-8 gap-y-5 sm:grid-cols-2 xl:grid-cols-3">
                <div>
                    <dt class="text-xs text-neutral-500">Kode</dt>
                    <dd class="mt-1 font-mono text-sm font-medium text-neutral-900">{{ $location->code }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-neutral-500">Tipe</dt>
                    <dd class="mt-1 text-sm font-medium text-neutral-900">{{ $typeLabels[$location->location_type] ?? 'Lainnya' }}</dd>
                </div>
                @if ($location->parent)
                    <div>
                        <dt class="text-xs text-neutral-500">Induk Lokasi</dt>
                        <dd class="mt-1 text-sm font-medium text-neutral-900">
                            <a href="{{ route('tambak.show', $location->parent) }}" class="hover:underline">{{ $location->parent->name }}</a>
                        </dd>
                    </div>
                @endif
                @if ($location->address)
                    <div class="sm:col-span-2">
                        <dt class="text-xs text-neutral-500">Alamat</dt>
                        <dd class="mt-1 text-sm leading-6 text-neutral-800">{{ $location->address }}</dd>
                    </div>
                @endif
                @if ($location->description)
                    <div class="sm:col-span-2">
                        <dt class="text-xs text-neutral-500">Deskripsi</dt>
                        <dd class="mt-1 text-sm leading-6 text-neutral-800">{{ $location->description }}</dd>
                    </div>
                @endif
                <div>
                    <dt class="text-xs text-neutral-500">Status</dt>
                    <dd class="mt-1 text-sm font-medium text-neutral-900">{{ $location->status === 'ACTIVE' ? 'Aktif' : 'Tidak Aktif' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-neutral-500">Dibuat</dt>
                    <dd class="mt-1 text-sm text-neutral-800">{{ $location->created_at->locale('id')->translatedFormat('d F Y, H:i') }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-neutral-500">Terakhir diperbarui</dt>
                    <dd class="mt-1 text-sm text-neutral-800">{{ $location->updated_at->locale('id')->translatedFormat('d F Y, H:i') }}</dd>
                </div>
            </dl>
        </x-card>

        @if ($children->isNotEmpty())
            <x-table-wrapper title="Lokasi Anak" description="Lokasi yang berada langsung di bawah {{ $location->name }}.">
                <table class="w-full min-w-[640px] text-left">
                    <thead>
                        <tr class="border-b border-neutral-200 bg-neutral-50/70 text-[11px] font-semibold uppercase tracking-wider text-neutral-500">
                            <th scope="col" class="px-5 py-3 sm:px-6">Kode</th>
                            <th scope="col" class="px-5 py-3">Nama</th>
                            <th scope="col" class="px-5 py-3">Tipe</th>
                            <th scope="col" class="px-5 py-3">Status</th>
                            <th scope="col" class="px-5 py-3 pr-6 text-right">Stok Aktif</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100">
                        @foreach ($children as $child)
                            <tr class="transition-colors hover:bg-neutral-50/70">
                                <td class="px-5 py-3.5 font-mono text-xs text-neutral-600 sm:px-6">{{ $child->code }}</td>
                                <td class="px-5 py-3.5"><a href="{{ route('tambak.show', $child) }}" class="font-medium text-neutral-900 hover:underline">{{ $child->name }}</a></td>
                                <td class="px-5 py-3.5 text-center"><x-badge>{{ $typeLabels[$child->location_type] ?? 'Lainnya' }}</x-badge></td>
                                <td class="px-5 py-3.5 text-center text-neutral-700">{{ $child->status === 'ACTIVE' ? 'Aktif' : 'Tidak Aktif' }}</td>
                                <td class="px-5 py-3.5 pr-6 text-right font-medium tabular-nums text-neutral-900">
                                    {{ number_format((float) ($child->active_stock_sum ?? 0), 0, ',', '.') }} ekor
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </x-table-wrapper>
        @endif

        @if ($location->location_type === 'PETAK')
            <x-table-wrapper title="Stok di Petak Saat Ini" description="Posisi stok aktif per Batch di {{ $location->name }}.">
                @if ($currentStocks->isEmpty())
                    <x-empty-state title="Belum ada stok aktif" description="Petak ini belum memiliki stok komoditas aktif." icon="package" />
                @else
                    <table class="w-full min-w-[980px] text-left">
                        <thead>
                            <tr class="border-b border-neutral-200 bg-neutral-50/70 text-[11px] font-semibold uppercase tracking-wider text-neutral-500">
                                <th scope="col" class="px-5 py-3 sm:px-6">Batch</th>
                                <th scope="col" class="px-5 py-3">Komoditas</th>
                                <th scope="col" class="px-5 py-3">Vendor</th>
                                <th scope="col" class="px-5 py-3">Tanggal Masuk</th>
                                <th scope="col" class="px-5 py-3 text-right">Jumlah</th>
                                <th scope="col" class="px-5 py-3 text-right">Harga / Ekor</th>
                                <th scope="col" class="px-5 py-3 pr-6 text-right">Nilai Stok</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-100">
                            @foreach ($currentStocks as $stock)
                                @php
                                    $quantity = (float) $stock->quantity;
                                    $unitCost = (float) $stock->batch->unit_cost;
                                    $quantityDecimals = floor($quantity) === $quantity ? 0 : 3;
                                @endphp
                                <tr class="transition-colors hover:bg-neutral-50/70">
                                    <td class="px-5 py-3.5 text-center font-mono text-xs font-medium text-neutral-700 sm:px-6">{{ $stock->batch->batch_code }}</td>
                                    <td class="px-5 py-3.5 font-medium text-neutral-900">{{ $stock->batch->commodity->name }}</td>
                                    <td class="px-5 py-3.5 text-neutral-600">{{ $stock->batch->vendor?->name ?? 'Tidak tercatat' }}</td>
                                    <td class="px-5 py-3.5 text-center text-neutral-600">{{ $stock->batch->purchase_date->locale('id')->translatedFormat('d M Y') }}</td>
                                    <td class="px-5 py-3.5 text-right font-medium tabular-nums text-neutral-900">{{ number_format($quantity, $quantityDecimals, ',', '.') }} {{ $stock->batch->commodity->unit }}</td>
                                    <td class="px-5 py-3.5 text-right tabular-nums text-neutral-700">Rp{{ number_format($unitCost, 0, ',', '.') }}</td>
                                    <td class="px-5 py-3.5 pr-6 text-right font-medium tabular-nums text-neutral-900">Rp{{ number_format($quantity * $unitCost, 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </x-table-wrapper>
        @else
            <x-table-wrapper title="Ringkasan Stok" description="Akumulasi stok aktif pada petak di bawah {{ $location->name }}.">
                @if ($aggregatedStocks->isEmpty())
                    <x-empty-state title="Belum ada stok aktif" description="Belum ada stok pada petak di bawah lokasi ini." icon="package" />
                @else
                    <table class="w-full min-w-[560px] text-left">
                        <thead>
                            <tr class="border-b border-neutral-200 bg-neutral-50/70 text-[11px] font-semibold uppercase tracking-wider text-neutral-500">
                                <th scope="col" class="px-5 py-3 sm:px-6">Komoditas</th>
                                <th scope="col" class="px-5 py-3 text-right">Jumlah Batch</th>
                                <th scope="col" class="px-5 py-3 pr-6 text-right">Total Stok</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-100">
                            @foreach ($aggregatedStocks as $stock)
                                <tr class="transition-colors hover:bg-neutral-50/70">
                                    <td class="px-5 py-3.5 font-medium text-neutral-900 sm:px-6">{{ $stock['commodity']->name }}</td>
                                    <td class="px-5 py-3.5 text-right tabular-nums text-neutral-700">{{ number_format($stock['batch_count'], 0, ',', '.') }}</td>
                                    <td class="px-5 py-3.5 pr-6 text-right font-medium tabular-nums text-neutral-900">{{ number_format($stock['quantity'], 0, ',', '.') }} {{ $stock['commodity']->unit }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </x-table-wrapper>
        @endif

        <x-card :padding="false">
            <div class="border-b border-neutral-200 px-5 py-4 sm:px-6">
                <h2 class="text-base font-semibold text-neutral-950">Aktivitas Terbaru</h2>
                <p class="mt-1 text-xs text-neutral-500">Transaksi operasional terbaru yang terkait dengan lokasi ini.</p>
            </div>
            @if ($recentActivities->isEmpty())
                <x-empty-state title="Belum ada aktivitas" description="Aktivitas operasional lokasi akan tampil di bagian ini." />
            @else
                <ol class="divide-y divide-neutral-100">
                    @foreach ($recentActivities as $activity)
                        <li class="flex gap-3 px-5 py-4 sm:px-6">
                            <span class="mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-lg border border-neutral-200 bg-neutral-50 text-neutral-500">
                                <x-icon :name="$activity['icon']" class="size-4" />
                            </span>
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <x-badge>{{ $activity['type'] }}</x-badge>
                                    <span class="text-xs text-neutral-500">{{ $activity['date']->locale('id')->translatedFormat('d M Y, H:i') }}</span>
                                </div>
                                <p class="mt-1.5 text-sm leading-5 text-neutral-800">{{ $activity['description'] }}</p>
                                <p class="mt-1 text-xs text-neutral-500">{{ $activity['user'] }}</p>
                            </div>
                        </li>
                    @endforeach
                </ol>
            @endif
        </x-card>
    </div>
</x-layouts.app>
