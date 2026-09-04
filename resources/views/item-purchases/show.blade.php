<x-layouts.app :title="'Detail Pembelian · '.$itemPurchase->transaction_number">
    <div class="space-y-6">
        <a href="{{ route('item-purchases.index') }}" class="inline-flex items-center gap-2 text-sm text-neutral-500 hover:text-neutral-900"><x-icon name="arrow-left" class="size-4" />Pembelian Barang/Item</a>
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <x-page-header :title="$itemPurchase->transaction_number" description="Detail Pembelian Barang/Item" />
            <div class="flex gap-2">
                <x-button variant="secondary" :href="route('item-purchases.edit', $itemPurchase)"><x-icon name="edit" class="size-4" />Edit</x-button>
                <form method="POST" action="{{ route('item-purchases.destroy', $itemPurchase) }}" data-confirm="Hapus pembelian {{ $itemPurchase->transaction_number }}?" data-confirm-title="Hapus Pembelian Barang/Item" data-confirm-description="Catatan pembelian akan dihapus permanen. Stok Barang/Item tidak berubah." data-confirm-action="Hapus Pembelian" data-confirm-tone="danger">@csrf @method('DELETE')<x-button type="submit"><x-icon name="trash" class="size-4" />Hapus</x-button></form>
            </div>
        </div>
        <x-flash-message />
        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-kpi-card label="Jumlah" :value="number_format((float) $itemPurchase->quantity, 3, ',', '.')" :suffix="$itemPurchase->feedItem->unit" icon="package" />
            <x-kpi-card label="Harga Satuan" :value="'Rp'.number_format((float) $itemPurchase->unit_cost, 0, ',', '.')" icon="coins" />
            <x-kpi-card label="Total Biaya" :value="'Rp'.number_format((float) $itemPurchase->total_cost, 0, ',', '.')" icon="coins" />
            <x-kpi-card label="Dicatat Oleh" :value="$itemPurchase->createdBy->name" icon="user" />
        </section>
        <x-card>
            <h2 class="text-base font-semibold">Informasi Pembelian</h2>
            <dl class="mt-5 grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
                <div><dt class="text-xs text-neutral-500">Tanggal Pembelian</dt><dd class="mt-1 text-sm font-medium">{{ $itemPurchase->transaction_date->locale('id')->translatedFormat('d M Y, H:i') }}</dd></div>
                <div><dt class="text-xs text-neutral-500">Barang/Item</dt><dd class="mt-1 text-sm font-medium">{{ $itemPurchase->feedItem->code }} — {{ $itemPurchase->feedItem->name }}</dd></div>
                <div><dt class="text-xs text-neutral-500">Jenis Barang/Item</dt><dd class="mt-1 text-sm font-medium">{{ $itemPurchase->feedItem->itemType->name }}</dd></div>
                <div><dt class="text-xs text-neutral-500">Vendor</dt><dd class="mt-1 text-sm font-medium">{{ $itemPurchase->vendor->name }}</dd></div>
                @if ($itemPurchase->notes)<div class="sm:col-span-2 xl:col-span-4"><dt class="text-xs text-neutral-500">Catatan</dt><dd class="mt-1 whitespace-pre-line text-sm">{{ $itemPurchase->notes }}</dd></div>@endif
            </dl>
        </x-card>
    </div>
</x-layouts.app>
