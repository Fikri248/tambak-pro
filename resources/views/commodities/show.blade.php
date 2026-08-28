<x-layouts.app :title="'Detail Komoditas · '.$commodity->name">
    <div class="space-y-6">
        <div>
            <a href="{{ route('commodities.index') }}" class="mb-4 inline-flex items-center gap-2 text-sm text-neutral-500 hover:text-neutral-900">
                <x-icon name="arrow-left" class="size-4" />
                Master Komoditas
            </a>

            <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-3">
                        <h1 class="text-2xl font-semibold tracking-tight text-neutral-950 sm:text-[26px]">{{ $commodity->name }}</h1>
                        <x-badge>{{ $commodity->status === 'ACTIVE' ? 'Aktif' : 'Tidak Aktif' }}</x-badge>
                    </div>
                    <p class="mt-1 text-sm text-neutral-500">{{ $commodity->code }}{{ $commodity->category ? ' · '.$commodity->category : '' }}</p>
                </div>

                @if (auth()->user()->canAccess('commodities.manage'))
                    <div class="flex flex-wrap gap-2">
                        <x-button variant="secondary" :href="route('commodities.edit', $commodity)" data-crud-modal data-crud-modal-size="xl">
                            <x-icon name="edit" class="size-4" />
                            Edit Komoditas
                        </x-button>
                        <form method="POST" action="{{ route('commodities.status', $commodity) }}" data-confirm="{{ $commodity->status === 'ACTIVE' ? 'Nonaktifkan komoditas ini?' : 'Aktifkan komoditas ini?' }}" data-confirm-title="{{ $commodity->status === 'ACTIVE' ? 'Nonaktifkan Komoditas' : 'Aktifkan Komoditas' }}" data-confirm-action="{{ $commodity->status === 'ACTIVE' ? 'Nonaktifkan' : 'Aktifkan' }}">
                            @csrf
                            @method('PATCH')
                            <x-button type="submit">
                                <x-icon name="power" class="size-4" />
                                {{ $commodity->status === 'ACTIVE' ? 'Nonaktifkan' : 'Aktifkan' }}
                            </x-button>
                        </form>
                    </div>
                @endif
            </div>
        </div>

        <x-flash-message />

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Ringkasan {{ $commodity->name }}">
            <x-kpi-card label="Total Stok" :value="number_format((float) $summary['total_stock'], 0, ',', '.')" :suffix="$commodity->unit" icon="seedling" />
            <x-kpi-card label="Batch Aktif" :value="number_format($summary['active_batches'], 0, ',', '.')" icon="package" />
            <x-kpi-card label="Lokasi Aktif" :value="number_format($summary['active_locations'], 0, ',', '.')" suffix="lokasi" icon="map" />
            <x-kpi-card label="Total Nilai Stok" :value="'Rp'.number_format((float) $summary['stock_value'], 0, ',', '.')" icon="coins" />
        </section>

        <x-card>
            <h2 class="text-base font-semibold text-neutral-950">Informasi Komoditas</h2>
            <dl class="mt-5 grid gap-x-8 gap-y-5 sm:grid-cols-2 xl:grid-cols-4">
                <div>
                    <dt class="text-xs text-neutral-500">Kode</dt>
                    <dd class="mt-1 font-mono text-sm font-medium text-neutral-900">{{ $commodity->code }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-neutral-500">Nama</dt>
                    <dd class="mt-1 text-sm font-medium text-neutral-900">{{ $commodity->name }}</dd>
                </div>
                @if ($commodity->category)
                    <div>
                        <dt class="text-xs text-neutral-500">Kategori</dt>
                        <dd class="mt-1 text-sm font-medium text-neutral-900">{{ $commodity->category }}</dd>
                    </div>
                @endif
                <div>
                    <dt class="text-xs text-neutral-500">Satuan</dt>
                    <dd class="mt-1 text-sm font-medium text-neutral-900">{{ $commodity->unit }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-neutral-500">Status</dt>
                    <dd class="mt-1 text-sm font-medium text-neutral-900">{{ $commodity->status === 'ACTIVE' ? 'Aktif' : 'Tidak Aktif' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-neutral-500">Dibuat</dt>
                    <dd class="mt-1 text-sm text-neutral-800">{{ $commodity->created_at->locale('id')->translatedFormat('d F Y, H:i') }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-neutral-500">Terakhir diperbarui</dt>
                    <dd class="mt-1 text-sm text-neutral-800">{{ $commodity->updated_at->locale('id')->translatedFormat('d F Y, H:i') }}</dd>
                </div>
                @if ($commodity->description)
                    <div class="sm:col-span-2 xl:col-span-4">
                        <dt class="text-xs text-neutral-500">Deskripsi</dt>
                        <dd class="mt-1 text-sm leading-6 text-neutral-800">{{ $commodity->description }}</dd>
                    </div>
                @endif
            </dl>
        </x-card>

        <x-table-wrapper title="Batch Aktif" description="Batch aktif yang menggunakan {{ $commodity->name }}.">
            @if ($activeBatches->isEmpty())
                <x-empty-state title="Belum ada Batch aktif" description="Komoditas ini belum memiliki Batch operasional aktif." icon="package" />
            @else
                <table class="w-full min-w-[900px] text-left">
                    <thead>
                        <tr class="border-b border-neutral-200 bg-neutral-50/70 text-[11px] font-semibold uppercase tracking-wider text-neutral-500">
                            <th scope="col" class="px-5 py-3 sm:px-6">Batch</th>
                            <th scope="col" class="px-5 py-3">Vendor</th>
                            <th scope="col" class="px-5 py-3">Tanggal Pembelian</th>
                            <th scope="col" class="px-5 py-3 text-right">Jumlah Awal</th>
                            <th scope="col" class="px-5 py-3 text-right">Stok Saat Ini</th>
                            <th scope="col" class="px-5 py-3 text-right">Harga per Satuan</th>
                            <th scope="col" class="px-5 py-3 pr-6">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100">
                        @foreach ($activeBatches as $batch)
                            @php
                                $initial = (float) $batch->initial_quantity;
                                $current = (float) ($batch->current_stock ?? 0);
                            @endphp
                            <tr class="transition-colors hover:bg-neutral-50/70">
                                <td class="px-5 py-3.5 text-center font-mono text-xs font-medium text-neutral-700 sm:px-6">{{ $batch->batch_code }}</td>
                                <td class="px-5 py-3.5 text-neutral-600">{{ $batch->vendor?->name ?? 'Tidak tercatat' }}</td>
                                <td class="px-5 py-3.5 text-center text-neutral-600">{{ $batch->purchase_date->locale('id')->translatedFormat('d M Y') }}</td>
                                <td class="px-5 py-3.5 text-right tabular-nums text-neutral-700">{{ number_format($initial, floor($initial) === $initial ? 0 : 3, ',', '.') }} {{ $commodity->unit }}</td>
                                <td class="px-5 py-3.5 text-right font-medium tabular-nums text-neutral-900">{{ number_format($current, floor($current) === $current ? 0 : 3, ',', '.') }} {{ $commodity->unit }}</td>
                                <td class="px-5 py-3.5 text-right tabular-nums text-neutral-700">Rp{{ number_format((float) $batch->unit_cost, 0, ',', '.') }}</td>
                                <td class="px-5 py-3.5 pr-6 text-center"><x-badge>Aktif</x-badge></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </x-table-wrapper>

        <x-table-wrapper title="Sebaran Stok" description="Posisi stok saat ini berdasarkan lokasi dan Batch.">
            @if ($stockDistribution->isEmpty())
                <x-empty-state title="Belum ada sebaran stok" description="Stok positif komoditas ini belum tercatat di lokasi mana pun." icon="map" />
            @else
                <table class="w-full min-w-[760px] text-left">
                    <thead>
                        <tr class="border-b border-neutral-200 bg-neutral-50/70 text-[11px] font-semibold uppercase tracking-wider text-neutral-500">
                            <th scope="col" class="px-5 py-3 sm:px-6">Lokasi</th>
                            <th scope="col" class="px-5 py-3">Batch</th>
                            <th scope="col" class="px-5 py-3 text-right">Jumlah</th>
                            <th scope="col" class="px-5 py-3 text-right">Harga per Satuan</th>
                            <th scope="col" class="px-5 py-3 pr-6 text-right">Nilai</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100">
                        @foreach ($stockDistribution as $stock)
                            @php
                                $quantity = (float) $stock->quantity;
                                $unitCost = (float) $stock->batch->unit_cost;
                            @endphp
                            <tr class="transition-colors hover:bg-neutral-50/70">
                                <td class="px-5 py-3.5 text-center sm:px-6">
                                    <a href="{{ route('tambak.show', $stock->location) }}" class="font-medium text-neutral-900 hover:underline">{{ $stock->location->name }}</a>
                                    <p class="mt-0.5 font-mono text-xs text-neutral-500">{{ $stock->location->code }}</p>
                                </td>
                                <td class="px-5 py-3.5 text-center font-mono text-xs text-neutral-700">{{ $stock->batch->batch_code }}</td>
                                <td class="px-5 py-3.5 text-right font-medium tabular-nums text-neutral-900">{{ number_format($quantity, floor($quantity) === $quantity ? 0 : 3, ',', '.') }} {{ $commodity->unit }}</td>
                                <td class="px-5 py-3.5 text-right tabular-nums text-neutral-700">Rp{{ number_format($unitCost, 0, ',', '.') }}</td>
                                <td class="px-5 py-3.5 pr-6 text-right font-medium tabular-nums text-neutral-900">Rp{{ number_format($quantity * $unitCost, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </x-table-wrapper>
    </div>
</x-layouts.app>
