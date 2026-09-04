@php
    $typeError = $errors->has('item_type_name') || $errors->has('item_type_delete');
@endphp

<div class="space-y-2 sm:col-span-2" data-item-type-field data-select-name="item_type_id">
    <x-form.select name="item_type_id" label="Jenis Barang/Item" required>
        <option value="">Pilih Jenis Barang/Item</option>
        @foreach ($itemTypes as $itemType)
            <option value="{{ $itemType->id }}" data-semantic="{{ $itemType->semantic_type }}" @selected((string) $selectedTypeId === (string) $itemType->id)>{{ $itemType->name }}</option>
        @endforeach
    </x-form.select>

    @if (auth()->user()->canAccess('feed-items.manage'))
        <details data-item-type-panel @if ($typeError) open @endif class="rounded-lg border border-dashed border-neutral-300 bg-neutral-50/70 px-3 py-2.5">
            <summary class="cursor-pointer text-xs font-semibold text-neutral-700 marker:text-neutral-400">Kelola Jenis Barang/Item</summary>
            <div class="mt-3 space-y-4">
                <div data-item-type-feedback class="hidden rounded-md border border-neutral-200 bg-white px-3 py-2 text-xs text-neutral-700" role="status" aria-live="polite"></div>
                @if ($typeError)
                    <div class="rounded-md border border-neutral-300 bg-white px-3 py-2 text-xs font-medium text-neutral-700" role="alert">{{ $errors->first('item_type_name') ?: $errors->first('item_type_delete') }}</div>
                @endif

                <div>
                    <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-neutral-500">Pilihan Tersedia</p>
                    <div data-item-type-list class="max-h-56 space-y-2 overflow-y-auto pr-1">
                        @foreach ($itemTypes as $itemType)
                            <div data-item-type-row data-option-id="{{ $itemType->id }}" data-option-name="{{ $itemType->name }}" data-system="{{ $itemType->is_system ? 'true' : 'false' }}" data-update-url="{{ route('item-types.update', $itemType) }}" data-delete-url="{{ route('item-types.destroy', $itemType) }}" class="flex flex-col gap-2 rounded-md border border-neutral-200 bg-white px-3 py-2 sm:flex-row sm:items-center sm:justify-between">
                                <div class="min-w-0">
                                    <span data-item-type-row-name class="block break-words text-sm font-medium text-neutral-800">{{ $itemType->name }}</span>
                                    @if ($itemType->is_system)<span class="text-[11px] text-neutral-500">Jenis bawaan sistem</span>@endif
                                </div>
                                <div data-item-type-actions hidden class="flex shrink-0 flex-wrap gap-1 sm:justify-end">
                                    <button type="button" data-item-type-edit class="inline-flex min-h-9 items-center gap-1.5 rounded-md px-2.5 text-xs font-medium text-neutral-600 hover:bg-neutral-100"><x-icon name="edit" class="size-3.5" />Edit</button>
                                    <button type="button" data-item-type-delete class="inline-flex min-h-9 items-center gap-1.5 rounded-md px-2.5 text-xs font-medium text-neutral-600 hover:bg-neutral-100"><x-icon name="trash" class="size-3.5" />Hapus</button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div data-item-type-editor hidden class="space-y-3 rounded-md border border-neutral-300 bg-white p-3">
                    <label class="block text-xs font-medium text-neutral-700">Nama Jenis Barang/Item
                        <input data-item-type-edit-input type="text" maxlength="255" autocomplete="off" class="mt-1.5 block h-10 w-full rounded-lg border border-neutral-200 px-3 text-sm">
                    </label>
                    <div class="flex justify-end gap-2"><button type="button" data-item-type-edit-cancel class="min-h-9 px-3 text-xs">Batal</button><button type="button" data-item-type-edit-save class="min-h-9 rounded-md border border-neutral-300 px-3 text-xs font-semibold">Simpan</button></div>
                </div>

                <div data-item-type-delete-confirm hidden class="space-y-3 rounded-md border border-neutral-300 bg-white p-3">
                    <div><p class="text-sm font-semibold">Hapus Jenis Barang/Item?</p><p data-item-type-delete-message class="mt-1 text-xs text-neutral-600"></p></div>
                    <div class="flex justify-end gap-2"><button type="button" data-item-type-delete-cancel class="min-h-9 px-3 text-xs">Batal</button><button type="button" data-item-type-delete-confirm-button class="min-h-9 rounded-md border border-neutral-300 px-3 text-xs font-semibold">Hapus</button></div>
                </div>

                <details data-item-type-add class="rounded-md border border-neutral-200 bg-white px-3 py-2.5">
                    <summary class="cursor-pointer text-xs font-semibold text-neutral-700">+ Tambah Jenis Baru</summary>
                    <div class="mt-3 space-y-3">
                        <x-form.input name="item_type_name" label="Nama Jenis Barang/Item Baru" placeholder="Contoh: Vitamin" maxlength="255" autocomplete="off" />
                        <div class="flex justify-end"><x-button type="submit" variant="secondary" :formaction="route('item-types.store')" formmethod="POST" formnovalidate name="_method" value="POST" data-item-type-submit>Simpan Jenis Barang/Item</x-button></div>
                    </div>
                </details>

                <noscript>
                    <p class="text-xs text-neutral-500">Kelola jenis tanpa JavaScript; nilai formulir Barang/Item akan dipertahankan saat halaman dimuat ulang.</p>
                    @foreach ($itemTypes as $itemType)
                        <div class="mt-2 flex flex-wrap items-end gap-2">
                            <label class="flex-1 text-xs">{{ $itemType->name }}<input name="item_type_name" value="{{ $itemType->name }}" class="mt-1 h-9 w-full rounded border px-2"></label>
                            <button type="submit" formaction="{{ route('item-types.update', $itemType) }}" formmethod="POST" formnovalidate name="_method" value="PATCH" class="h-9 rounded border px-3 text-xs">Edit</button>
                            <button type="submit" formaction="{{ route('item-types.destroy', $itemType) }}" formmethod="POST" formnovalidate name="_method" value="DELETE" class="h-9 rounded border px-3 text-xs">Hapus</button>
                        </div>
                    @endforeach
                </noscript>

                <template data-item-type-row-template>
                    <div data-item-type-row class="flex flex-col gap-2 rounded-md border border-neutral-200 bg-white px-3 py-2 sm:flex-row sm:items-center sm:justify-between"><span data-item-type-row-name class="text-sm font-medium"></span><div data-item-type-actions class="flex gap-1"><button type="button" data-item-type-edit class="min-h-9 px-2.5 text-xs">Edit</button><button type="button" data-item-type-delete class="min-h-9 px-2.5 text-xs">Hapus</button></div></div>
                </template>
            </div>
        </details>
    @endif
</div>
