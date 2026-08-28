<x-layouts.app :title="'Detail Pemindahan Stok · '.$stockMovement->transaction_number">
    @php
        $commodity = $stockMovement->batch->commodity;
        $quantity = (float) $stockMovement->quantity;
        $decimals = floor($quantity) === $quantity ? 0 : 3;
        $currentTotal = $currentStocks->sum(fn ($stock) => (float) $stock->quantity);
        $currentDecimals = floor($currentTotal) === $currentTotal ? 0 : 3;
    @endphp

    <div class="space-y-6">
        <div>
            <a href="{{ route('movements.index') }}" class="mb-4 inline-flex items-center gap-2 text-sm text-neutral-500 hover:text-neutral-900">
                <x-icon name="arrow-left" class="size-4" />
                Pemindahan Stok
            </a>
            <p class="text-sm font-medium text-neutral-600">Detail Pemindahan Stok</p>
            <div class="mt-1 flex flex-wrap items-center gap-3">
                <h1 class="font-mono text-2xl font-semibold tracking-tight text-neutral-950 sm:text-[26px]">{{ $stockMovement->transaction_number }}</h1>
                <x-badge>Tercatat</x-badge>
            </div>
            <p class="mt-1 text-sm text-neutral-500">Pemindahan Stok · {{ $stockMovement->transaction_date->locale('id')->translatedFormat('d F Y, H:i') }}</p>
        </div>

        <x-flash-message />

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Ringkasan pemindahan stok">
            <x-kpi-card label="Jumlah Dipindahkan" :value="number_format($quantity, $decimals, ',', '.')" :suffix="$commodity->unit" icon="transfer" />
            <x-kpi-card label="Batch" :value="$stockMovement->batch->batch_code" icon="package" />
            <x-kpi-card label="Komoditas" :value="$commodity->name" icon="seedling" />
            <x-kpi-card label="Total Stok Kelompok" :value="number_format($currentTotal, $currentDecimals, ',', '.')" :suffix="$commodity->unit" icon="waves" />
        </section>

        <div class="grid gap-6 xl:grid-cols-[minmax(0,1.25fr)_minmax(320px,0.75fr)]">
            <x-card>
                <h2 class="text-base font-semibold text-neutral-950">Informasi Pemindahan</h2>
                <dl class="mt-5 grid gap-x-8 gap-y-5 sm:grid-cols-2">
                    <div><dt class="text-xs text-neutral-500">No. Transaksi</dt><dd class="mt-1 font-mono text-sm font-medium text-neutral-800">{{ $stockMovement->transaction_number }}</dd></div>
                    <div><dt class="text-xs text-neutral-500">Tanggal</dt><dd class="mt-1 text-sm text-neutral-800">{{ $stockMovement->transaction_date->locale('id')->translatedFormat('d F Y, H:i') }}</dd></div>
                    <div><dt class="text-xs text-neutral-500">Dicatat oleh</dt><dd class="mt-1 text-sm text-neutral-800">{{ $stockMovement->createdBy->name }}</dd></div>
                    <div><dt class="text-xs text-neutral-500">Batch</dt><dd class="mt-1"><x-badge>{{ $stockMovement->batch->batch_code }}</x-badge></dd></div>
                    <div><dt class="text-xs text-neutral-500">Komoditas</dt><dd class="mt-1 text-sm text-neutral-800"><a href="{{ route('commodities.show', $commodity) }}" class="font-medium hover:underline">{{ $commodity->name }}</a></dd></div>
                    <div><dt class="text-xs text-neutral-500">Jumlah Dipindahkan</dt><dd class="mt-1 text-sm font-medium tabular-nums text-neutral-800">{{ number_format($quantity, $decimals, ',', '.') }} {{ $commodity->unit }}</dd></div>
                    <div><dt class="text-xs text-neutral-500">Petak Asal</dt><dd class="mt-1 text-sm text-neutral-800"><a href="{{ route('tambak.show', $stockMovement->fromLocation) }}" class="font-medium hover:underline">{{ $stockMovement->fromLocation->name }}</a>@if ($stockMovement->fromLocation->parent)<span class="text-neutral-500"> · {{ $stockMovement->fromLocation->parent->name }}</span>@endif</dd></div>
                    <div><dt class="text-xs text-neutral-500">Petak Tujuan</dt><dd class="mt-1 text-sm text-neutral-800"><a href="{{ route('tambak.show', $stockMovement->toLocation) }}" class="font-medium hover:underline">{{ $stockMovement->toLocation->name }}</a>@if ($stockMovement->toLocation->parent)<span class="text-neutral-500"> · {{ $stockMovement->toLocation->parent->name }}</span>@endif</dd></div>
                    <div><dt class="text-xs text-neutral-500">Waktu Dicatat</dt><dd class="mt-1 text-sm text-neutral-800">{{ $stockMovement->created_at->locale('id')->translatedFormat('d F Y, H:i') }}</dd></div>
                    @if ($stockMovement->notes)
                        <div class="sm:col-span-2"><dt class="text-xs text-neutral-500">Catatan</dt><dd class="mt-1 whitespace-pre-line text-sm leading-6 text-neutral-800">{{ $stockMovement->notes }}</dd></div>
                    @endif
                </dl>
            </x-card>

            <x-card>
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-base font-semibold text-neutral-950">Sebaran Batch Saat Ini</h2>
                        <p class="mt-1 text-xs leading-5 text-neutral-500">Posisi terkini dapat berubah setelah pemindahan berikutnya.</p>
                    </div>
                    <x-badge>{{ $stockMovement->batch->status === 'ACTIVE' ? 'Batch Aktif' : 'Tidak Aktif' }}</x-badge>
                </div>

                @if ($currentStocks->isEmpty())
                    <x-empty-state class="mt-3" title="Tidak ada stok tersisa" description="Batch ini tidak memiliki stok positif pada lokasi mana pun." icon="package" />
                @else
                    <div class="mt-5 divide-y divide-neutral-100 border-y border-neutral-200">
                        @foreach ($currentStocks as $stock)
                            @php
                                $stockQuantity = (float) $stock->quantity;
                                $stockDecimals = floor($stockQuantity) === $stockQuantity ? 0 : 3;
                            @endphp
                            <div class="flex items-center justify-between gap-4 py-3">
                                <div class="min-w-0">
                                    <a href="{{ route('tambak.show', $stock->location) }}" class="truncate text-sm font-medium text-neutral-900 hover:underline">{{ $stock->location->name }}</a>
                                    <p class="mt-0.5 font-mono text-xs text-neutral-500">{{ $stock->location->code }}</p>
                                </div>
                                <p class="shrink-0 text-sm font-semibold tabular-nums text-neutral-900">{{ number_format($stockQuantity, $stockDecimals, ',', '.') }} {{ $commodity->unit }}</p>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-4 flex items-center justify-between gap-4 text-sm">
                        <span class="text-neutral-500">Total saat ini</span>
                        <span class="font-semibold tabular-nums text-neutral-950">{{ number_format($currentTotal, $currentDecimals, ',', '.') }} {{ $commodity->unit }}</span>
                    </div>
                @endif
            </x-card>
        </div>

        <p class="text-xs leading-5 text-neutral-500">Admin dapat mengedit atau menghapus pemindahan hanya ketika dampak stok dapat dibatalkan dengan aman dan belum digunakan oleh aktivitas lanjutan.</p>
    </div>
</x-layouts.app>
