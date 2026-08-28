<x-layouts.app title="Edit Pemindahan Stok">
    <div class="mx-auto max-w-5xl space-y-6">
        <div>
            <a href="{{ route('movements.show', $stockMovement) }}" class="mb-4 inline-flex items-center gap-2 text-sm text-neutral-500 hover:text-neutral-900">
                <x-icon name="arrow-left" class="size-4" />
                Detail Pemindahan Stok
            </a>
            <x-page-header title="Edit Pemindahan Stok" description="Perbarui transaksi {{ $stockMovement->transaction_number }} tanpa mengubah identitas pencatatan awal." />
        </div>

        <x-flash-message />

        <div role="note" class="rounded-xl border border-neutral-200 bg-neutral-50 px-4 py-3 text-sm leading-6 text-neutral-700 sm:px-5">
            Sistem mengembalikan dampak pemindahan lama sebelum menerapkan perubahan. Penyimpanan akan ditolak jika stok tujuan sudah digunakan oleh transaksi berikutnya.
        </div>

        <form
            method="POST"
            action="{{ route('movements.update', $stockMovement) }}"
            class="space-y-6"
            data-movement-form
            data-old-source="{{ old('from_location_id', $stockMovement->from_location_id) }}"
            data-old-batch="{{ old('batch_id', $stockMovement->batch_id) }}"
            data-old-destination="{{ old('to_location_id', $stockMovement->to_location_id) }}"
        >
            @csrf
            @method('PATCH')

            <script type="application/json" data-movement-batches>@json($batchOptions)</script>
            <script type="application/json" data-destination-stocks>@json($destinationStocks)</script>

            <x-card>
                <div class="grid gap-5 md:grid-cols-2">
                    <x-form.input
                        name="transaction_date"
                        label="Tanggal Transaksi"
                        type="datetime-local"
                        :value="old('transaction_date', $stockMovement->transaction_date->format('Y-m-d\TH:i'))"
                        required
                    />

                    <x-form.select name="from_location_id" label="Petak Asal" help="Petak tempat stok berada sebelum dipindahkan." required data-movement-source>
                        <option value="">Pilih petak asal</option>
                        @foreach ($sourceLocations as $location)
                            <option
                                value="{{ $location->id }}"
                                data-label="{{ $location->name }}"
                                @selected((string) old('from_location_id', $stockMovement->from_location_id) === (string) $location->id)
                            >
                                {{ $location->name }}{{ $location->parent ? ' — '.$location->parent->name : '' }}
                            </option>
                        @endforeach
                    </x-form.select>

                    <div class="md:col-span-2">
                        <x-form.select name="batch_id" label="Batch / Komoditas" required disabled data-movement-batch>
                            <option value="">Pilih petak asal terlebih dahulu</option>
                        </x-form.select>
                    </div>

                    <div class="rounded-lg border border-neutral-200 bg-neutral-50 px-4 py-3">
                        <p class="text-xs font-medium text-neutral-500">Stok Setelah Dampak Lama Dikembalikan</p>
                        <p class="mt-1 text-lg font-semibold tabular-nums text-neutral-950" data-available-stock>0 unit</p>
                        <p class="mt-1 text-xs text-neutral-500">Ketersediaan akan diperiksa ulang dengan penguncian data saat disimpan.</p>
                    </div>

                    <x-form.select name="to_location_id" label="Petak Tujuan" help="Petak yang akan menerima stok setelah perubahan disimpan." required data-movement-destination>
                        <option value="">Pilih petak tujuan</option>
                        @foreach ($destinations as $location)
                            <option
                                value="{{ $location->id }}"
                                data-label="{{ $location->name }}"
                                @selected((string) old('to_location_id', $stockMovement->to_location_id) === (string) $location->id)
                            >
                                {{ $location->name }}{{ $location->parent ? ' — '.$location->parent->name : '' }}
                            </option>
                        @endforeach
                    </x-form.select>

                    <div>
                        <x-form.input
                            name="quantity"
                            label="Jumlah Dipindahkan"
                            help="Masukkan jumlah stok baru yang ingin dipindahkan."
                            type="number"
                            :value="$stockMovement->quantity"
                            min="0.001"
                            step="0.001"
                            placeholder="500"
                            required
                            data-movement-quantity
                        />
                        <p class="mt-1.5 text-xs text-neutral-500">Satuan: <span data-movement-unit>unit</span></p>
                    </div>

                    <div class="md:col-span-2">
                        <x-form.textarea
                            name="notes"
                            label="Catatan"
                            :value="$stockMovement->notes"
                            rows="4"
                            placeholder="Catatan pemindahan (opsional)."
                        />
                    </div>
                </div>
            </x-card>

            <x-card>
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="text-base font-semibold text-neutral-950">Ringkasan Perubahan</h2>
                        <p class="mt-1 text-sm text-neutral-600" data-preview-batch>Batch belum dipilih</p>
                    </div>
                    <x-badge><span data-preview-moved>0 unit</span></x-badge>
                </div>

                <div class="mt-5 grid gap-4 sm:grid-cols-2">
                    <div class="rounded-lg border border-neutral-200 p-4">
                        <p class="text-xs font-medium text-neutral-500">Petak Asal</p>
                        <p class="mt-1 text-sm font-medium text-neutral-900" data-preview-source>Petak asal belum dipilih</p>
                        <p class="mt-3 text-lg font-semibold tabular-nums text-neutral-950" data-preview-source-stock>0 → 0 unit</p>
                    </div>
                    <div class="rounded-lg border border-neutral-200 p-4">
                        <p class="text-xs font-medium text-neutral-500">Petak Tujuan</p>
                        <p class="mt-1 text-sm font-medium text-neutral-900" data-preview-destination>Petak tujuan belum dipilih</p>
                        <p class="mt-3 text-lg font-semibold tabular-nums text-neutral-950" data-preview-destination-stock>0 → 0 unit</p>
                    </div>
                </div>
                <p class="mt-4 hidden text-xs font-medium text-neutral-700" data-preview-warning role="status"></p>
            </x-card>

            <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <x-button variant="secondary" :href="route('movements.show', $stockMovement)" data-crud-modal-cancel>Batal</x-button>
                <x-button type="submit" data-movement-submit>
                    <x-icon name="check" class="size-4" />
                    Simpan Perubahan
                </x-button>
            </div>
        </form>
    </div>
</x-layouts.app>
