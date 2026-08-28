@php($isEditing = isset($commodity))

<form method="POST" action="{{ $isEditing ? route('commodities.update', $commodity) : route('commodities.store') }}" class="space-y-6">
    @csrf
    @if ($isEditing)
        @method('PUT')
    @endif

    <div class="grid gap-5 sm:grid-cols-2">
        <x-business-code label="Kode" :value="$commodity->code ?? null" />
        <x-form.input
            name="name"
            label="Nama Komoditas"
            :value="$commodity->name ?? null"
            placeholder="Contoh: Udang Vaname"
            maxlength="255"
            required
            autocomplete="off"
        />
        <x-form.input
            name="category"
            label="Kategori"
            :value="$commodity->category ?? null"
            placeholder="Contoh: Udang, Ikan, Kepiting"
            maxlength="100"
            autocomplete="off"
        />
        <x-form.input
            name="unit"
            label="Satuan"
            :value="$commodity->unit ?? 'ekor'"
            placeholder="Contoh: ekor, kg, unit"
            maxlength="50"
            required
            autocomplete="off"
        />
    </div>

    <x-form.textarea name="description" label="Deskripsi" :value="$commodity->description ?? null" placeholder="Catatan tambahan mengenai komoditas" />

    @if ($isEditing)
        <div class="flex items-center justify-between gap-4 rounded-lg border border-neutral-200 px-4 py-3">
            <div>
                <p class="text-sm font-medium text-neutral-800">Status komoditas</p>
                <p class="mt-0.5 text-xs text-neutral-500">Status diubah melalui aksi khusus agar stok dan Batch aktif selalu diperiksa.</p>
            </div>
            <x-badge>{{ $commodity->status === 'ACTIVE' ? 'Aktif' : 'Tidak Aktif' }}</x-badge>
        </div>
    @endif

    <div class="flex flex-col-reverse gap-3 border-t border-neutral-200 pt-5 sm:flex-row sm:justify-end">
        <x-button variant="secondary" :href="$isEditing ? route('commodities.show', $commodity) : route('commodities.index')" data-crud-modal-cancel>Batal</x-button>
        <x-button type="submit">{{ $isEditing ? 'Simpan Perubahan' : 'Simpan Komoditas' }}</x-button>
    </div>
</form>
