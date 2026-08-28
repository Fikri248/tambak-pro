<x-layouts.app title="Tambah Pemberian Pakan">
    <div class="mx-auto max-w-5xl space-y-6">
        <div>
            <a href="{{ route('feeding.index') }}" class="mb-4 inline-flex items-center gap-2 text-sm text-neutral-500 hover:text-neutral-900">
                <x-icon name="arrow-left" class="size-4" />
                Pemberian Pakan
            </a>
            <x-page-header title="Tambah Pemberian Pakan" description="Catat penggunaan pakan, nutrisi, atau obat pada petak budidaya." />
        </div>

        <x-flash-message />

        @if ($locations->isEmpty() || $feedItems->isEmpty())
            <x-card>
                <x-empty-state title="Data operasional belum tersedia" description="Diperlukan petak dengan stok positif dan minimal satu pakan, nutrisi, atau obat aktif untuk mencatat pemberian." icon="feed" />
            </x-card>
        @else
            <form
                method="POST"
                action="{{ route('feeding.store') }}"
                class="space-y-6"
                data-feeding-form
                data-old-location="{{ old('location_id') }}"
                data-old-batch="{{ old('batch_id') }}"
                data-old-item="{{ old('feed_item_id') }}"
                data-old-vendor="{{ old('vendor_id') }}"
            >
                @csrf
                <script type="application/json" data-feeding-scopes>@json($scopeOptions)</script>
                <script type="application/json" data-feeding-items>@json($itemOptions)</script>

                <x-card>
                    <div class="grid gap-5 md:grid-cols-2">
                        <x-form.input name="transaction_date" label="Tanggal Transaksi" type="datetime-local" :value="now()->format('Y-m-d\TH:i')" required />

                        <x-form.select name="location_id" label="Petak" required data-feeding-location>
                            <option value="">Pilih petak</option>
                            @foreach ($locations as $location)
                                <option value="{{ $location->id }}" data-label="{{ $location->name }}" @selected((string) old('location_id') === (string) $location->id)>
                                    {{ $location->name }}{{ $location->parent ? ' — '.$location->parent->name : '' }}
                                </option>
                            @endforeach
                        </x-form.select>

                        <div class="md:col-span-2">
                            <x-form.select name="batch_id" label="Batch / Cakupan" disabled data-feeding-batch>
                                <option value="">Pilih petak terlebih dahulu</option>
                            </x-form.select>
                        </div>

                        <div class="rounded-lg border border-neutral-200 bg-neutral-50 px-4 py-3">
                            <p class="text-xs font-medium text-neutral-500">Stok Saat Ini</p>
                            <p class="mt-1 text-lg font-semibold tabular-nums text-neutral-950" data-feeding-stock>0 unit</p>
                            <p class="mt-1 text-xs text-neutral-500">Perkiraan awal; stok saat pencatatan dihitung kembali setelah data stok dikunci.</p>
                        </div>

                        <x-form.select name="feed_item_id" label="Pakan, Nutrisi, atau Obat" required data-feeding-item>
                            <option value="">Pilih kebutuhan</option>
                            @foreach ($feedItems as $feedItem)
                                <option value="{{ $feedItem->id }}" data-label="{{ $feedItem->name }}" @selected((string) old('feed_item_id') === (string) $feedItem->id)>
                                    {{ $feedItem->code }} — {{ $feedItem->name }} — {{ $typeLabels[$feedItem->item_type] }}
                                </option>
                            @endforeach
                        </x-form.select>

                        <x-form.select name="vendor_id" label="Vendor" data-feeding-vendor>
                            <option value="">Tanpa Vendor</option>
                            @foreach ($vendors as $vendor)
                                <option value="{{ $vendor->id }}" data-label="{{ $vendor->name }}" @selected((string) old('vendor_id') === (string) $vendor->id)>
                                    {{ $vendor->name }} — {{ $vendorTypeLabels[$vendor->vendor_type] }}
                                </option>
                            @endforeach
                        </x-form.select>

                        <div>
                            <x-form.input name="feed_quantity" label="Jumlah Penggunaan" type="number" min="0.001" step="0.001" placeholder="5" required data-feeding-quantity />
                            <p class="mt-1.5 text-xs text-neutral-500">Satuan: <span data-feeding-unit>unit</span></p>
                        </div>

                        <div>
                            <x-form.input name="unit_cost" label="Harga per Satuan" type="number" min="0" step="0.0001" placeholder="20000" required data-feeding-cost />
                            <p class="mt-1.5 text-xs text-neutral-500"><span data-feeding-cost-helper>Rp0 / unit</span>. Dapat disesuaikan dengan harga aktual.</p>
                        </div>

                        <div class="rounded-lg border border-neutral-200 bg-neutral-50 px-4 py-3 md:col-span-2">
                            <p class="text-xs font-medium text-neutral-500">Total Biaya</p>
                            <p class="mt-1 text-lg font-semibold tabular-nums text-neutral-950" data-feeding-total>Rp0</p>
                            <p class="mt-1 text-xs text-neutral-500">Dihitung ulang oleh sistem dari jumlah × harga per satuan.</p>
                        </div>

                        <div class="md:col-span-2">
                            <x-form.textarea name="notes" label="Catatan" rows="4" placeholder="Catatan penggunaan kebutuhan (opsional)." />
                        </div>
                    </div>
                </x-card>

                <x-card>
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h2 class="text-base font-semibold text-neutral-950">Ringkasan Pemberian</h2>
                            <p class="mt-1 text-sm text-neutral-600" data-feeding-summary-item>Kebutuhan belum dipilih</p>
                        </div>
                        <x-badge><span data-feeding-summary-type>Jenis belum dipilih</span></x-badge>
                    </div>
                    <dl class="mt-5 grid gap-x-8 gap-y-4 sm:grid-cols-2 xl:grid-cols-4">
                        <div><dt class="text-xs text-neutral-500">Petak</dt><dd class="mt-1 text-sm font-medium text-neutral-800" data-feeding-summary-location>Belum dipilih</dd></div>
                        <div><dt class="text-xs text-neutral-500">Cakupan</dt><dd class="mt-1 text-sm font-medium text-neutral-800" data-feeding-summary-scope>Belum dipilih</dd></div>
                        <div><dt class="text-xs text-neutral-500">Stok Saat Pencatatan</dt><dd class="mt-1 text-sm font-medium tabular-nums text-neutral-800" data-feeding-summary-stock>0 unit</dd></div>
                        <div><dt class="text-xs text-neutral-500">Vendor</dt><dd class="mt-1 text-sm font-medium text-neutral-800" data-feeding-summary-vendor>Tanpa Vendor</dd></div>
                        <div><dt class="text-xs text-neutral-500">Jumlah</dt><dd class="mt-1 text-sm font-medium tabular-nums text-neutral-800" data-feeding-summary-quantity>0 unit</dd></div>
                        <div><dt class="text-xs text-neutral-500">Harga per Satuan</dt><dd class="mt-1 text-sm font-medium tabular-nums text-neutral-800" data-feeding-summary-cost>Rp0 / satuan</dd></div>
                        <div><dt class="text-xs text-neutral-500">Total</dt><dd class="mt-1 text-lg font-semibold tabular-nums text-neutral-950" data-feeding-summary-total>Rp0</dd></div>
                    </dl>
                </x-card>

                <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <x-button variant="secondary" :href="route('feeding.index')" data-crud-modal-cancel>Batal</x-button>
                    <x-button type="submit" data-feeding-submit>
                        <x-icon name="check" class="size-4" />
                        Simpan Pemberian
                    </x-button>
                </div>
            </form>
        @endif
    </div>
</x-layouts.app>
