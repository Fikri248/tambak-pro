<x-layouts.app :title="'Edit '.$feedingTransaction->transaction_number">
    <div class="mx-auto max-w-4xl space-y-6">
        <div>
            <a href="{{ route('feeding.show', $feedingTransaction) }}" class="mb-4 inline-flex items-center gap-2 text-sm text-neutral-500 hover:text-neutral-900">
                <x-icon name="arrow-left" class="size-4" />
                {{ $feedingTransaction->transaction_number }}
            </a>
            <x-page-header title="Edit Penggunaan Barang/Item" description="Perbarui penggunaan dan biaya tanpa mengubah stok." />
        </div>

        <x-flash-message />

        <form method="POST" action="{{ route('feeding.update', $feedingTransaction) }}" class="space-y-6">
            @csrf
            @method('PUT')

            <x-card>
                <div class="grid gap-5 md:grid-cols-2">
                    <x-form.input
                        name="transaction_date"
                        label="Tanggal Transaksi"
                        type="datetime-local"
                        :value="$feedingTransaction->transaction_date->format('Y-m-d\TH:i')"
                        required
                    />

                    <x-form.select name="location_id" label="Petak" required>
                        <option value="">Pilih petak</option>
                        @foreach ($locations as $location)
                            <option value="{{ $location->id }}" @selected((string) old('location_id', $feedingTransaction->location_id) === (string) $location->id)>
                                {{ $location->name }}{{ $location->parent ? ' — '.$location->parent->name : '' }}
                            </option>
                        @endforeach
                    </x-form.select>

                    <div class="md:col-span-2">
                        <x-form.select name="batch_id" label="Batch / Cakupan" help="Pilih Batch yang memiliki stok pada Petak, atau seluruh Petak.">
                            <option value="">Seluruh Petak</option>
                            @foreach ($batches as $batch)
                                <option value="{{ $batch->id }}" @selected((string) old('batch_id', $feedingTransaction->batch_id) === (string) $batch->id)>
                                    {{ $batch->batch_code }} · {{ $batch->commodity->name }}
                                </option>
                            @endforeach
                        </x-form.select>
                    </div>

                    <x-form.select name="feed_item_id" label="Barang/Item" required>
                        <option value="">Pilih kebutuhan</option>
                        @foreach ($feedItems as $feedItem)
                            <option value="{{ $feedItem->id }}" @selected((string) old('feed_item_id', $feedingTransaction->feed_item_id) === (string) $feedItem->id)>
                                {{ $feedItem->code }} — {{ $feedItem->name }}
                            </option>
                        @endforeach
                    </x-form.select>

                    <x-form.select name="vendor_id" label="Vendor">
                        <option value="">Tanpa Vendor</option>
                        @foreach ($vendors as $vendor)
                            <option value="{{ $vendor->id }}" @selected((string) old('vendor_id', $feedingTransaction->vendor_id) === (string) $vendor->id)>
                                {{ $vendor->code }} — {{ $vendor->name }}
                            </option>
                        @endforeach
                    </x-form.select>

                    <x-form.input
                        name="feed_quantity"
                        label="Jumlah Penggunaan"
                        type="number"
                        min="0.001"
                        step="0.001"
                        :value="$feedingTransaction->feed_quantity"
                        required
                    />

                    <x-form.input
                        name="unit_cost"
                        label="Harga per Satuan"
                        type="number"
                        min="0"
                        step="0.0001"
                        :value="$feedingTransaction->unit_cost"
                        help="Total biaya dihitung ulang oleh sistem."
                        required
                    />

                    <div class="md:col-span-2">
                        <x-form.textarea name="notes" label="Catatan" :value="$feedingTransaction->notes" rows="4" />
                    </div>
                </div>
            </x-card>

            <x-card>
                <h2 class="text-base font-semibold text-neutral-950">Kebijakan Stok Saat Pencatatan</h2>
                <p class="mt-1 text-sm leading-6 text-neutral-600">Jika Petak dan Batch tetap sama, nilai historis tidak berubah. Jika konteks stok diganti, sistem menghitung ulang snapshot dari stok yang dikunci saat perubahan disimpan.</p>
            </x-card>

            <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <x-button variant="secondary" :href="route('feeding.show', $feedingTransaction)" data-crud-modal-cancel>Batal</x-button>
                <x-button type="submit">
                    <x-icon name="check" class="size-4" />
                    Simpan Perubahan
                </x-button>
            </div>
        </form>
    </div>
</x-layouts.app>
