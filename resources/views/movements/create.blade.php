<x-layouts.app title="Catat Pemindahan Stok">
    <div class="mx-auto max-w-5xl space-y-6">
        <div>
            <a href="{{ route('movements.index') }}" class="mb-4 inline-flex items-center gap-2 text-sm text-neutral-500 hover:text-neutral-900">
                <x-icon name="arrow-left" class="size-4" />
                Pemindahan Stok
            </a>
            <x-page-header title="Catat Pemindahan Stok" description="Pindahkan sebagian atau seluruh stok dari satu petak ke petak lainnya." />
        </div>

        <x-flash-message />

        @if ($sourceLocations->isEmpty())
            <x-card>
                <x-empty-state title="Tidak ada stok yang dapat dipindahkan" description="Belum ada petak aktif dengan stok Batch positif." icon="transfer" />
            </x-card>
        @else
            <form
                method="POST"
                action="{{ route('movements.store') }}"
                class="space-y-6"
                data-movement-form
                data-old-source="{{ old('from_location_id') }}"
                data-old-batch="{{ old('batch_id') }}"
                data-old-destination="{{ old('to_location_id') }}"
            >
                @csrf

                <script type="application/json" data-movement-batches>@json($batchOptions)</script>
                <script type="application/json" data-destination-stocks>@json($destinationStocks)</script>

                <x-card>
                    <div class="grid gap-5 md:grid-cols-2">
                        <x-form.input name="transaction_date" label="Tanggal Transaksi" type="datetime-local" :value="now()->format('Y-m-d\TH:i')" required />

                        <x-form.select name="from_location_id" label="Petak Asal" help="Petak tempat stok berada saat ini." required data-movement-source>
                            <option value="">Pilih petak asal</option>
                            @foreach ($sourceLocations as $location)
                                <option value="{{ $location->id }}" data-label="{{ $location->name }}" @selected((string) old('from_location_id') === (string) $location->id)>
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
                            <p class="text-xs font-medium text-neutral-500">Stok Tersedia</p>
                            <p class="mt-1 text-lg font-semibold tabular-nums text-neutral-950" data-available-stock>0 unit</p>
                            <p class="mt-1 text-xs text-neutral-500">Stok aktual akan diperiksa kembali saat pemindahan disimpan.</p>
                        </div>

                        <x-form.select name="to_location_id" label="Petak Tujuan" help="Petak yang akan menerima stok." required data-movement-destination>
                            <option value="">Pilih petak tujuan</option>
                            @foreach ($destinations as $location)
                                <option value="{{ $location->id }}" data-label="{{ $location->name }}" @selected((string) old('to_location_id') === (string) $location->id)>
                                    {{ $location->name }}{{ $location->parent ? ' — '.$location->parent->name : '' }}
                                </option>
                            @endforeach
                        </x-form.select>

                        <div>
                            <x-form.input name="quantity" label="Jumlah Dipindahkan" help="Masukkan jumlah stok yang ingin dipindahkan." type="number" min="0.001" step="0.001" placeholder="500" required data-movement-quantity />
                            <p class="mt-1.5 text-xs text-neutral-500">Satuan: <span data-movement-unit>unit</span></p>
                        </div>

                        <div class="md:col-span-2">
                            <x-form.textarea name="notes" label="Catatan" rows="4" placeholder="Catatan pemindahan (opsional)." />
                        </div>
                    </div>
                </x-card>

                <x-card>
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h2 class="text-base font-semibold text-neutral-950">Ringkasan Pemindahan</h2>
                            <p class="mt-1 text-sm text-neutral-600" data-preview-batch>Batch belum dipilih</p>
                        </div>
                        <x-badge><span data-preview-moved>0 unit</span></x-badge>
                    </div>

                    <div class="mt-5 grid gap-4 sm:grid-cols-2">
                        <div class="rounded-lg border border-neutral-200 p-4">
                            <p class="text-xs font-medium text-neutral-500">Dari</p>
                            <p class="mt-1 text-sm font-medium text-neutral-900" data-preview-source>Petak asal belum dipilih</p>
                            <p class="mt-3 text-lg font-semibold tabular-nums text-neutral-950" data-preview-source-stock>0 → 0 unit</p>
                        </div>
                        <div class="rounded-lg border border-neutral-200 p-4">
                            <p class="text-xs font-medium text-neutral-500">Ke</p>
                            <p class="mt-1 text-sm font-medium text-neutral-900" data-preview-destination>Petak tujuan belum dipilih</p>
                            <p class="mt-3 text-lg font-semibold tabular-nums text-neutral-950" data-preview-destination-stock>0 → 0 unit</p>
                        </div>
                    </div>
                    <p class="mt-4 hidden text-xs font-medium text-neutral-700" data-preview-warning role="status"></p>
                </x-card>

                <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <x-button variant="secondary" :href="route('movements.index')" data-crud-modal-cancel>Batal</x-button>
                    <x-button type="submit" data-movement-submit>
                        <x-icon name="check" class="size-4" />
                        Catat Pemindahan
                    </x-button>
                </div>
            </form>
        @endif
    </div>
</x-layouts.app>
