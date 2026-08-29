@php
    $isEditing = isset($account);
@endphp

<form method="POST" action="{{ $isEditing ? route('chart-of-accounts.update', $account) : route('chart-of-accounts.store') }}" class="space-y-6" data-coa-form>
    @csrf

    <x-form.input
        name="number_code"
        label="Nomor Akun"
        :value="$account->number_code ?? null"
        placeholder="Contoh: 1001"
        inputmode="numeric"
        pattern="[0-9]*"
        maxlength="50"
        autocomplete="off"
        help="Diisi manual oleh Admin. Gunakan angka saja; nol di depan tetap disimpan."
        required
    />

    <div class="grid gap-5 sm:grid-cols-2">
        @include('chart-of-accounts.partials.lookup-field', [
            'lookupType' => 'description',
            'fieldName' => 'description_id',
            'newField' => 'new_description',
            'label' => 'Deskripsi',
            'options' => $descriptions,
            'selectedId' => $account->description_id ?? null,
        ])
        @include('chart-of-accounts.partials.lookup-field', [
            'lookupType' => 'account_type',
            'fieldName' => 'account_type_id',
            'newField' => 'new_account_type',
            'label' => 'Tipe Akun',
            'options' => $accountTypes,
            'selectedId' => $account->account_type_id ?? null,
        ])
        @include('chart-of-accounts.partials.lookup-field', [
            'lookupType' => 'financial_statement',
            'fieldName' => 'financial_statement_id',
            'newField' => 'new_financial_statement',
            'label' => 'Laporan Keuangan',
            'options' => $financialStatements,
            'selectedId' => $account->financial_statement_id ?? null,
        ])
    </div>

    @if ($isEditing)
        <div class="flex items-center justify-between gap-4 rounded-lg border border-neutral-200 px-4 py-3">
            <div>
                <p class="text-sm font-medium text-neutral-800">Status Akun</p>
                <p class="mt-0.5 text-xs text-neutral-500">Status diubah melalui aksi khusus pada daftar atau detail akun.</p>
            </div>
            <x-badge>{{ $account->status === 'ACTIVE' ? 'Aktif' : 'Tidak Aktif' }}</x-badge>
        </div>
    @endif

    <div class="flex flex-col-reverse gap-3 border-t border-neutral-200 pt-5 sm:flex-row sm:justify-end">
        <x-button variant="secondary" :href="$isEditing ? route('chart-of-accounts.show', $account) : route('chart-of-accounts.index')" data-crud-modal-cancel>Batal</x-button>
        <x-button type="submit" :name="$isEditing ? '_method' : null" :value="$isEditing ? 'PUT' : null">{{ $isEditing ? 'Simpan Perubahan' : 'Simpan' }}</x-button>
    </div>
</form>
