<x-layouts.app :title="'Detail Perubahan Jumlah · '.$stockAdjustment->transaction_number">
    @php
        $commodity = $stockAdjustment->batch->commodity;
        $before = (float) $stockAdjustment->quantity_before;
        $change = (float) $stockAdjustment->quantity_change;
        $after = (float) $stockAdjustment->quantity_after;
        $formatQuantity = fn (float $value): string => number_format($value, floor($value) === $value ? 0 : 3, ',', '.');
        $currentTotal = $currentStocks->sum(fn ($stock) => (float) $stock->quantity);
    @endphp

    <div class="space-y-6">
        <div>
            <a href="{{ route('adjustments.index') }}" class="mb-4 inline-flex items-center gap-2 text-sm text-neutral-500 hover:text-neutral-900">
                <x-icon name="arrow-left" class="size-4" />
                Perubahan Jumlah
            </a>
            <div class="flex flex-wrap items-center gap-3">
                <h1 class="font-mono text-2xl font-semibold tracking-tight text-neutral-950 sm:text-[26px]">{{ $stockAdjustment->transaction_number }}</h1>
                <x-badge>Tercatat</x-badge>
            </div>
            <p class="mt-1 text-sm text-neutral-500">{{ $typeLabels[$stockAdjustment->adjustment_type] }} · {{ $stockAdjustment->transaction_date->locale('id')->translatedFormat('d F Y, H:i') }}</p>
        </div>

        <x-flash-message />

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Ringkasan perubahan stok">
            <x-kpi-card label="Jumlah Sebelum" :value="$formatQuantity($before)" :suffix="$commodity->unit" icon="history" />
            <x-kpi-card label="Perubahan" :value="($change > 0 ? '+' : '').$formatQuantity($change)" :suffix="$commodity->unit" icon="adjustment" />
            <x-kpi-card label="Jumlah Sesudah" :value="$formatQuantity($after)" :suffix="$commodity->unit" icon="check" />
            <x-kpi-card label="Total Stok Kelompok" :value="$formatQuantity($currentTotal)" :suffix="$commodity->unit" icon="package" />
        </section>

        <div class="grid gap-6 xl:grid-cols-[minmax(0,1.25fr)_minmax(320px,0.75fr)]">
            <x-card>
                <h2 class="text-base font-semibold text-neutral-950">Informasi Perubahan</h2>
                <dl class="mt-5 grid gap-x-8 gap-y-5 sm:grid-cols-2">
                    <div><dt class="text-xs text-neutral-500">No. Transaksi</dt><dd class="mt-1 font-mono text-sm font-medium text-neutral-800">{{ $stockAdjustment->transaction_number }}</dd></div>
                    <div><dt class="text-xs text-neutral-500">Tanggal</dt><dd class="mt-1 text-sm text-neutral-800">{{ $stockAdjustment->transaction_date->locale('id')->translatedFormat('d F Y, H:i') }}</dd></div>
                    <div><dt class="text-xs text-neutral-500">Jenis</dt><dd class="mt-1"><x-badge>{{ $typeLabels[$stockAdjustment->adjustment_type] }}</x-badge></dd></div>
                    <div><dt class="text-xs text-neutral-500">Dicatat oleh</dt><dd class="mt-1 text-sm text-neutral-800">{{ $stockAdjustment->createdBy->name }}</dd></div>
                    <div><dt class="text-xs text-neutral-500">Petak</dt><dd class="mt-1 text-sm text-neutral-800"><a href="{{ route('tambak.show', $stockAdjustment->location) }}" class="font-medium hover:underline">{{ $stockAdjustment->location->name }}</a>@if ($stockAdjustment->location->parent)<span class="text-neutral-500"> · {{ $stockAdjustment->location->parent->name }}</span>@endif</dd></div>
                    <div><dt class="text-xs text-neutral-500">Batch</dt><dd class="mt-1"><x-badge>{{ $stockAdjustment->batch->batch_code }}</x-badge></dd></div>
                    <div><dt class="text-xs text-neutral-500">Komoditas</dt><dd class="mt-1 text-sm text-neutral-800"><a href="{{ route('commodities.show', $commodity) }}" class="font-medium hover:underline">{{ $commodity->name }}</a></dd></div>
                    <div><dt class="text-xs text-neutral-500">Jumlah Sebelum</dt><dd class="mt-1 text-sm tabular-nums text-neutral-800">{{ $formatQuantity($before) }} {{ $commodity->unit }}</dd></div>
                    <div><dt class="text-xs text-neutral-500">Perubahan</dt><dd class="mt-1 text-sm font-medium tabular-nums text-neutral-800">{{ $change > 0 ? '+' : '' }}{{ $formatQuantity($change) }} {{ $commodity->unit }}</dd></div>
                    <div><dt class="text-xs text-neutral-500">Jumlah Sesudah</dt><dd class="mt-1 text-sm tabular-nums text-neutral-800">{{ $formatQuantity($after) }} {{ $commodity->unit }}</dd></div>
                    <div><dt class="text-xs text-neutral-500">Waktu Dicatat</dt><dd class="mt-1 text-sm text-neutral-800">{{ $stockAdjustment->created_at->locale('id')->translatedFormat('d F Y, H:i') }}</dd></div>
                    <div class="sm:col-span-2"><dt class="text-xs text-neutral-500">Alasan</dt><dd class="mt-1 whitespace-pre-line text-sm leading-6 text-neutral-800">{{ $stockAdjustment->reason }}</dd></div>
                </dl>
            </x-card>

            <x-card>
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-base font-semibold text-neutral-950">Sebaran Batch Saat Ini</h2>
                        <p class="mt-1 text-xs leading-5 text-neutral-500">Posisi terkini dapat berubah setelah transaksi berikutnya.</p>
                    </div>
                    <x-badge>{{ $stockAdjustment->batch->status === 'ACTIVE' ? 'Batch Aktif' : 'Tidak Aktif' }}</x-badge>
                </div>

                @if ($currentStocks->isEmpty())
                    <x-empty-state class="mt-3" title="Tidak ada stok tersisa" description="Batch ini tidak memiliki stok positif pada lokasi mana pun." icon="package" />
                @else
                    <div class="mt-5 divide-y divide-neutral-100 border-y border-neutral-200">
                        @foreach ($currentStocks as $stock)
                            @php($stockQuantity = (float) $stock->quantity)
                            <div class="flex items-center justify-between gap-4 py-3">
                                <div class="min-w-0">
                                    <a href="{{ route('tambak.show', $stock->location) }}" class="truncate text-sm font-medium text-neutral-900 hover:underline">{{ $stock->location->name }}</a>
                                    <p class="mt-0.5 font-mono text-xs text-neutral-500">{{ $stock->location->code }}</p>
                                </div>
                                <p class="shrink-0 text-sm font-semibold tabular-nums text-neutral-900">{{ $formatQuantity($stockQuantity) }} {{ $commodity->unit }}</p>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-4 flex items-center justify-between gap-4 text-sm">
                        <span class="text-neutral-500">Total saat ini</span>
                        <span class="font-semibold tabular-nums text-neutral-950">{{ $formatQuantity($currentTotal) }} {{ $commodity->unit }}</span>
                    </div>
                @endif
            </x-card>
        </div>

        <p class="text-xs leading-5 text-neutral-500">Admin dan Manager dapat mengedit atau menghapus perubahan ini selama posisi stok masih sesuai dan belum memiliki aktivitas lanjutan.</p>
    </div>
</x-layouts.app>
