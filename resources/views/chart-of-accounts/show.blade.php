<x-layouts.app :title="'Detail Chart of Accounts · '.$account->number_code">
    <div class="space-y-6">
        <div>
            <a href="{{ route('chart-of-accounts.index') }}" class="mb-4 inline-flex items-center gap-2 text-sm text-neutral-500 hover:text-neutral-900"><x-icon name="arrow-left" class="size-4" />Chart of Accounts</a>
            <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                <div>
                    <div class="flex flex-wrap items-center gap-3">
                        <h1 class="font-mono text-2xl font-semibold tracking-tight text-neutral-950 sm:text-[26px]">{{ $account->number_code }}</h1>
                        <x-badge>{{ $account->status === 'ACTIVE' ? 'Aktif' : 'Tidak Aktif' }}</x-badge>
                    </div>
                    <p class="mt-1 text-sm text-neutral-500">{{ $account->description->name }}</p>
                </div>
                @if (auth()->user()->canAccess('chart-of-accounts.manage'))
                    <div class="flex flex-wrap gap-2">
                        <x-button variant="secondary" :href="route('chart-of-accounts.edit', $account)" data-crud-modal data-crud-modal-size="xl"><x-icon name="edit" class="size-4" />Edit</x-button>
                        <form method="POST" action="{{ route('chart-of-accounts.status', $account) }}" data-confirm="{{ $account->status === 'ACTIVE' ? 'Nonaktifkan akun ini?' : 'Aktifkan akun ini?' }}" data-confirm-title="{{ $account->status === 'ACTIVE' ? 'Nonaktifkan Akun' : 'Aktifkan Akun' }}" data-confirm-action="{{ $account->status === 'ACTIVE' ? 'Nonaktifkan' : 'Aktifkan' }}">
                            @csrf
                            @method('PATCH')
                            <x-button type="submit"><x-icon name="power" class="size-4" />{{ $account->status === 'ACTIVE' ? 'Nonaktifkan' : 'Aktifkan' }}</x-button>
                        </form>
                    </div>
                @endif
            </div>
        </div>

        <x-flash-message />

        <x-card>
            <h2 class="text-base font-semibold text-neutral-950">Informasi Akun</h2>
            <dl class="mt-5 grid gap-x-8 gap-y-5 sm:grid-cols-2">
                <div><dt class="text-xs text-neutral-500">Nomor Akun</dt><dd class="mt-1 font-mono text-sm font-medium text-neutral-900">{{ $account->number_code }}</dd></div>
                <div><dt class="text-xs text-neutral-500">Deskripsi</dt><dd class="mt-1 text-sm font-medium text-neutral-900">{{ $account->description->name }}</dd></div>
                <div><dt class="text-xs text-neutral-500">Tipe Akun</dt><dd class="mt-1 text-sm font-medium text-neutral-900">{{ $account->accountType->name }}</dd></div>
                <div><dt class="text-xs text-neutral-500">Laporan Keuangan</dt><dd class="mt-1 text-sm font-medium text-neutral-900">{{ $account->financialStatement->name }}</dd></div>
                <div><dt class="text-xs text-neutral-500">Status</dt><dd class="mt-1 text-sm font-medium text-neutral-900">{{ $account->status === 'ACTIVE' ? 'Aktif' : 'Tidak Aktif' }}</dd></div>
            </dl>
        </x-card>
    </div>
</x-layouts.app>
