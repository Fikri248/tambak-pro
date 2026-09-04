@php
    $isEditing = isset($itemPurchase);
    $selectedItem = old('feed_item_id', $itemPurchase->feed_item_id ?? '');
    $selectedVendor = old('vendor_id', $itemPurchase->vendor_id ?? '');
@endphp

<form method="POST" action="{{ $isEditing ? route('item-purchases.update', $itemPurchase) : route('item-purchases.store') }}" class="space-y-6" data-purchase-form>
    @csrf
    @if ($isEditing) @method('PUT') @endif

    <div class="grid gap-5 sm:grid-cols-2">
        <x-business-code label="No. Transaksi" :value="$itemPurchase->transaction_number ?? null" />
        <x-form.input name="transaction_date" label="Tanggal Pembelian" type="datetime-local" :value="$isEditing ? $itemPurchase->transaction_date->format('Y-m-d\TH:i') : now()->format('Y-m-d\TH:i')" required />

        <x-form.select name="feed_item_id" label="Barang/Item" required data-purchase-item>
            <option value="">Pilih Barang/Item</option>
            @foreach ($feedItems as $item)
                <option value="{{ $item->id }}" data-unit="{{ $item->unit }}" data-price="{{ $item->default_price }}" data-vendor="{{ $item->default_vendor_id }}" @selected((string) $selectedItem === (string) $item->id)>{{ $item->code }} — {{ $item->name }} — {{ $item->itemType->name }}{{ $item->status !== 'ACTIVE' ? ' (Tidak aktif)' : '' }}</option>
            @endforeach
        </x-form.select>
        <x-form.select name="vendor_id" label="Vendor" required data-purchase-vendor>
            <option value="">Pilih Vendor</option>
            @foreach ($vendors as $vendor)
                <option value="{{ $vendor->id }}" @selected((string) $selectedVendor === (string) $vendor->id)>{{ $vendor->code }} — {{ $vendor->name }} — {{ $vendor->vendorType->name }}{{ $vendor->status !== 'ACTIVE' ? ' (Tidak aktif)' : '' }}</option>
            @endforeach
        </x-form.select>

        <x-form.input name="quantity" label="Jumlah" type="number" :value="$itemPurchase->quantity ?? null" min="0.001" step="0.001" required inputmode="decimal" data-purchase-quantity />
        <x-form.input name="unit_cost" label="Harga Satuan" type="number" :value="$itemPurchase->unit_cost ?? null" min="0" step="0.0001" required inputmode="decimal" data-purchase-cost />
    </div>

    <div class="rounded-lg border border-neutral-200 bg-neutral-50 px-4 py-3">
        <p class="text-xs text-neutral-500">Total Biaya (dihitung ulang oleh server)</p>
        <p data-purchase-total class="mt-1 text-xl font-semibold tabular-nums text-neutral-950">Rp0</p>
    </div>

    <x-form.textarea name="notes" label="Catatan" :value="$itemPurchase->notes ?? null" placeholder="Catatan pembelian (opsional)" />

    <div class="flex flex-col-reverse gap-3 border-t border-neutral-200 pt-5 sm:flex-row sm:justify-end">
        <x-button variant="secondary" :href="$isEditing ? route('item-purchases.show', $itemPurchase) : route('item-purchases.index')">Batal</x-button>
        <x-button type="submit">{{ $isEditing ? 'Simpan Perubahan' : 'Simpan Pembelian' }}</x-button>
    </div>
</form>
