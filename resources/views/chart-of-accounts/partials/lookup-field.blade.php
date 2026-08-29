@php
    $lookupError = old('lookup_type') === $lookupType
        && ($errors->has($newField) || $errors->has('lookup_name') || $errors->has('lookup_delete'));
@endphp

<div class="space-y-2" data-coa-lookup-field="{{ $lookupType }}" data-select-name="{{ $fieldName }}">
    <x-form.select :name="$fieldName" :label="$label" required>
        <option value="">Pilih {{ $label }}</option>
        @foreach ($options as $option)
            <option value="{{ $option->id }}" @selected((string) old($fieldName, $selectedId) === (string) $option->id)>
                {{ $option->name }}{{ $option->status === 'INACTIVE' ? ' (Tidak Aktif)' : '' }}
            </option>
        @endforeach
    </x-form.select>

    <details data-coa-lookup-panel @if ($lookupError) open @endif class="rounded-lg border border-dashed border-neutral-300 bg-neutral-50/70 px-3 py-2.5">
        <summary class="cursor-pointer text-xs font-semibold text-neutral-700 marker:text-neutral-400">Kelola Opsi {{ $label }}</summary>
        <div class="mt-3 space-y-4">
            <div data-coa-lookup-feedback class="hidden rounded-md border border-neutral-200 bg-white px-3 py-2 text-xs text-neutral-700" role="status" aria-live="polite"></div>

            @if ($lookupError)
                <div class="rounded-md border border-neutral-300 bg-white px-3 py-2 text-xs font-medium text-neutral-700" role="alert">
                    {{ $errors->first($newField) ?: ($errors->first('lookup_name') ?: $errors->first('lookup_delete')) }}
                </div>
            @endif

            <div>
                <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-neutral-500">Pilihan Tersedia</p>
                <div data-coa-lookup-list class="max-h-56 space-y-2 overflow-y-auto pr-1">
                    @foreach ($managedOptions as $option)
                        <div
                            data-coa-lookup-row
                            data-option-id="{{ $option->id }}"
                            data-option-name="{{ $option->name }}"
                            data-option-status="{{ $option->status }}"
                            data-update-url="{{ route('chart-of-accounts.lookups.update', [$lookupType, $option->id]) }}"
                            data-delete-url="{{ route('chart-of-accounts.lookups.destroy', [$lookupType, $option->id]) }}"
                            class="flex flex-col gap-2 rounded-md border border-neutral-200 bg-white px-3 py-2 sm:flex-row sm:items-center sm:justify-between"
                        >
                            <div class="min-w-0">
                                <span data-coa-lookup-row-name class="block break-words text-sm font-medium text-neutral-800">{{ $option->name }}</span>
                                @if ($option->status === 'INACTIVE')
                                    <span class="text-[11px] text-neutral-500">Tidak Aktif</span>
                                @endif
                            </div>
                            <div data-coa-lookup-actions hidden class="flex shrink-0 flex-wrap gap-1 sm:justify-end">
                                <button type="button" data-coa-lookup-edit class="inline-flex min-h-9 items-center gap-1.5 rounded-md px-2.5 text-xs font-medium text-neutral-600 hover:bg-neutral-100 hover:text-neutral-900" aria-label="Edit {{ mb_strtolower($label) }} {{ $option->name }}">
                                    <x-icon name="edit" class="size-3.5" />Edit
                                </button>
                                <button type="button" data-coa-lookup-delete class="inline-flex min-h-9 items-center gap-1.5 rounded-md px-2.5 text-xs font-medium text-neutral-600 hover:bg-neutral-100 hover:text-neutral-900" aria-label="Hapus {{ mb_strtolower($label) }} {{ $option->name }}">
                                    <x-icon name="trash" class="size-3.5" />Hapus
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
                <p data-coa-lookup-empty @class(['mt-2 text-xs text-neutral-500', 'hidden' => $managedOptions->isNotEmpty()])>Belum ada pilihan.</p>
            </div>

            <div data-coa-lookup-editor hidden class="space-y-3 rounded-md border border-neutral-300 bg-white p-3" role="group" aria-label="Edit {{ $label }}">
                <label class="block text-xs font-medium text-neutral-700">
                    Nama {{ $label }}
                    <input data-coa-lookup-edit-input type="text" maxlength="255" autocomplete="off" class="mt-1.5 block h-10 w-full rounded-lg border border-neutral-200 bg-white px-3 text-sm text-neutral-900 focus:border-neutral-400 focus:outline-none focus:ring-2 focus:ring-neutral-200">
                </label>
                <div class="flex flex-wrap justify-end gap-2">
                    <button type="button" data-coa-lookup-edit-cancel class="min-h-9 rounded-md px-3 text-xs font-medium text-neutral-600 hover:bg-neutral-100">Batal</button>
                    <button type="button" data-coa-lookup-edit-save class="min-h-9 rounded-md border border-neutral-300 bg-white px-3 text-xs font-semibold text-neutral-800 hover:bg-neutral-50">Simpan</button>
                </div>
            </div>

            <div data-coa-lookup-delete-confirm hidden class="space-y-3 rounded-md border border-neutral-300 bg-white p-3" role="group" aria-label="Konfirmasi hapus {{ $label }}">
                <div>
                    <p data-coa-lookup-delete-title class="text-sm font-semibold text-neutral-900"></p>
                    <p data-coa-lookup-delete-message class="mt-1 text-xs leading-5 text-neutral-600"></p>
                </div>
                <div class="flex flex-wrap justify-end gap-2">
                    <button type="button" data-coa-lookup-delete-cancel class="min-h-9 rounded-md px-3 text-xs font-medium text-neutral-600 hover:bg-neutral-100">Batal</button>
                    <button type="button" data-coa-lookup-delete-confirm-button class="min-h-9 rounded-md border border-neutral-300 bg-white px-3 text-xs font-semibold text-neutral-800 hover:bg-neutral-50">Hapus</button>
                </div>
            </div>

            <details data-coa-lookup-add @if ($errors->has($newField)) open @endif class="rounded-md border border-neutral-200 bg-white px-3 py-2.5">
                <summary data-coa-lookup-add-summary class="cursor-pointer text-xs font-semibold text-neutral-700 marker:text-neutral-400">+ Tambah Baru</summary>
                <div class="mt-3 space-y-3">
                    <x-form.input :name="$newField" :label="'Nama '.$label.' Baru'" placeholder="Masukkan nama pilihan" maxlength="255" autocomplete="off" />
                    <div class="flex justify-end">
                        <x-button
                            type="submit"
                            variant="secondary"
                            :formaction="route('chart-of-accounts.lookups.store')"
                            formmethod="POST"
                            formnovalidate
                            name="lookup_type"
                            :value="$lookupType"
                            data-coa-lookup-submit
                            data-lookup-input="{{ $newField }}"
                            data-select-name="{{ $fieldName }}"
                        >Simpan {{ $label }}</x-button>
                    </div>
                </div>
            </details>

            <noscript>
                <div class="space-y-2 border-t border-neutral-200 pt-3">
                    <p class="text-xs text-neutral-500">Edit dan hapus tetap tersedia tanpa JavaScript. Form akan dimuat ulang dengan nilai utama dipertahankan.</p>
                    @foreach ($managedOptions as $option)
                        <details class="rounded-md border border-neutral-200 bg-white px-3 py-2">
                            <summary class="cursor-pointer text-sm font-medium text-neutral-800">{{ $option->name }}</summary>
                            <div class="mt-3 space-y-3">
                                <label class="block text-xs font-medium text-neutral-700">
                                    Nama {{ $label }}
                                    <input name="lookup_names[{{ $lookupType }}][{{ $option->id }}]" value="{{ old("lookup_names.{$lookupType}.{$option->id}", $option->name) }}" maxlength="255" required class="mt-1.5 block h-10 w-full rounded-lg border border-neutral-200 px-3 text-sm">
                                </label>
                                <div class="flex flex-wrap gap-2">
                                    <button type="submit" formaction="{{ route('chart-of-accounts.lookups.update', [$lookupType, $option->id]) }}" formmethod="POST" formnovalidate name="_method" value="PATCH" class="min-h-9 rounded-md border border-neutral-300 px-3 text-xs font-semibold">Simpan Edit</button>
                                    <details class="w-full rounded-md border border-neutral-200 p-2">
                                        <summary class="cursor-pointer text-xs font-medium text-neutral-600">Konfirmasi Hapus</summary>
                                        <p class="my-2 text-xs text-neutral-600">{{ $label }} '{{ $option->name }}' akan dihapus permanen jika belum digunakan.</p>
                                        <button type="submit" formaction="{{ route('chart-of-accounts.lookups.destroy', [$lookupType, $option->id]) }}" formmethod="POST" formnovalidate name="_method" value="DELETE" class="min-h-9 rounded-md border border-neutral-300 px-3 text-xs font-semibold">Hapus</button>
                                    </details>
                                </div>
                            </div>
                        </details>
                    @endforeach
                </div>
            </noscript>

            <template data-coa-lookup-row-template>
                <div data-coa-lookup-row class="flex flex-col gap-2 rounded-md border border-neutral-200 bg-white px-3 py-2 sm:flex-row sm:items-center sm:justify-between">
                    <div class="min-w-0"><span data-coa-lookup-row-name class="block break-words text-sm font-medium text-neutral-800"></span></div>
                    <div data-coa-lookup-actions class="flex shrink-0 flex-wrap gap-1 sm:justify-end">
                        <button type="button" data-coa-lookup-edit class="inline-flex min-h-9 items-center gap-1.5 rounded-md px-2.5 text-xs font-medium text-neutral-600 hover:bg-neutral-100 hover:text-neutral-900"><x-icon name="edit" class="size-3.5" />Edit</button>
                        <button type="button" data-coa-lookup-delete class="inline-flex min-h-9 items-center gap-1.5 rounded-md px-2.5 text-xs font-medium text-neutral-600 hover:bg-neutral-100 hover:text-neutral-900"><x-icon name="trash" class="size-3.5" />Hapus</button>
                    </div>
                </div>
            </template>
        </div>
    </details>
</div>
