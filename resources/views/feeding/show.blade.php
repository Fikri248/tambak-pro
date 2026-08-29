<x-layouts.app :title="'Detail Pemberian Pakan · '.$feedingTransaction->transaction_number">
    @php
        $item = $feedingTransaction->feedItem;
        $batch = $feedingTransaction->batch;
        $quantity = (float) $feedingTransaction->feed_quantity;
        $snapshot = (float) $feedingTransaction->stock_quantity_snapshot;
        $quantityDecimals = floor($quantity) === $quantity ? 0 : 3;
        $snapshotDecimals = floor($snapshot) === $snapshot ? 0 : 3;
        $snapshotUnit = $batch?->commodity->unit
            ?? (($currentStocks->pluck('batch.commodity.unit')->filter()->unique()->count() === 1)
                ? $currentStocks->pluck('batch.commodity.unit')->filter()->first()
                : 'unit stok');
        $activityLabel = match ($item->item_type) {
            'FEED' => 'Pemberian Pakan',
            'NUTRITION' => 'Pemberian Nutrisi',
            'MEDICINE' => 'Penggunaan Obat',
            default => 'Penggunaan Kebutuhan Lain',
        };
    @endphp

    <div class="space-y-6">
        <div>
            <a href="{{ route('feeding.index') }}" class="mb-4 inline-flex items-center gap-2 text-sm text-neutral-500 hover:text-neutral-900">
                <x-icon name="arrow-left" class="size-4" />
                Pemberian Pakan
            </a>
            <div class="flex flex-wrap items-center gap-3">
                <h1 class="font-mono text-2xl font-semibold tracking-tight text-neutral-950 sm:text-[26px]">{{ $feedingTransaction->transaction_number }}</h1>
                <x-badge>Tercatat</x-badge>
            </div>
            <p class="mt-1 text-sm text-neutral-500">{{ $activityLabel }} · {{ $feedingTransaction->transaction_date->locale('id')->translatedFormat('d F Y, H:i') }}</p>
        </div>

        <x-flash-message />

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Ringkasan pemberian pakan">
            <x-kpi-card label="Jumlah Penggunaan" :value="number_format($quantity, $quantityDecimals, ',', '.')" :suffix="$item->unit" icon="feed" />
            <x-kpi-card label="Harga per Satuan" :value="'Rp'.number_format((float) $feedingTransaction->unit_cost, 0, ',', '.')" :suffix="'/ '.$item->unit" icon="coins" />
            <x-kpi-card label="Total Biaya" :value="'Rp'.number_format((float) $feedingTransaction->total_cost, 0, ',', '.')" icon="coins" />
            <x-kpi-card label="Stok Saat Pencatatan" :value="number_format($snapshot, $snapshotDecimals, ',', '.')" :suffix="$snapshotUnit" icon="waves" />
        </section>

        <div class="grid gap-6 xl:grid-cols-[minmax(0,1.25fr)_minmax(320px,0.75fr)]">
            <x-card>
                <h2 class="text-base font-semibold text-neutral-950">Informasi Transaksi</h2>
                <dl class="mt-5 grid gap-x-8 gap-y-5 sm:grid-cols-2">
                    <div><dt class="text-xs text-neutral-500">No. Transaksi</dt><dd class="mt-1 font-mono text-sm font-medium text-neutral-800">{{ $feedingTransaction->transaction_number }}</dd></div>
                    <div><dt class="text-xs text-neutral-500">Tanggal</dt><dd class="mt-1 text-sm text-neutral-800">{{ $feedingTransaction->transaction_date->locale('id')->translatedFormat('d F Y, H:i') }}</dd></div>
                    <div><dt class="text-xs text-neutral-500">Dicatat oleh</dt><dd class="mt-1 text-sm text-neutral-800">{{ $feedingTransaction->createdBy->name }}</dd></div>
                    <div><dt class="text-xs text-neutral-500">Petak</dt><dd class="mt-1 text-sm text-neutral-800"><a href="{{ route('tambak.show', $feedingTransaction->location) }}" class="font-medium hover:underline">{{ $feedingTransaction->location->name }}</a>@if ($feedingTransaction->location->parent)<span class="text-neutral-500"> · {{ $feedingTransaction->location->parent->name }}</span>@endif</dd></div>
                    <div><dt class="text-xs text-neutral-500">Batch / Cakupan</dt><dd class="mt-1 text-sm text-neutral-800">@if ($batch)<x-badge>{{ $batch->batch_code }}</x-badge>@else<span class="font-medium">Seluruh Petak</span>@endif</dd></div>
                    @if ($batch)
                        <div><dt class="text-xs text-neutral-500">Komoditas</dt><dd class="mt-1 text-sm text-neutral-800"><a href="{{ route('commodities.show', $batch->commodity) }}" class="font-medium hover:underline">{{ $batch->commodity->name }}</a></dd></div>
                    @endif
                    <div><dt class="text-xs text-neutral-500">Pakan / Nutrisi / Obat</dt><dd class="mt-1 text-sm font-medium text-neutral-800"><a href="{{ route('feed-items.show', $item) }}" class="hover:underline">{{ $item->name }}</a></dd></div>
                    <div><dt class="text-xs text-neutral-500">Jenis Kebutuhan</dt><dd class="mt-1"><x-badge>{{ $typeLabels[$item->item_type] }}</x-badge></dd></div>
                    @if ($feedingTransaction->vendor)
                        <div><dt class="text-xs text-neutral-500">Vendor</dt><dd class="mt-1 text-sm text-neutral-800"><a href="{{ route('vendors.show', $feedingTransaction->vendor) }}" class="font-medium hover:underline">{{ $feedingTransaction->vendor->name }}</a></dd></div>
                    @endif
                    <div><dt class="text-xs text-neutral-500">Stok Saat Pencatatan</dt><dd class="mt-1 text-sm font-medium tabular-nums text-neutral-800">{{ number_format($snapshot, $snapshotDecimals, ',', '.') }} {{ $snapshotUnit }}</dd></div>
                    <div><dt class="text-xs text-neutral-500">Jumlah Penggunaan</dt><dd class="mt-1 text-sm font-medium tabular-nums text-neutral-800">{{ number_format($quantity, $quantityDecimals, ',', '.') }} {{ $item->unit }}</dd></div>
                    <div><dt class="text-xs text-neutral-500">Harga per Satuan</dt><dd class="mt-1 text-sm font-medium tabular-nums text-neutral-800">Rp{{ number_format((float) $feedingTransaction->unit_cost, 0, ',', '.') }} / {{ $item->unit }}</dd></div>
                    <div><dt class="text-xs text-neutral-500">Total Biaya</dt><dd class="mt-1 text-sm font-semibold tabular-nums text-neutral-900">Rp{{ number_format((float) $feedingTransaction->total_cost, 0, ',', '.') }}</dd></div>
                    <div><dt class="text-xs text-neutral-500">Waktu Dicatat</dt><dd class="mt-1 text-sm text-neutral-800">{{ $feedingTransaction->created_at->locale('id')->translatedFormat('d F Y, H:i') }}</dd></div>
                    @if ($feedingTransaction->notes)
                        <div class="sm:col-span-2"><dt class="text-xs text-neutral-500">Catatan</dt><dd class="mt-1 whitespace-pre-line text-sm leading-6 text-neutral-800">{{ $feedingTransaction->notes }}</dd></div>
                    @endif
                </dl>
                <p class="mt-5 border-t border-neutral-200 pt-4 text-xs leading-5 text-neutral-500">Stok saat pencatatan adalah angka historis dan tidak dihitung ulang ketika stok berubah.</p>
            </x-card>

            <x-card>
                <h2 class="text-base font-semibold text-neutral-950">Kondisi Saat Ini</h2>
                <p class="mt-1 text-xs leading-5 text-neutral-500">Kondisi terkini dipisahkan dari stok historis saat pencatatan.</p>

                @if ($currentStocks->isEmpty())
                    <x-empty-state class="mt-3" title="Tidak ada stok positif" description="Stok terkait saat ini sudah tidak tersedia." icon="package" />
                @else
                    <div class="mt-5 divide-y divide-neutral-100 border-y border-neutral-200">
                        @foreach ($currentStocks as $stock)
                            @php
                                $stockQuantity = (float) $stock->quantity;
                                $stockDecimals = floor($stockQuantity) === $stockQuantity ? 0 : 3;
                                $stockUnit = $batch?->commodity->unit ?? $stock->batch->commodity->unit;
                            @endphp
                            <div class="flex items-center justify-between gap-4 py-3">
                                <div class="min-w-0">
                                    @if ($batch)
                                        <a href="{{ route('tambak.show', $stock->location) }}" class="truncate text-sm font-medium text-neutral-900 hover:underline">{{ $stock->location->name }}</a>
                                        <p class="mt-0.5 font-mono text-xs text-neutral-500">{{ $stock->location->code }}</p>
                                    @else
                                        <p class="text-sm font-medium text-neutral-900">{{ $stock->batch->batch_code }}</p>
                                        <p class="mt-0.5 text-xs text-neutral-500">{{ $stock->batch->commodity->name }}</p>
                                    @endif
                                </div>
                                <p class="shrink-0 text-sm font-semibold tabular-nums text-neutral-900">{{ number_format($stockQuantity, $stockDecimals, ',', '.') }} {{ $stockUnit }}</p>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-card>
        </div>

        <p class="text-xs leading-5 text-neutral-500">Admin dan Manager dapat mengedit atau menghapus catatan pemberian ini. Transaksi hanya mencatat penggunaan dan biaya; jumlah stok bibit tidak berubah.</p>
    </div>
</x-layouts.app>
