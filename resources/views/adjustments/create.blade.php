<x-layouts.app title="Tambah Perubahan Jumlah">
    <div class="mx-auto max-w-5xl space-y-6">
        <div>
            <a href="{{ route('adjustments.index') }}" class="mb-4 inline-flex items-center gap-2 text-sm text-neutral-500 hover:text-neutral-900">
                <x-icon name="arrow-left" class="size-4" />
                Perubahan Jumlah
            </a>
            <x-page-header title="Tambah Perubahan Jumlah" description="Catat kematian, kehilangan, atau penyesuaian stok pada petak." />
        </div>

        <x-flash-message />

        @if ($locations->isEmpty())
            <x-card>
                <x-empty-state title="Tidak ada stok yang dapat disesuaikan" description="Belum ada petak aktif dengan stok Batch positif." icon="adjustment" />
            </x-card>
        @else
            <form
                method="POST"
                action="{{ route('adjustments.store') }}"
                class="space-y-6"
                data-adjustment-form
                data-old-location="{{ old('location_id') }}"
                data-old-batch="{{ old('batch_id') }}"
            >
                @csrf
                <script type="application/json" data-adjustment-batches>@json($batchOptions)</script>

                <x-card>
                    <div class="grid gap-5 md:grid-cols-2">
                        <x-form.input name="transaction_date" label="Tanggal Transaksi" type="datetime-local" :value="now()->format('Y-m-d\TH:i')" required />

                        <x-form.select name="location_id" label="Petak" required data-adjustment-location>
                            <option value="">Pilih petak</option>
                            @foreach ($locations as $location)
                                <option value="{{ $location->id }}" data-label="{{ $location->name }}" @selected((string) old('location_id') === (string) $location->id)>
                                    {{ $location->name }}{{ $location->parent ? ' — '.$location->parent->name : '' }}
                                </option>
                            @endforeach
                        </x-form.select>

                        <div class="md:col-span-2">
                            <x-form.select name="batch_id" label="Batch / Komoditas" required disabled data-adjustment-batch>
                                <option value="">Pilih petak terlebih dahulu</option>
                            </x-form.select>
                        </div>

                        <div class="rounded-lg border border-neutral-200 bg-neutral-50 px-4 py-3">
                            <p class="text-xs font-medium text-neutral-500">Stok Saat Ini</p>
                            <p class="mt-1 text-lg font-semibold tabular-nums text-neutral-950" data-adjustment-stock>0 unit</p>
                            <p class="mt-1 text-xs text-neutral-500">Stok aktual akan diperiksa kembali saat transaksi disimpan.</p>
                        </div>

                        <x-form.select name="adjustment_type" label="Jenis Perubahan" required data-adjustment-type>
                            <option value="">Pilih jenis perubahan</option>
                            @foreach ($typeLabels as $value => $label)
                                <option value="{{ $value }}" data-label="{{ $label }}" @selected(old('adjustment_type') === $value)>{{ $label }}</option>
                            @endforeach
                        </x-form.select>

                        <div class="hidden" data-adjustment-direction-field>
                            <x-form.select name="direction" label="Arah Perubahan" data-adjustment-direction>
                                <option value="">Pilih arah</option>
                                <option value="IN" @selected(old('direction') === 'IN')>Tambah Jumlah</option>
                                <option value="OUT" @selected(old('direction') === 'OUT')>Kurangi Jumlah</option>
                            </x-form.select>
                        </div>

                        <div>
                            <x-form.input name="quantity" label="Jumlah" type="number" min="0.001" step="0.001" placeholder="100" required data-adjustment-quantity />
                            <p class="mt-1.5 text-xs text-neutral-500">Masukkan angka absolut. Satuan: <span data-adjustment-unit>unit</span></p>
                        </div>

                        <div class="md:col-span-2">
                            <x-form.textarea name="reason" label="Alasan" rows="4" placeholder="Jelaskan alasan perubahan stok." required />
                        </div>
                    </div>
                </x-card>

                <x-card>
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h2 class="text-base font-semibold text-neutral-950">Ringkasan Perubahan</h2>
                            <p class="mt-1 text-sm text-neutral-600" data-adjustment-preview-batch>Batch belum dipilih</p>
                        </div>
                        <x-badge><span data-adjustment-preview-type>Jenis belum dipilih</span></x-badge>
                    </div>
                    <dl class="mt-5 grid gap-x-8 gap-y-4 sm:grid-cols-2 xl:grid-cols-3">
                        <div><dt class="text-xs text-neutral-500">Petak</dt><dd class="mt-1 text-sm font-medium text-neutral-800" data-adjustment-preview-location>Belum dipilih</dd></div>
                        <div><dt class="text-xs text-neutral-500">Sebelum</dt><dd class="mt-1 text-lg font-semibold tabular-nums text-neutral-950" data-adjustment-preview-before>0 unit</dd></div>
                        <div><dt class="text-xs text-neutral-500">Perubahan</dt><dd class="mt-1 text-lg font-semibold tabular-nums text-neutral-950" data-adjustment-preview-change>0 unit</dd></div>
                        <div><dt class="text-xs text-neutral-500">Sesudah</dt><dd class="mt-1 text-lg font-semibold tabular-nums text-neutral-950" data-adjustment-preview-after>0 unit</dd></div>
                        <div class="sm:col-span-2"><dt class="text-xs text-neutral-500">Alasan</dt><dd class="mt-1 text-sm leading-5 text-neutral-800" data-adjustment-preview-reason>Belum diisi</dd></div>
                    </dl>
                    <p class="mt-4 hidden text-xs font-medium text-neutral-700" data-adjustment-preview-warning role="status"></p>
                </x-card>

                <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <x-button variant="secondary" :href="route('adjustments.index')" data-crud-modal-cancel>Batal</x-button>
                    <x-button type="submit" data-adjustment-submit>
                        <x-icon name="check" class="size-4" />
                        Simpan Perubahan
                    </x-button>
                </div>
            </form>
        @endif
    </div>
</x-layouts.app>
