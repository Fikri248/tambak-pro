@php
    $isEditing = isset($vendor);
    $selectedType = old('vendor_type', $vendor->vendor_type ?? 'SEED');
@endphp

<form method="POST" action="{{ $isEditing ? route('vendors.update', $vendor) : route('vendors.store') }}" class="space-y-6">
    @csrf
    @if ($isEditing) @method('PUT') @endif

    <div class="grid gap-5 sm:grid-cols-2">
        <x-business-code label="Kode Vendor" :value="$vendor->code ?? null" />
        <x-form.input name="name" label="Nama Vendor" :value="$vendor->name ?? null" placeholder="Contoh: CV Tambak Sejahtera" maxlength="255" required autocomplete="off" />
        <x-form.select name="vendor_type" label="Jenis Vendor" required>
            @foreach ($typeLabels as $value => $label)
                <option value="{{ $value }}" @selected($selectedType === $value)>{{ $label }}</option>
            @endforeach
        </x-form.select>
        <x-form.input name="phone" label="Nomor Telepon" type="tel" :value="$vendor->phone ?? null" placeholder="Contoh: 081234567890" maxlength="30" autocomplete="tel" />
        <x-form.input name="email" label="Email" type="email" :value="$vendor->email ?? null" placeholder="Contoh: vendor@example.test" maxlength="255" autocomplete="email" class="sm:col-span-2" />
    </div>

    <x-form.textarea name="address" label="Alamat" :value="$vendor->address ?? null" placeholder="Alamat lengkap atau wilayah operasional Vendor" />
    <x-form.textarea name="description" label="Deskripsi" :value="$vendor->description ?? null" placeholder="Catatan tambahan mengenai Vendor" />

    @if ($isEditing)
        <div class="flex items-center justify-between gap-4 rounded-lg border border-neutral-200 px-4 py-3">
            <div>
                <p class="text-sm font-medium text-neutral-800">Status Vendor</p>
                <p class="mt-0.5 text-xs text-neutral-500">Status diubah melalui aksi khusus agar data aktif yang terkait selalu diperiksa.</p>
            </div>
            <x-badge>{{ $vendor->status === 'ACTIVE' ? 'Aktif' : 'Tidak Aktif' }}</x-badge>
        </div>
    @endif

    <div class="flex flex-col-reverse gap-3 border-t border-neutral-200 pt-5 sm:flex-row sm:justify-end">
        <x-button variant="secondary" :href="$isEditing ? route('vendors.show', $vendor) : route('vendors.index')" data-crud-modal-cancel>Batal</x-button>
        <x-button type="submit">{{ $isEditing ? 'Simpan Perubahan' : 'Simpan Vendor' }}</x-button>
    </div>
</form>
