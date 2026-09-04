<x-layouts.app title="Tambah Pembelian Barang/Item">
    <div class="space-y-6">
        <a href="{{ route('item-purchases.index') }}" class="inline-flex items-center gap-2 text-sm text-neutral-500 hover:text-neutral-900"><x-icon name="arrow-left" class="size-4" />Pembelian Barang/Item</a>
        <x-page-header title="Tambah Pembelian Barang/Item" description="Catat kuantitas dan biaya pengadaan tanpa mengubah saldo stok Barang/Item." />
        <x-flash-message />
        @if ($feedItems->isEmpty() || $vendors->isEmpty())
            <x-empty-state title="Data master belum tersedia" description="Diperlukan minimal satu Barang/Item aktif dan satu Vendor aktif." icon="package" />
        @else
            <x-card>@include('item-purchases.partials.form')</x-card>
        @endif
    </div>
</x-layouts.app>
