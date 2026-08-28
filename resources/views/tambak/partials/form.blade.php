@php
    $isEditing = isset($location);
    $selectedType = old('location_type', $location->location_type ?? 'TAMBAK');
    $selectedParent = (string) old('parent_id', $location->parent_id ?? '');
@endphp

<form method="POST" action="{{ $isEditing ? route('tambak.update', $location) : route('tambak.store') }}" class="space-y-6">
    @csrf
    @if ($isEditing)
        @method('PUT')
    @endif

    <div class="grid gap-5 sm:grid-cols-2">
        <x-business-code label="Kode" :value="$location->code ?? null" />
        <x-form.input
            name="name"
            label="Nama Lokasi"
            :value="$location->name ?? null"
            placeholder="Contoh: Tambak C"
            maxlength="255"
            required
            autocomplete="off"
        />
        <x-form.select name="location_type" label="Tipe Lokasi" required>
            @foreach ($typeLabels as $value => $label)
                <option value="{{ $value }}" @selected($selectedType === $value)>{{ $label }}</option>
            @endforeach
        </x-form.select>
        <x-form.select name="parent_id" label="Induk Lokasi">
            <option value="">Tanpa induk</option>
            @foreach ($parentOptions as $parent)
                <option value="{{ $parent->id }}" @selected($selectedParent === (string) $parent->id)>
                    {{ $parent->name }} · {{ $typeLabels[$parent->location_type] ?? 'Lainnya' }}{{ $parent->status === 'INACTIVE' ? ' (Tidak Aktif)' : '' }}
                </option>
            @endforeach
        </x-form.select>
    </div>

    <div class="rounded-lg border border-neutral-200 bg-neutral-50 px-4 py-3 text-xs leading-5 text-neutral-600">
        Area umumnya tanpa induk. Tambak dapat berada di bawah Area, sedangkan Petak berada di bawah Tambak.
    </div>

    <x-form.textarea name="address" label="Alamat" :value="$location->address ?? null" placeholder="Alamat atau keterangan wilayah lokasi" />
    <x-form.textarea name="description" label="Deskripsi" :value="$location->description ?? null" placeholder="Catatan tambahan mengenai lokasi" />

    @if ($isEditing)
        <div class="flex items-center justify-between rounded-lg border border-neutral-200 px-4 py-3">
            <div>
                <p class="text-sm font-medium text-neutral-800">Status lokasi</p>
                <p class="mt-0.5 text-xs text-neutral-500">Status diubah melalui aksi khusus agar aturan stok dan anak lokasi selalu diperiksa.</p>
            </div>
            <x-badge>{{ $location->status === 'ACTIVE' ? 'Aktif' : 'Tidak Aktif' }}</x-badge>
        </div>
    @endif

    <div class="flex flex-col-reverse gap-3 border-t border-neutral-200 pt-5 sm:flex-row sm:justify-end">
        <x-button variant="secondary" :href="$isEditing ? route('tambak.show', $location) : route('tambak.index')" data-crud-modal-cancel>Batal</x-button>
        <x-button type="submit">{{ $isEditing ? 'Simpan Perubahan' : 'Tambah Lokasi' }}</x-button>
    </div>
</form>
