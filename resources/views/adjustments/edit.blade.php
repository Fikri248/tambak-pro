<x-layouts.app :title="'Edit '.$stockAdjustment->transaction_number">
    @php
        $quantity = abs((float) $stockAdjustment->quantity_change);
        $direction = (float) $stockAdjustment->quantity_change >= 0 ? 'IN' : 'OUT';
    @endphp

    <div class="mx-auto max-w-4xl space-y-6">
        <div>
            <a href="{{ route('adjustments.show', $stockAdjustment) }}" class="mb-4 inline-flex items-center gap-2 text-sm text-neutral-500 hover:text-neutral-900">
                <x-icon name="arrow-left" class="size-4" />
                {{ $stockAdjustment->transaction_number }}
            </a>
            <x-page-header title="Edit Perubahan Jumlah" description="Perbarui transaksi dengan membatalkan dampak lama dan menghitung ulang stok secara aman." />
        </div>

        <x-flash-message />

        <form method="POST" action="{{ route('adjustments.update', $stockAdjustment) }}" class="space-y-6">
            @csrf
            @method('PUT')

            <input type="hidden" name="location_id" value="{{ $stockAdjustment->location_id }}">
            <input type="hidden" name="batch_id" value="{{ $stockAdjustment->batch_id }}">

            <x-card>
                <div class="mb-5 rounded-lg border border-neutral-200 bg-neutral-50 px-4 py-3">
                    <dl class="grid gap-3 text-sm sm:grid-cols-2">
                        <div>
                            <dt class="text-xs text-neutral-500">Petak</dt>
                            <dd class="mt-1 font-medium text-neutral-900">{{ $stockAdjustment->location->name }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-neutral-500">Batch</dt>
                            <dd class="mt-1 font-medium text-neutral-900">{{ $stockAdjustment->batch->batch_code }} · {{ $stockAdjustment->batch->commodity->name }}</dd>
                        </div>
                    </dl>
                    <p class="mt-3 text-xs leading-5 text-neutral-500">Petak dan Batch dikunci agar riwayat stok tetap konsisten. Sistem memeriksa aktivitas lanjutan sebelum menyimpan.</p>
                </div>

                <div class="grid gap-5 md:grid-cols-2">
                    <x-form.input
                        name="transaction_date"
                        label="Tanggal Transaksi"
                        type="datetime-local"
                        :value="$stockAdjustment->transaction_date->format('Y-m-d\TH:i')"
                        required
                    />

                    <x-form.select name="adjustment_type" label="Jenis Perubahan" required>
                        @foreach ($typeLabels as $value => $label)
                            <option value="{{ $value }}" @selected(old('adjustment_type', $stockAdjustment->adjustment_type) === $value)>{{ $label }}</option>
                        @endforeach
                    </x-form.select>

                    <x-form.select name="direction" label="Arah Perubahan" help="Digunakan saat jenis perubahan adalah Lainnya.">
                        <option value="">Ditentukan oleh jenis perubahan</option>
                        <option value="IN" @selected(old('direction', $direction) === 'IN')>Tambah Jumlah</option>
                        <option value="OUT" @selected(old('direction', $direction) === 'OUT')>Kurangi Jumlah</option>
                    </x-form.select>

                    <x-form.input
                        name="quantity"
                        label="Jumlah"
                        type="number"
                        min="0.001"
                        step="0.001"
                        :value="$quantity"
                        help="Masukkan angka absolut. Sistem menentukan tanda perubahan dari jenisnya."
                        required
                    />

                    <div class="md:col-span-2">
                        <x-form.textarea name="reason" label="Alasan" :value="$stockAdjustment->reason" rows="4" required />
                    </div>
                </div>
            </x-card>

            <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <x-button variant="secondary" :href="route('adjustments.show', $stockAdjustment)" data-crud-modal-cancel>Batal</x-button>
                <x-button type="submit">
                    <x-icon name="check" class="size-4" />
                    Simpan Perubahan
                </x-button>
            </div>
        </form>
    </div>
</x-layouts.app>
