<div class="space-y-2" data-coa-lookup-field="{{ $lookupType }}">
    <x-form.select :name="$fieldName" :label="$label" required>
        <option value="">Pilih {{ $label }}</option>
        @foreach ($options as $option)
            <option value="{{ $option->id }}" @selected((string) old($fieldName, $selectedId) === (string) $option->id)>
                {{ $option->name }}{{ $option->status === 'INACTIVE' ? ' (Tidak Aktif)' : '' }}
            </option>
        @endforeach
    </x-form.select>

    <details data-coa-lookup-panel @if ($errors->has($newField)) open @endif class="rounded-lg border border-dashed border-neutral-300 bg-neutral-50/70 px-3 py-2.5">
        <summary class="cursor-pointer text-xs font-semibold text-neutral-700 marker:text-neutral-400">+ Tambah Baru</summary>
        <div class="mt-3 space-y-3">
            <x-form.input :name="$newField" :label="'Nama '.$label.' Baru'" placeholder="Masukkan nama pilihan" maxlength="255" autocomplete="off" />
            <div data-coa-lookup-feedback class="hidden rounded-md border border-neutral-200 bg-white px-3 py-2 text-xs text-neutral-700" role="status" aria-live="polite"></div>
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
</div>
