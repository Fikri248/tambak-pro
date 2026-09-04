<x-layouts.app :title="'Edit Pembelian · '.$itemPurchase->transaction_number">
    <div class="space-y-6">
        <a href="{{ route('item-purchases.show', $itemPurchase) }}" class="inline-flex items-center gap-2 text-sm text-neutral-500 hover:text-neutral-900"><x-icon name="arrow-left" class="size-4" />Detail Pembelian Barang/Item</a>
        <x-page-header title="Edit Pembelian Barang/Item" description="Nomor transaksi tetap; Total Biaya dihitung ulang oleh server." />
        <x-flash-message />
        <x-card>@include('item-purchases.partials.form')</x-card>
    </div>
</x-layouts.app>
