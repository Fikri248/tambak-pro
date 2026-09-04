<x-layouts.app :title="'Detail Vendor · '.$vendor->name">
    <div class="space-y-6">
        <div>
            <a href="{{ route('vendors.index') }}" class="mb-4 inline-flex items-center gap-2 text-sm text-neutral-500 hover:text-neutral-900">
                <x-icon name="arrow-left" class="size-4" />
                Master Vendor
            </a>

            <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-3">
                        <h1 class="text-2xl font-semibold tracking-tight text-neutral-950 sm:text-[26px]">{{ $vendor->name }}</h1>
                        <x-badge>{{ $vendor->status === 'ACTIVE' ? 'Aktif' : 'Tidak Aktif' }}</x-badge>
                    </div>
                    <p class="mt-1 text-sm text-neutral-500">{{ $vendor->code }} · {{ $vendor->vendorType->name }}</p>
                </div>

                @if (auth()->user()->canAccess('vendors.manage'))
                    <div class="flex flex-wrap gap-2">
                        <x-button variant="secondary" :href="route('vendors.edit', $vendor)" data-crud-modal data-crud-modal-size="xl">
                            <x-icon name="edit" class="size-4" />
                            Edit Vendor
                        </x-button>
                        <form method="POST" action="{{ route('vendors.status', $vendor) }}" data-confirm="{{ $vendor->status === 'ACTIVE' ? 'Nonaktifkan Vendor ini?' : 'Aktifkan Vendor ini?' }}" data-confirm-title="{{ $vendor->status === 'ACTIVE' ? 'Nonaktifkan Vendor' : 'Aktifkan Vendor' }}" data-confirm-action="{{ $vendor->status === 'ACTIVE' ? 'Nonaktifkan' : 'Aktifkan' }}">
                            @csrf
                            @method('PATCH')
                            <x-button type="submit">
                                <x-icon name="power" class="size-4" />
                                {{ $vendor->status === 'ACTIVE' ? 'Nonaktifkan' : 'Aktifkan' }}
                            </x-button>
                        </form>
                    </div>
                @endif
            </div>
        </div>

        <x-flash-message />

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Ringkasan {{ $vendor->name }}">
            <x-kpi-card label="Batch" :value="number_format($vendor->commodity_batches_count, 0, ',', '.')" icon="seedling" />
            <x-kpi-card label="Barang/Item" :value="number_format($vendor->default_feed_items_count, 0, ',', '.')" icon="feed" />
            <x-kpi-card label="Transaksi Terkait" :value="number_format($vendor->feeding_transactions_count, 0, ',', '.')" icon="history" />
            <x-kpi-card label="Status" :value="$vendor->status === 'ACTIVE' ? 'Aktif' : 'Tidak Aktif'" icon="check" />
        </section>

        <x-card>
            <h2 class="text-base font-semibold text-neutral-950">Informasi Vendor</h2>
            <dl class="mt-5 grid gap-x-8 gap-y-5 sm:grid-cols-2 xl:grid-cols-4">
                <div><dt class="text-xs text-neutral-500">Kode</dt><dd class="mt-1 font-mono text-sm font-medium text-neutral-900">{{ $vendor->code }}</dd></div>
                <div><dt class="text-xs text-neutral-500">Nama</dt><dd class="mt-1 text-sm font-medium text-neutral-900">{{ $vendor->name }}</dd></div>
                <div><dt class="text-xs text-neutral-500">Jenis</dt><dd class="mt-1 text-sm font-medium text-neutral-900">{{ $vendor->vendorType->name }}</dd></div>
                <div><dt class="text-xs text-neutral-500">Status</dt><dd class="mt-1 text-sm font-medium text-neutral-900">{{ $vendor->status === 'ACTIVE' ? 'Aktif' : 'Tidak Aktif' }}</dd></div>
                @if ($vendor->phone)
                    @php
                        $whatsAppUrl = \App\Support\WhatsApp::url($vendor->phone);
                    @endphp
                    <div>
                        <dt class="text-xs text-neutral-500">Telepon</dt>
                        <dd class="mt-1 flex flex-wrap items-center gap-2 text-sm text-neutral-800" data-vendor-phone>
                            @if ($whatsAppUrl)
                                <a href="{{ $whatsAppUrl }}" target="_blank" rel="noopener noreferrer" class="inline-flex size-8 shrink-0 items-center justify-center rounded-md text-emerald-700 transition-colors hover:bg-emerald-50 hover:text-emerald-800" aria-label="Chat WhatsApp dengan {{ $vendor->name }}" title="Chat WhatsApp">
                                    <x-icon name="whatsapp" class="size-4.5" />
                                </a>
                            @endif
                            <a href="tel:{{ $vendor->phone }}" class="break-all hover:underline">{{ $vendor->phone }}</a>
                        </dd>
                    </div>
                @endif
                @if ($vendor->email)
                    <div class="sm:col-span-2"><dt class="text-xs text-neutral-500">Email</dt><dd class="mt-1 text-sm text-neutral-800"><a href="mailto:{{ $vendor->email }}" class="break-all hover:underline">{{ $vendor->email }}</a></dd></div>
                @endif
                @if ($vendor->address)
                    <div class="sm:col-span-2 xl:col-span-4"><dt class="text-xs text-neutral-500">Alamat</dt><dd class="mt-1 text-sm leading-6 text-neutral-800">{{ $vendor->address }}</dd></div>
                @endif
                @if ($vendor->description)
                    <div class="sm:col-span-2 xl:col-span-4"><dt class="text-xs text-neutral-500">Deskripsi</dt><dd class="mt-1 text-sm leading-6 text-neutral-800">{{ $vendor->description }}</dd></div>
                @endif
                <div><dt class="text-xs text-neutral-500">Dibuat</dt><dd class="mt-1 text-sm text-neutral-800">{{ $vendor->created_at->locale('id')->translatedFormat('d F Y, H:i') }}</dd></div>
                <div><dt class="text-xs text-neutral-500">Terakhir diperbarui</dt><dd class="mt-1 text-sm text-neutral-800">{{ $vendor->updated_at->locale('id')->translatedFormat('d F Y, H:i') }}</dd></div>
            </dl>
        </x-card>

        <x-table-wrapper title="Batch Terkait" description="Maksimal 12 Batch terbaru yang menggunakan Vendor ini.">
            @if ($relatedBatches->isEmpty())
                <x-empty-state title="Belum ada Batch terkait" description="Vendor ini belum tercatat untuk Batch komoditas." icon="seedling" />
            @else
                <table class="w-full min-w-[860px] text-left">
                    <thead>
                        <tr class="border-b border-neutral-200 bg-neutral-50/70 text-[11px] font-semibold uppercase tracking-wider text-neutral-500">
                            <th scope="col" class="px-5 py-3 sm:px-6">Batch</th>
                            <th scope="col" class="px-5 py-3">Komoditas</th>
                            <th scope="col" class="px-5 py-3">Tanggal Pembelian</th>
                            <th scope="col" class="px-5 py-3 text-right">Jumlah Awal</th>
                            <th scope="col" class="px-5 py-3 text-right">Harga per Satuan</th>
                            <th scope="col" class="px-5 py-3 pr-6">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100">
                        @foreach ($relatedBatches as $batch)
                            @php
                                $initial = (float) $batch->initial_quantity;
                                $batchStatus = ['ACTIVE' => 'Aktif', 'CLOSED' => 'Ditutup', 'CANCELLED' => 'Dibatalkan'][$batch->status] ?? 'Tidak Dikenal';
                            @endphp
                            <tr class="transition-colors hover:bg-neutral-50/70">
                                <td class="px-5 py-3.5 text-center font-mono text-xs font-medium text-neutral-700 sm:px-6">{{ $batch->batch_code }}</td>
                                <td class="px-5 py-3.5"><a href="{{ route('commodities.show', $batch->commodity) }}" class="font-medium text-neutral-900 hover:underline">{{ $batch->commodity->name }}</a></td>
                                <td class="px-5 py-3.5 text-center text-neutral-600">{{ $batch->purchase_date->locale('id')->translatedFormat('d M Y') }}</td>
                                <td class="px-5 py-3.5 text-right tabular-nums text-neutral-700">{{ number_format($initial, floor($initial) === $initial ? 0 : 3, ',', '.') }} {{ $batch->commodity->unit }}</td>
                                <td class="px-5 py-3.5 text-right tabular-nums text-neutral-700">Rp{{ number_format((float) $batch->unit_cost, 0, ',', '.') }}</td>
                                <td class="px-5 py-3.5 pr-6 text-center"><x-badge>{{ $batchStatus }}</x-badge></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </x-table-wrapper>

        <x-table-wrapper title="Barang/Item Terkait" description="Maksimal 12 Barang/Item yang menggunakan Vendor ini sebagai Vendor utama.">
            @if ($relatedFeedItems->isEmpty())
                <x-empty-state title="Belum ada Barang/Item terkait" description="Vendor ini belum digunakan sebagai Vendor utama Barang/Item." icon="feed" />
            @else
                <table class="w-full min-w-[700px] text-left">
                    <thead>
                        <tr class="border-b border-neutral-200 bg-neutral-50/70 text-[11px] font-semibold uppercase tracking-wider text-neutral-500">
                            <th scope="col" class="px-5 py-3 sm:px-6">Kode</th>
                            <th scope="col" class="px-5 py-3">Nama</th>
                            <th scope="col" class="px-5 py-3">Jenis</th>
                            <th scope="col" class="px-5 py-3">Satuan</th>
                            <th scope="col" class="px-5 py-3 text-right">Harga Acuan per Satuan</th>
                            <th scope="col" class="px-5 py-3 pr-6">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100">
                        @foreach ($relatedFeedItems as $item)
                            <tr class="transition-colors hover:bg-neutral-50/70">
                                <td class="px-5 py-3.5 font-mono text-xs text-neutral-600 sm:px-6">{{ $item->code }}</td>
                                <td class="px-5 py-3.5 font-medium text-neutral-900">{{ $item->name }}</td>
                                <td class="px-5 py-3.5 text-center"><x-badge>{{ $item->itemType->name }}</x-badge></td>
                                <td class="px-5 py-3.5 text-center text-neutral-600">{{ $item->unit }}</td>
                                <td class="px-5 py-3.5 text-right tabular-nums text-neutral-700">Rp{{ number_format((float) $item->default_price, 0, ',', '.') }}</td>
                                <td class="px-5 py-3.5 pr-6 text-center"><x-badge>{{ $item->status === 'ACTIVE' ? 'Aktif' : 'Tidak Aktif' }}</x-badge></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </x-table-wrapper>

        <x-card :padding="false">
            <div class="border-b border-neutral-200 px-5 py-4 sm:px-6">
                <h2 class="text-base font-semibold text-neutral-950">Penggunaan Operasional Terbaru</h2>
                <p class="mt-1 text-xs text-neutral-500">Maksimal enam transaksi penggunaan Barang/Item terbaru yang menggunakan Vendor ini.</p>
            </div>
            @if ($recentTransactions->isEmpty())
                <x-empty-state title="Belum ada transaksi terkait" description="Transaksi penggunaan Barang/Item yang terkait akan tampil di bagian ini." icon="history" />
            @else
                <ol class="divide-y divide-neutral-100">
                    @foreach ($recentTransactions as $transaction)
                        @php
                            $feedQuantity = (float) $transaction->feed_quantity;
                            $feedQuantityDecimals = floor($feedQuantity) === $feedQuantity ? 0 : 3;
                        @endphp
                        <li class="flex gap-3 px-5 py-4 sm:px-6">
                            <span class="mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-lg border border-neutral-200 bg-neutral-50 text-neutral-500"><x-icon name="feed" class="size-4" /></span>
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <x-badge>{{ $transaction->transaction_number }}</x-badge>
                                    <span class="text-xs text-neutral-500">{{ $transaction->transaction_date->locale('id')->translatedFormat('d M Y, H:i') }}</span>
                                </div>
                                <p class="mt-1.5 text-sm leading-5 text-neutral-800">{{ number_format($feedQuantity, $feedQuantityDecimals, ',', '.') }} {{ $transaction->feedItem->unit }} {{ $transaction->feedItem->name }} untuk {{ $transaction->location->name }}.</p>
                                <p class="mt-1 text-xs text-neutral-500">{{ $transaction->createdBy?->name ?? 'Sistem' }}</p>
                            </div>
                        </li>
                    @endforeach
                </ol>
            @endif
        </x-card>
    </div>
</x-layouts.app>
