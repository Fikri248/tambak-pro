<x-layouts.app title="Pembelian Barang/Item">
    @php
        $itemOptions = $feedItems->mapWithKeys(fn ($item) => [$item->id => $item->name])->all();
        $vendorOptions = $vendors->mapWithKeys(fn ($vendor) => [$vendor->id => $vendor->name])->all();
        $typeOptions = $itemTypes->mapWithKeys(fn ($type) => [$type->id => $type->name])->all();
        $filterCount = collect($filters)->except('search')->filter()->count();
    @endphp
    <div class="space-y-6">
        <x-page-header title="Pembelian Barang/Item" description="Kelola catatan kuantitas dan biaya pengadaan Barang/Item.">
            <x-slot:actions><x-button :href="route('item-purchases.create')"><x-icon name="plus" class="size-4" />Tambah Pembelian</x-button></x-slot:actions>
        </x-page-header>
        <x-flash-message />
        <section class="grid gap-4 sm:grid-cols-2"><x-kpi-card label="Jumlah Transaksi" :value="number_format($summary['total'], 0, ',', '.')" icon="history" /><x-kpi-card label="Total Biaya Pembelian" :value="'Rp'.number_format($summary['cost'], 0, ',', '.')" icon="coins" /></section>
        <x-card>
            <form method="GET" action="{{ route('item-purchases.index') }}" class="flex flex-col gap-3 lg:flex-row lg:items-start">
                <div class="min-w-0 flex-1"><label for="purchase-search" class="sr-only">Cari Pembelian</label><div class="relative"><x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-neutral-400" /><input id="purchase-search" name="search" type="search" value="{{ $filters['search'] }}" placeholder="Cari nomor, Barang/Item, Vendor, atau pencatat..." class="h-10 w-full rounded-lg border border-neutral-200 pl-9 pr-3 text-sm"></div></div>
                <x-filters.panel id="purchase-filters" :active-count="$filterCount" class="w-full lg:w-auto">
                    <div class="grid gap-4 sm:grid-cols-2"><x-filters.select name="feed_item_id" label="Barang/Item" :options="$itemOptions" :value="$filters['itemId']" placeholder="Semua Barang/Item" /><x-filters.select name="vendor_id" label="Vendor" :options="$vendorOptions" :value="$filters['vendorId']" placeholder="Semua Vendor" /><x-filters.select name="item_type_id" label="Jenis Barang/Item" :options="$typeOptions" :value="$filters['typeId']" placeholder="Semua Jenis" /><x-filters.date-range :from="$filters['dateFrom']" :to="$filters['dateTo']" /></div>
                    <div class="mt-5 flex justify-end gap-2">@if ($filterCount || $filters['search'] !== '')<x-button variant="secondary" :href="route('item-purchases.index')">Reset</x-button>@endif<x-button type="submit">Terapkan Filter</x-button></div>
                </x-filters.panel>
                <x-page-size id="item-purchases-per-page" :value="$transactions->perPage()" />
            </form>
        </x-card>
        <div>
            <x-table-wrapper title="Daftar Pembelian Barang/Item" description="Pembelian mencatat biaya pengadaan dan tidak menambah saldo stok Barang/Item.">
                @if ($transactions->isEmpty())
                    <x-empty-state title="Belum ada Pembelian Barang/Item" description="Tambahkan transaksi pembelian pertama." icon="coins"><x-button :href="route('item-purchases.create')">Tambah Pembelian</x-button></x-empty-state>
                @else
                    <table data-responsive-table="item-purchases" class="w-full min-w-[1280px] text-left">
                        <thead><tr class="border-b border-neutral-200 bg-neutral-50/70 text-[11px] font-semibold uppercase tracking-wider text-neutral-500"><th class="px-5 py-3">No. Transaksi</th><th class="px-5 py-3">Tanggal</th><th class="px-5 py-3">Barang/Item</th><th class="px-5 py-3">Jenis Barang/Item</th><th class="px-5 py-3">Vendor</th><th class="px-5 py-3">Jumlah</th><th class="px-5 py-3">Harga Satuan</th><th class="px-5 py-3">Total Biaya</th><th class="px-5 py-3">Dicatat Oleh</th><th class="px-5 py-3">Aksi</th></tr></thead>
                        <tbody class="divide-y divide-neutral-100">@foreach ($transactions as $transaction)<tr class="hover:bg-neutral-50/70"><td class="px-5 py-3.5"><a href="{{ route('item-purchases.show', $transaction) }}" class="font-mono text-xs font-medium hover:underline">{{ $transaction->transaction_number }}</a></td><td class="whitespace-nowrap px-5 py-3.5 text-center">{{ $transaction->transaction_date->locale('id')->translatedFormat('d M Y, H:i') }}</td><td class="px-5 py-3.5 font-medium">{{ $transaction->feedItem->name }}</td><td class="px-5 py-3.5 text-center"><x-badge>{{ $transaction->feedItem->itemType->name }}</x-badge></td><td class="px-5 py-3.5">{{ $transaction->vendor->name }}</td><td class="px-5 py-3.5 text-center tabular-nums">{{ number_format((float) $transaction->quantity, 3, ',', '.') }} {{ $transaction->feedItem->unit }}</td><td class="px-5 py-3.5 text-center tabular-nums">Rp{{ number_format((float) $transaction->unit_cost, 0, ',', '.') }}</td><td class="px-5 py-3.5 text-center font-medium tabular-nums">Rp{{ number_format((float) $transaction->total_cost, 0, ',', '.') }}</td><td class="px-5 py-3.5">{{ $transaction->createdBy->name }}</td><td class="px-5 py-3.5"><div class="flex gap-1"><a href="{{ route('item-purchases.show', $transaction) }}" class="flex size-9 items-center justify-center rounded-lg hover:bg-neutral-100"><x-icon name="eye" class="size-4" /></a><a href="{{ route('item-purchases.edit', $transaction) }}" class="flex size-9 items-center justify-center rounded-lg hover:bg-neutral-100"><x-icon name="edit" class="size-4" /></a><form method="POST" action="{{ route('item-purchases.destroy', $transaction) }}" data-confirm="Hapus pembelian {{ $transaction->transaction_number }}?" data-confirm-title="Hapus Pembelian Barang/Item" data-confirm-action="Hapus Pembelian" data-confirm-tone="danger">@csrf @method('DELETE')<button class="flex size-9 items-center justify-center rounded-lg hover:bg-neutral-100" type="submit"><x-icon name="trash" class="size-4" /></button></form></div></td></tr>@endforeach</tbody>
                    </table>
                @endif
            </x-table-wrapper>
            @if ($transactions->hasPages())<div class="mt-4">{{ $transactions->links() }}</div>@endif
        </div>
    </div>
</x-layouts.app>
