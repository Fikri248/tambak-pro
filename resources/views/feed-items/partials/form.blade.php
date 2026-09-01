@php
    $isEditing = isset($feedItem);
    $selectedType = old('item_type', $feedItem->item_type ?? 'FEED');
    $selectedVendor = old('default_vendor_id', $feedItem->default_vendor_id ?? '');
@endphp

<form method="POST" action="{{ $isEditing ? route('feed-items.update', $feedItem) : route('feed-items.store') }}" class="space-y-6">
    @csrf
    @if ($isEditing) @method('PUT') @endif

    <div class="grid gap-5 sm:grid-cols-2">
        <x-business-code label="Kode" :value="$feedItem->code ?? null" />
        <x-form.select name="item_type" label="Jenis" required>
            @foreach ($typeLabels as $value => $label)
                <option value="{{ $value }}" @selected($selectedType === $value)>{{ $label }}</option>
            @endforeach
        </x-form.select>

        <x-form.input name="name" label="Nama Pakan, Nutrisi, atau Obat" :value="$feedItem->name ?? null" placeholder="Contoh: Pakan Starter Premium" maxlength="255" required autocomplete="off" />
        <x-form.select name="default_vendor_id" label="Vendor Utama">
            <option value="">Tanpa Vendor Utama</option>
            @foreach ($vendors as $vendor)
                <option value="{{ $vendor->id }}" @selected((string) $selectedVendor === (string) $vendor->id)>
                    {{ $vendor->name }} — {{ $vendor->vendorType->name }}{{ $vendor->status !== 'ACTIVE' ? ' (Tidak aktif — ganti atau kosongkan)' : '' }}
                </option>
            @endforeach
        </x-form.select>

        <x-form.input name="unit" label="Satuan" :value="$feedItem->unit ?? null" placeholder="Contoh: kg, liter, botol" maxlength="50" required autocomplete="off" />
        <x-form.input name="default_price" label="Harga Acuan per Satuan" type="number" :value="$feedItem->default_price ?? 0" min="0" step="0.01" placeholder="20000" required inputmode="decimal" />
    </div>

    <x-form.textarea name="description" label="Deskripsi" :value="$feedItem->description ?? null" placeholder="Keterangan penggunaan atau karakteristik kebutuhan" />

    @if ($isEditing)
        <div class="flex items-center justify-between gap-4 rounded-lg border border-neutral-200 px-4 py-3">
            <div>
                <p class="text-sm font-medium text-neutral-800">Status kebutuhan</p>
                <p class="mt-0.5 text-xs text-neutral-500">Status diubah melalui aksi khusus agar histori penggunaan tetap terjaga.</p>
            </div>
            <x-badge>{{ $feedItem->status === 'ACTIVE' ? 'Aktif' : 'Tidak Aktif' }}</x-badge>
        </div>
    @endif

    <div class="flex flex-col-reverse gap-3 border-t border-neutral-200 pt-5 sm:flex-row sm:justify-end">
        <x-button variant="secondary" :href="$isEditing ? route('feed-items.show', $feedItem) : route('feed-items.index')" data-crud-modal-cancel>Batal</x-button>
        <x-button type="submit">{{ $isEditing ? 'Simpan Perubahan' : 'Simpan Kebutuhan' }}</x-button>
    </div>
</form>
