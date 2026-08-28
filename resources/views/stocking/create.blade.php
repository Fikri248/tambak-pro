<x-layouts.app title="Tambah Pembibitan">
    <div class="mx-auto max-w-5xl space-y-6">
        <div>
            <a href="{{ route('stocking.index') }}" class="mb-4 inline-flex items-center gap-2 text-sm text-neutral-500 hover:text-neutral-900">
                <x-icon name="arrow-left" class="size-4" />
                Transaksi Pembibitan
            </a>
            <x-page-header title="Tambah Pembibitan" description="Catat bibit baru yang masuk ke petak budidaya." />
        </div>

        <x-flash-message />

        <form method="POST" action="{{ route('stocking.store') }}" class="space-y-6" data-stocking-form>
            @csrf

            <x-card>
                <div class="grid gap-5 md:grid-cols-2">
                    <x-form.input name="transaction_date" label="Tanggal Transaksi" type="datetime-local" :value="now()->format('Y-m-d\TH:i')" required />

                    <x-form.select name="location_id" label="Lokasi Petak" required data-summary-source="location">
                        <option value="">Pilih lokasi petak</option>
                        @foreach ($locations as $location)
                            <option value="{{ $location->id }}" data-label="{{ $location->name }}" @selected((string) old('location_id') === (string) $location->id)>
                                {{ $location->name }}{{ $location->parent ? ' — '.$location->parent->name : '' }}
                            </option>
                        @endforeach
                    </x-form.select>

                    <x-form.select name="commodity_id" label="Komoditas" required data-summary-source="commodity">
                        <option value="">Pilih komoditas</option>
                        @foreach ($commodities as $commodity)
                            <option value="{{ $commodity->id }}" data-label="{{ $commodity->name }}" data-unit="{{ $commodity->unit }}" @selected((string) old('commodity_id') === (string) $commodity->id)>
                                {{ $commodity->name }} · {{ $commodity->unit }}
                            </option>
                        @endforeach
                    </x-form.select>

                    <x-form.select name="vendor_id" label="Vendor Bibit" required data-summary-source="vendor">
                        <option value="">Pilih Vendor</option>
                        @foreach ($vendors as $vendor)
                            <option value="{{ $vendor->id }}" data-label="{{ $vendor->name }}" @selected((string) old('vendor_id') === (string) $vendor->id)>
                                {{ $vendor->name }} · {{ $vendor->vendor_type === 'SEED' ? 'Vendor Bibit' : 'Vendor Beragam' }}
                            </option>
                        @endforeach
                    </x-form.select>

                    <x-business-code label="Kode Batch" data-summary-source="batch" data-value="" />

                    <div>
                        <x-form.input name="quantity" label="Jumlah Bibit" type="number" min="0.001" step="0.001" placeholder="1000" required data-summary-source="quantity" />
                        <p class="mt-1.5 text-xs text-neutral-500">Satuan: <span data-commodity-unit>unit</span></p>
                    </div>

                    <x-form.input name="total_cost" label="Total Biaya" type="number" min="0" step="0.01" placeholder="500000" required data-summary-source="cost" />

                    <div class="rounded-lg border border-neutral-200 bg-neutral-50 px-4 py-3">
                        <p class="text-xs font-medium text-neutral-500">Harga per <span data-unit-label>unit</span></p>
                        <p class="mt-1 text-lg font-semibold tabular-nums text-neutral-950" data-unit-cost>Rp0</p>
                        <p class="mt-1 text-xs text-neutral-500">Dihitung ulang oleh server saat transaksi disimpan.</p>
                    </div>

                    <div class="md:col-span-2">
                        <x-form.textarea name="notes" label="Catatan" rows="4" placeholder="Catatan pembelian atau kondisi bibit (opsional)." />
                    </div>
                </div>
            </x-card>

            <x-card>
                <h2 class="text-base font-semibold text-neutral-950">Ringkasan Pembibitan</h2>
                <dl class="mt-4 grid gap-x-8 gap-y-4 sm:grid-cols-2">
                    <div><dt class="text-xs text-neutral-500">Petak</dt><dd class="mt-1 text-sm font-medium text-neutral-800" data-summary-location>Belum dipilih</dd></div>
                    <div><dt class="text-xs text-neutral-500">Komoditas</dt><dd class="mt-1 text-sm font-medium text-neutral-800" data-summary-commodity>Belum dipilih</dd></div>
                    <div><dt class="text-xs text-neutral-500">Vendor</dt><dd class="mt-1 text-sm font-medium text-neutral-800" data-summary-vendor>Belum dipilih</dd></div>
                    <div><dt class="text-xs text-neutral-500">Batch</dt><dd class="mt-1 font-mono text-sm font-medium text-neutral-800" data-summary-batch>Belum diisi</dd></div>
                    <div><dt class="text-xs text-neutral-500">Jumlah</dt><dd class="mt-1 text-sm font-medium tabular-nums text-neutral-800" data-summary-quantity>0 unit</dd></div>
                    <div><dt class="text-xs text-neutral-500">Total Biaya</dt><dd class="mt-1 text-sm font-medium tabular-nums text-neutral-800" data-summary-cost>Rp0</dd></div>
                    <div><dt class="text-xs text-neutral-500">Harga per Satuan</dt><dd class="mt-1 text-sm font-medium tabular-nums text-neutral-800" data-summary-unit-cost>Rp0</dd></div>
                </dl>
            </x-card>

            <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <x-button variant="secondary" :href="route('stocking.index')" data-crud-modal-cancel>Batal</x-button>
                <x-button type="submit" data-submit-button>
                    <x-icon name="check" class="size-4" />
                    Simpan Pembibitan
                </x-button>
            </div>
        </form>
    </div>
</x-layouts.app>
