@php
    $typeError = $errors->has('vendor_type_name') || $errors->has('vendor_type_delete');
@endphp

<div class="space-y-2" data-vendor-type-field data-select-name="vendor_type_id">
    <x-form.select name="vendor_type_id" label="Jenis Vendor" required>
        <option value="">Pilih Jenis Vendor</option>
        @foreach ($vendorTypes as $vendorType)
            <option value="{{ $vendorType->id }}" @selected((string) $selectedTypeId === (string) $vendorType->id)>{{ $vendorType->name }}</option>
        @endforeach
    </x-form.select>

    <details data-vendor-type-panel @if ($typeError) open @endif class="rounded-lg border border-dashed border-neutral-300 bg-neutral-50/70 px-3 py-2.5">
        <summary class="cursor-pointer text-xs font-semibold text-neutral-700 marker:text-neutral-400">Kelola Jenis Vendor</summary>
        <div class="mt-3 space-y-4">
            <div data-vendor-type-feedback class="hidden rounded-md border border-neutral-200 bg-white px-3 py-2 text-xs text-neutral-700" role="status" aria-live="polite"></div>

            @if ($typeError)
                <div class="rounded-md border border-neutral-300 bg-white px-3 py-2 text-xs font-medium text-neutral-700" role="alert">
                    {{ $errors->first('vendor_type_name') ?: $errors->first('vendor_type_delete') }}
                </div>
            @endif

            <div>
                <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-neutral-500">Pilihan Tersedia</p>
                <div data-vendor-type-list class="max-h-56 space-y-2 overflow-y-auto pr-1">
                    @foreach ($vendorTypes as $vendorType)
                        <div
                            data-vendor-type-row
                            data-option-id="{{ $vendorType->id }}"
                            data-option-name="{{ $vendorType->name }}"
                            data-system="{{ $vendorType->is_system ? 'true' : 'false' }}"
                            data-update-url="{{ route('vendor-types.update', $vendorType) }}"
                            data-delete-url="{{ route('vendor-types.destroy', $vendorType) }}"
                            class="flex flex-col gap-2 rounded-md border border-neutral-200 bg-white px-3 py-2 sm:flex-row sm:items-center sm:justify-between"
                        >
                            <div class="min-w-0">
                                <span data-vendor-type-row-name class="block break-words text-sm font-medium text-neutral-800">{{ $vendorType->name }}</span>
                                @if ($vendorType->is_system)
                                    <span class="text-[11px] text-neutral-500">Jenis bawaan sistem</span>
                                @endif
                            </div>
                            <div data-vendor-type-actions hidden class="flex shrink-0 flex-wrap gap-1 sm:justify-end">
                                <button type="button" data-vendor-type-edit class="inline-flex min-h-9 items-center gap-1.5 rounded-md px-2.5 text-xs font-medium text-neutral-600 hover:bg-neutral-100 hover:text-neutral-900" aria-label="Edit Jenis Vendor {{ $vendorType->name }}">
                                    <x-icon name="edit" class="size-3.5" />Edit
                                </button>
                                <button type="button" data-vendor-type-delete class="inline-flex min-h-9 items-center gap-1.5 rounded-md px-2.5 text-xs font-medium text-neutral-600 hover:bg-neutral-100 hover:text-neutral-900" aria-label="Hapus Jenis Vendor {{ $vendorType->name }}">
                                    <x-icon name="trash" class="size-3.5" />Hapus
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
                <p data-vendor-type-empty @class(['mt-2 text-xs text-neutral-500', 'hidden' => $vendorTypes->isNotEmpty()])>Belum ada Jenis Vendor.</p>
            </div>

            <div data-vendor-type-editor hidden class="space-y-3 rounded-md border border-neutral-300 bg-white p-3" role="group" aria-label="Edit Jenis Vendor">
                <label class="block text-xs font-medium text-neutral-700">
                    Nama Jenis Vendor
                    <input data-vendor-type-edit-input type="text" maxlength="255" autocomplete="off" class="mt-1.5 block h-10 w-full rounded-lg border border-neutral-200 bg-white px-3 text-sm text-neutral-900 focus:border-neutral-400 focus:outline-none focus:ring-2 focus:ring-neutral-200">
                </label>
                <div class="flex flex-wrap justify-end gap-2">
                    <button type="button" data-vendor-type-edit-cancel class="min-h-9 rounded-md px-3 text-xs font-medium text-neutral-600 hover:bg-neutral-100">Batal</button>
                    <button type="button" data-vendor-type-edit-save class="min-h-9 rounded-md border border-neutral-300 bg-white px-3 text-xs font-semibold text-neutral-800 hover:bg-neutral-50">Simpan</button>
                </div>
            </div>

            <div data-vendor-type-delete-confirm hidden class="space-y-3 rounded-md border border-neutral-300 bg-white p-3" role="group" aria-label="Konfirmasi hapus Jenis Vendor">
                <div>
                    <p data-vendor-type-delete-title class="text-sm font-semibold text-neutral-900"></p>
                    <p data-vendor-type-delete-message class="mt-1 text-xs leading-5 text-neutral-600"></p>
                </div>
                <div class="flex flex-wrap justify-end gap-2">
                    <button type="button" data-vendor-type-delete-cancel class="min-h-9 rounded-md px-3 text-xs font-medium text-neutral-600 hover:bg-neutral-100">Batal</button>
                    <button type="button" data-vendor-type-delete-confirm-button class="min-h-9 rounded-md border border-neutral-300 bg-white px-3 text-xs font-semibold text-neutral-800 hover:bg-neutral-50">Hapus</button>
                </div>
            </div>

            <details data-vendor-type-add @if ($errors->has('vendor_type_name')) open @endif class="rounded-md border border-neutral-200 bg-white px-3 py-2.5">
                <summary data-vendor-type-add-summary class="cursor-pointer text-xs font-semibold text-neutral-700 marker:text-neutral-400">+ Tambah Jenis Baru</summary>
                <div class="mt-3 space-y-3">
                    <x-form.input name="vendor_type_name" label="Nama Jenis Vendor Baru" placeholder="Contoh: Vendor Obat" maxlength="255" autocomplete="off" />
                    <div class="flex justify-end">
                        <x-button type="submit" variant="secondary" :formaction="route('vendor-types.store')" formmethod="POST" formnovalidate name="_method" value="POST" data-vendor-type-submit data-lookup-input="vendor_type_name" data-select-name="vendor_type_id">Simpan Jenis Vendor</x-button>
                    </div>
                </div>
            </details>

            <noscript>
                <div class="space-y-2 border-t border-neutral-200 pt-3">
                    <p class="text-xs text-neutral-500">Edit dan hapus tetap tersedia tanpa JavaScript. Form akan dimuat ulang dengan nilai Vendor dipertahankan.</p>
                    @foreach ($vendorTypes as $vendorType)
                        <details class="rounded-md border border-neutral-200 bg-white px-3 py-2">
                            <summary class="cursor-pointer text-sm font-medium text-neutral-800">{{ $vendorType->name }}</summary>
                            <div class="mt-3 space-y-3">
                                <label class="block text-xs font-medium text-neutral-700">Nama Jenis Vendor
                                    <input name="vendor_type_names[{{ $vendorType->id }}]" value="{{ old("vendor_type_names.{$vendorType->id}", $vendorType->name) }}" maxlength="255" required class="mt-1.5 block h-10 w-full rounded-lg border border-neutral-200 px-3 text-sm">
                                </label>
                                <div class="flex flex-wrap gap-2">
                                    <button type="submit" formaction="{{ route('vendor-types.update', $vendorType) }}" formmethod="POST" formnovalidate name="_method" value="PATCH" class="min-h-9 rounded-md border border-neutral-300 px-3 text-xs font-semibold">Simpan Edit</button>
                                    <details class="w-full rounded-md border border-neutral-200 p-2">
                                        <summary class="cursor-pointer text-xs font-medium text-neutral-600">Konfirmasi Hapus</summary>
                                        <p class="my-2 text-xs text-neutral-600">Jenis Vendor '{{ $vendorType->name }}' akan dihapus permanen jika aman.</p>
                                        <button type="submit" formaction="{{ route('vendor-types.destroy', $vendorType) }}" formmethod="POST" formnovalidate name="_method" value="DELETE" class="min-h-9 rounded-md border border-neutral-300 px-3 text-xs font-semibold">Hapus</button>
                                    </details>
                                </div>
                            </div>
                        </details>
                    @endforeach
                </div>
            </noscript>

            <template data-vendor-type-row-template>
                <div data-vendor-type-row class="flex flex-col gap-2 rounded-md border border-neutral-200 bg-white px-3 py-2 sm:flex-row sm:items-center sm:justify-between">
                    <div class="min-w-0"><span data-vendor-type-row-name class="block break-words text-sm font-medium text-neutral-800"></span></div>
                    <div data-vendor-type-actions class="flex shrink-0 flex-wrap gap-1 sm:justify-end">
                        <button type="button" data-vendor-type-edit class="inline-flex min-h-9 items-center gap-1.5 rounded-md px-2.5 text-xs font-medium text-neutral-600 hover:bg-neutral-100 hover:text-neutral-900"><x-icon name="edit" class="size-3.5" />Edit</button>
                        <button type="button" data-vendor-type-delete class="inline-flex min-h-9 items-center gap-1.5 rounded-md px-2.5 text-xs font-medium text-neutral-600 hover:bg-neutral-100 hover:text-neutral-900"><x-icon name="trash" class="size-3.5" />Hapus</button>
                    </div>
                </div>
            </template>
        </div>
    </details>
</div>
