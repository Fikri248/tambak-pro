<x-layouts.app :title="'Detail Barang/Item · '.$feedItem->name">
    @php
        $formatQuantity = fn (float $value): string => number_format($value, floor($value) === $value ? 0 : 3, ',', '.');
    @endphp

    <div class="space-y-6">
        <div>
            <a href="{{ route('feed-items.index') }}" class="mb-4 inline-flex items-center gap-2 text-sm text-neutral-500 hover:text-neutral-900">
                <x-icon name="arrow-left" class="size-4" />
                Barang/Item
            </a>

            <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-3">
                        <h1 class="text-2xl font-semibold tracking-tight text-neutral-950 sm:text-[26px]">{{ $feedItem->name }}</h1>
                        <x-badge>{{ $feedItem->status === 'ACTIVE' ? 'Aktif' : 'Tidak Aktif' }}</x-badge>
                    </div>
                    <p class="mt-1 text-sm text-neutral-500">{{ $feedItem->code }} · {{ $feedItem->itemType->name }}</p>
                </div>

                @if (auth()->user()->canAccess('feed-items.manage'))
                    <div class="flex flex-wrap gap-2">
                        <x-button variant="secondary" :href="route('feed-items.edit', $feedItem)" data-crud-modal data-crud-modal-size="xl">
                            <x-icon name="edit" class="size-4" />
                            Edit Barang/Item
                        </x-button>
                        <form method="POST" action="{{ route('feed-items.status', $feedItem) }}" data-confirm="{{ $feedItem->status === 'ACTIVE' ? 'Nonaktifkan kebutuhan ini?' : 'Aktifkan kebutuhan ini?' }}" data-confirm-title="{{ $feedItem->status === 'ACTIVE' ? 'Nonaktifkan Kebutuhan' : 'Aktifkan Kebutuhan' }}" data-confirm-action="{{ $feedItem->status === 'ACTIVE' ? 'Nonaktifkan' : 'Aktifkan' }}">
                            @csrf
                            @method('PATCH')
                            <x-button type="submit">
                                <x-icon name="power" class="size-4" />
                                {{ $feedItem->status === 'ACTIVE' ? 'Nonaktifkan' : 'Aktifkan' }}
                            </x-button>
                        </form>
                    </div>
                @endif
            </div>
        </div>

        <x-flash-message />

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Ringkasan {{ $feedItem->name }}">
            <x-kpi-card label="Harga Acuan per Satuan" :value="'Rp'.\App\Support\DecimalDisplay::localized($feedItem->default_price)" icon="coins" />
            <x-kpi-card label="Vendor Utama" :value="$feedItem->defaultVendor?->name ?? 'Tanpa Vendor'" icon="truck" />
            <x-kpi-card label="Jumlah Penggunaan" :value="number_format($usageCount, 0, ',', '.')" suffix="transaksi" icon="history" />
            <x-kpi-card label="Total Dipakai" :value="$formatQuantity($totalUsage)" :suffix="$feedItem->unit" icon="feed" />
        </section>

        <x-card>
            <h2 class="text-base font-semibold text-neutral-950">Informasi Barang/Item</h2>
            <dl class="mt-5 grid gap-x-8 gap-y-5 sm:grid-cols-2 xl:grid-cols-4">
                <div><dt class="text-xs text-neutral-500">Kode</dt><dd class="mt-1 font-mono text-sm font-medium text-neutral-900">{{ $feedItem->code }}</dd></div>
                <div><dt class="text-xs text-neutral-500">Nama</dt><dd class="mt-1 text-sm font-medium text-neutral-900">{{ $feedItem->name }}</dd></div>
                <div><dt class="text-xs text-neutral-500">Jenis Barang/Item</dt><dd class="mt-1 text-sm font-medium text-neutral-900">{{ $feedItem->itemType->name }}</dd></div>
                <div><dt class="text-xs text-neutral-500">Satuan</dt><dd class="mt-1 text-sm font-medium text-neutral-900">{{ $feedItem->unit }}</dd></div>
                <div class="sm:col-span-2"><dt class="text-xs text-neutral-500">Vendor Utama</dt><dd class="mt-1 text-sm text-neutral-800">{{ $feedItem->defaultVendor?->name ?? 'Tanpa Vendor utama' }}@if ($feedItem->defaultVendor?->status === 'INACTIVE') <span class="text-neutral-500">(Tidak aktif)</span>@endif</dd></div>
                <div><dt class="text-xs text-neutral-500">Harga Acuan per Satuan</dt><dd class="mt-1 text-sm font-medium tabular-nums text-neutral-900">Rp{{ \App\Support\DecimalDisplay::localized($feedItem->default_price) }}</dd></div>
                <div><dt class="text-xs text-neutral-500">Status</dt><dd class="mt-1 text-sm font-medium text-neutral-900">{{ $feedItem->status === 'ACTIVE' ? 'Aktif' : 'Tidak Aktif' }}</dd></div>
                <div><dt class="text-xs text-neutral-500">Dibuat</dt><dd class="mt-1 text-sm text-neutral-800">{{ $feedItem->created_at->locale('id')->translatedFormat('d M Y, H:i') }}</dd></div>
                <div><dt class="text-xs text-neutral-500">Terakhir diperbarui</dt><dd class="mt-1 text-sm text-neutral-800">{{ $feedItem->updated_at->locale('id')->translatedFormat('d M Y, H:i') }}</dd></div>
                @if ($feedItem->description)
                    <div class="sm:col-span-2 xl:col-span-4"><dt class="text-xs text-neutral-500">Deskripsi</dt><dd class="mt-1 whitespace-pre-line text-sm leading-6 text-neutral-800">{{ $feedItem->description }}</dd></div>
                @endif
            </dl>
        </x-card>

        <x-table-wrapper title="Penggunaan Terbaru" description="Riwayat pemakaian Barang/Item dari transaksi penggunaan yang sudah tercatat.">
            @if ($recentTransactions->isEmpty())
                <x-empty-state title="Belum ada riwayat penggunaan" description="Barang/Item ini belum pernah digunakan." icon="history" />
            @else
                <table class="w-full min-w-[920px] text-left">
                    <thead>
                        <tr class="border-b border-neutral-200 bg-neutral-50/70 text-[11px] font-semibold uppercase tracking-wider text-neutral-500">
                            <th scope="col" class="px-5 py-3 sm:px-6">Tanggal</th>
                            <th scope="col" class="px-5 py-3">Lokasi</th>
                            <th scope="col" class="px-5 py-3">Batch</th>
                            <th scope="col" class="px-5 py-3 text-right">Jumlah</th>
                            <th scope="col" class="px-5 py-3 text-right">Harga per Satuan</th>
                            <th scope="col" class="px-5 py-3 text-right">Total Biaya</th>
                            <th scope="col" class="px-5 py-3 pr-6">Dicatat Oleh</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100">
                        @foreach ($recentTransactions as $transaction)
                            <tr class="hover:bg-neutral-50/70">
                                <td class="whitespace-nowrap px-5 py-3.5 text-center text-neutral-600 sm:px-6">{{ $transaction->transaction_date->locale('id')->translatedFormat('d M Y, H:i') }}</td>
                                <td class="px-5 py-3.5 text-center font-medium text-neutral-900">{{ $transaction->location->name }}</td>
                                <td class="px-5 py-3.5 text-center">
                                    @if ($transaction->batch)
                                        <x-badge>{{ $transaction->batch->batch_code }}</x-badge>
                                        <p class="mt-1 text-xs text-neutral-500">{{ $transaction->batch->commodity->name }}</p>
                                    @else
                                        <span class="text-sm text-neutral-500">Seluruh Petak</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5 text-right font-medium tabular-nums text-neutral-900">{{ $formatQuantity((float) $transaction->feed_quantity) }} {{ $feedItem->unit }}</td>
                                <td class="px-5 py-3.5 text-right tabular-nums text-neutral-600">Rp{{ number_format((float) $transaction->unit_cost, 0, ',', '.') }}</td>
                                <td class="px-5 py-3.5 text-right font-medium tabular-nums text-neutral-900">Rp{{ number_format((float) $transaction->total_cost, 0, ',', '.') }}</td>
                                <td class="px-5 py-3.5 pr-6 text-neutral-600">{{ $transaction->createdBy->name }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </x-table-wrapper>

        @if ($totalCost > 0)
            <p class="text-right text-xs text-neutral-500">Total biaya historis tercatat: <span class="font-medium text-neutral-800">Rp{{ number_format($totalCost, 0, ',', '.') }}</span></p>
        @endif
    </div>
</x-layouts.app>
