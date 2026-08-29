<x-layouts.app title="Chart of Accounts">
    <div class="space-y-6">
        <x-page-header title="Chart of Accounts" description="Kelola master akun untuk kebutuhan akuntansi.">
            @if (auth()->user()->canAccess('chart-of-accounts.manage'))
                <x-slot:actions>
                    <x-button :href="route('chart-of-accounts.create')" data-crud-modal data-crud-modal-size="xl">
                        <x-icon name="plus" class="size-4" />
                        Tambah
                    </x-button>
                </x-slot:actions>
            @endif
        </x-page-header>

        <x-flash-message />

        <section class="grid gap-4 sm:grid-cols-3" aria-label="Ringkasan Chart of Accounts">
            <x-kpi-card label="Total Akun" :value="number_format($summary['total'], 0, ',', '.')" icon="coins" />
            <x-kpi-card label="Akun Aktif" :value="number_format($summary['active'], 0, ',', '.')" icon="check" />
            <x-kpi-card label="Akun Tidak Aktif" :value="number_format($summary['inactive'], 0, ',', '.')" icon="power" />
        </section>

        @php($accountFilterCount = $filters['status'] ? 1 : 0)
        <x-card>
            <form method="GET" action="{{ route('chart-of-accounts.index') }}" class="flex flex-col gap-3 lg:flex-row lg:items-start">
                <div class="min-w-0 flex-1">
                    <label for="search" class="sr-only">Cari Chart of Accounts</label>
                    <div class="relative">
                        <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-neutral-400" />
                        <input id="search" name="search" type="search" value="{{ $filters['search'] }}" placeholder="Cari nomor, deskripsi, tipe, atau laporan..." class="h-10 w-full rounded-lg border border-neutral-200 bg-white pl-9 pr-3 text-sm placeholder:text-neutral-400 hover:border-neutral-300">
                    </div>
                </div>
                <x-filters.panel id="chart-of-accounts-filters" :active-count="$accountFilterCount" class="w-full lg:w-auto lg:shrink-0">
                    <x-filters.select name="status" label="Status" :options="['ACTIVE' => 'Aktif', 'INACTIVE' => 'Tidak Aktif']" :value="$filters['status']" placeholder="Semua Status" />
                    <div class="mt-5 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                        @if ($filters['search'] !== '' || $filters['status'])
                            <x-button variant="secondary" :href="route('chart-of-accounts.index')">Reset</x-button>
                        @endif
                        <x-button type="submit">Terapkan Filter</x-button>
                    </div>
                </x-filters.panel>
                <x-page-size id="chart-of-accounts-per-page" :value="$accounts->perPage()" />
            </form>
        </x-card>

        <div>
            <x-table-wrapper title="Daftar Chart of Accounts" description="Master akun yang tersedia untuk konfigurasi akuntansi berikutnya.">
                @if ($accounts->isEmpty())
                    <x-empty-state title="Belum ada akun" description="Tambahkan akun pertama untuk menyiapkan master akuntansi." icon="coins">
                        @if (auth()->user()->canAccess('chart-of-accounts.manage'))
                            <x-button :href="route('chart-of-accounts.create')" data-crud-modal data-crud-modal-size="xl">Tambah</x-button>
                        @endif
                    </x-empty-state>
                @else
                    <table data-responsive-table="chart-of-accounts" class="w-full min-w-[980px] text-left">
                        <thead>
                            <tr class="border-b border-neutral-200 bg-neutral-50/70 text-[11px] font-semibold uppercase tracking-wider text-neutral-500">
                                <th scope="col" class="px-5 py-3 text-center sm:px-6">Nomor Akun</th>
                                <th scope="col" class="px-5 py-3 text-center">Deskripsi</th>
                                <th scope="col" class="px-5 py-3 text-center">Tipe Akun</th>
                                <th scope="col" class="px-5 py-3 text-center">Laporan Keuangan</th>
                                <th scope="col" class="px-5 py-3 text-center">Status</th>
                                <th scope="col" class="px-5 py-3 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-100">
                            @foreach ($accounts as $account)
                                <tr class="transition-colors hover:bg-neutral-50/70">
                                    <td class="px-5 py-3.5 text-center align-middle! font-mono text-sm font-medium text-neutral-900 sm:px-6">
                                        <a href="{{ route('chart-of-accounts.show', $account) }}" data-crud-modal data-crud-modal-size="lg" class="hover:underline">{{ $account->number_code }}</a>
                                    </td>
                                    <td class="px-5 py-3.5 text-center align-middle! text-neutral-800">{{ $account->description->name }}</td>
                                    <td class="px-5 py-3.5 text-center align-middle! text-neutral-700">{{ $account->accountType->name }}</td>
                                    <td class="px-5 py-3.5 text-center align-middle! text-neutral-700">{{ $account->financialStatement->name }}</td>
                                    <td class="px-5 py-3.5 text-center align-middle!"><x-badge>{{ $account->status === 'ACTIVE' ? 'Aktif' : 'Tidak Aktif' }}</x-badge></td>
                                    <td class="px-5 py-3 text-center align-middle!">
                                        <div class="flex justify-center gap-1">
                                            <a href="{{ route('chart-of-accounts.show', $account) }}" data-crud-modal data-crud-modal-size="lg" class="flex size-9 items-center justify-center rounded-lg text-neutral-500 hover:bg-neutral-100 hover:text-neutral-900" aria-label="Detail akun {{ $account->number_code }}" title="Detail"><x-icon name="eye" class="size-4" /></a>
                                            @if (auth()->user()->canAccess('chart-of-accounts.manage'))
                                                <a href="{{ route('chart-of-accounts.edit', $account) }}" data-crud-modal data-crud-modal-size="xl" class="flex size-9 items-center justify-center rounded-lg text-neutral-500 hover:bg-neutral-100 hover:text-neutral-900" aria-label="Edit akun {{ $account->number_code }}" title="Edit"><x-icon name="edit" class="size-4" /></a>
                                                <form method="POST" action="{{ route('chart-of-accounts.status', $account) }}" data-confirm="{{ $account->status === 'ACTIVE' ? 'Nonaktifkan akun ini?' : 'Aktifkan akun ini?' }}" data-confirm-title="{{ $account->status === 'ACTIVE' ? 'Nonaktifkan Akun' : 'Aktifkan Akun' }}" data-confirm-action="{{ $account->status === 'ACTIVE' ? 'Nonaktifkan' : 'Aktifkan' }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="flex size-9 items-center justify-center rounded-lg text-neutral-500 hover:bg-neutral-100 hover:text-neutral-900" aria-label="{{ $account->status === 'ACTIVE' ? 'Nonaktifkan' : 'Aktifkan' }} akun {{ $account->number_code }}" title="{{ $account->status === 'ACTIVE' ? 'Nonaktifkan' : 'Aktifkan' }}"><x-icon name="power" class="size-4" /></button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </x-table-wrapper>

            @if ($accounts->hasPages())
                <div class="mt-4">{{ $accounts->links() }}</div>
            @endif
        </div>
    </div>
</x-layouts.app>
