<x-layouts.app title="Master Tambak">
    <div class="space-y-6">
        <x-page-header title="Master Tambak" description="Kelola area, tambak, dan petak lokasi budidaya.">
            @if (auth()->user()->canAccess('locations.manage'))
                <x-slot:actions>
                    <x-button :href="route('tambak.create')" data-crud-modal data-crud-modal-size="xl">
                        <x-icon name="plus" class="size-4" />
                        Tambah Lokasi
                    </x-button>
                </x-slot:actions>
            @endif
        </x-page-header>

        <x-flash-message />

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Ringkasan lokasi">
            <x-kpi-card label="Total Area" :value="number_format($summary['areas'], 0, ',', '.')" icon="map" />
            <x-kpi-card label="Total Tambak" :value="number_format($summary['tambak'], 0, ',', '.')" icon="building" />
            <x-kpi-card label="Total Petak" :value="number_format($summary['petak'], 0, ',', '.')" icon="waves" />
            <x-kpi-card label="Lokasi Aktif" :value="number_format($summary['active'], 0, ',', '.')" icon="check" />
        </section>

        @php
            $tambakFilterCount = collect([$filters['type'], $filters['status']])->filter()->count();
        @endphp
        <x-card>
            <form method="GET" action="{{ route('tambak.index') }}" class="flex flex-col gap-3 lg:flex-row lg:items-start">
                <div class="min-w-0 flex-1">
                    <label for="search" class="sr-only">Cari lokasi</label>
                    <div class="relative">
                        <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-neutral-400" />
                        <input id="search" name="search" type="search" value="{{ $filters['search'] }}" placeholder="Cari lokasi..." class="h-10 w-full rounded-lg border border-neutral-200 bg-white pl-9 pr-3 text-sm placeholder:text-neutral-400 hover:border-neutral-300">
                    </div>
                </div>
                <x-filters.panel id="tambak-filters" :active-count="$tambakFilterCount" class="w-full lg:w-auto lg:shrink-0">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-filters.select name="type" label="Tipe Lokasi" :options="$typeLabels" :value="$filters['type']" placeholder="Semua Tipe" />
                        <x-filters.select name="status" label="Status" :options="['ACTIVE' => 'Aktif', 'INACTIVE' => 'Tidak Aktif']" :value="$filters['status']" placeholder="Semua Status" />
                    </div>
                    <div class="mt-5 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                        @if ($filters['search'] !== '' || $filters['type'] || $filters['status'])
                            <x-button variant="secondary" :href="route('tambak.index')">Reset</x-button>
                        @endif
                        <x-button type="submit">Terapkan Filter</x-button>
                    </div>
                </x-filters.panel>
                <x-page-size id="tambak-per-page" :value="$locations->perPage()" />
            </form>
        </x-card>

        <div>
            <x-table-wrapper title="Daftar Lokasi" description="Hierarki area, tambak, dan petak yang terdaftar.">
                @if ($locations->isEmpty())
                    <x-empty-state title="Belum ada data lokasi" description="Tambahkan area, tambak, atau petak untuk mulai mengelola lokasi budidaya." icon="map">
                        @if (auth()->user()->canAccess('locations.manage'))
                            <x-button :href="route('tambak.create')" data-crud-modal data-crud-modal-size="xl">Tambah Lokasi</x-button>
                        @endif
                    </x-empty-state>
                @else
                    <table data-responsive-table="tambak" class="w-full min-w-[900px] text-left">
                        <thead>
                            <tr class="border-b border-neutral-200 bg-neutral-50/70 text-[11px] font-semibold uppercase tracking-wider text-neutral-500">
                                <th scope="col" class="px-5 py-3 sm:px-6">Kode</th>
                                <th scope="col" class="px-5 py-3">Nama Lokasi</th>
                                <th scope="col" class="px-5 py-3">Induk Lokasi</th>
                                <th scope="col" class="px-5 py-3">Tipe</th>
                                <th scope="col" class="px-5 py-3 text-right">Jumlah Anak</th>
                                <th scope="col" class="px-5 py-3">Status</th>
                                <th scope="col" class="px-5 py-3 pr-6 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-100">
                            @foreach ($locations as $location)
                                <tr class="transition-colors hover:bg-neutral-50/70">
                                    <td class="px-5 py-3.5 font-mono text-xs text-neutral-600 sm:px-6">{{ $location->code }}</td>
                                    <td class="px-5 py-3.5">
                                        <a href="{{ route('tambak.show', $location) }}" data-crud-modal data-crud-modal-size="lg" class="font-medium text-neutral-900 hover:underline">{{ $location->name }}</a>
                                        @if ($location->address)
                                            <p class="mt-0.5 max-w-[260px] truncate text-xs text-neutral-500">{{ $location->address }}</p>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3.5 text-neutral-600">{{ $location->parent?->name ?? '—' }}</td>
                                    <td class="px-5 py-3.5 text-center"><x-badge>{{ $typeLabels[$location->location_type] ?? 'Lainnya' }}</x-badge></td>
                                    <td class="px-5 py-3.5 text-right tabular-nums text-neutral-700">{{ number_format($location->children_count, 0, ',', '.') }}</td>
                                    <td class="px-5 py-3.5">
                                        <x-badge>{{ $location->status === 'ACTIVE' ? 'Aktif' : 'Tidak Aktif' }}</x-badge>
                                    </td>
                                    <td class="px-5 py-3 pr-6">
                                        <div class="flex justify-end gap-1">
                                            <a href="{{ route('tambak.show', $location) }}" data-crud-modal data-crud-modal-size="lg" class="flex size-9 items-center justify-center rounded-lg text-neutral-500 hover:bg-neutral-100 hover:text-neutral-900" aria-label="Detail {{ $location->name }}" title="Detail">
                                                <x-icon name="eye" class="size-4" />
                                            </a>
                                            @if (auth()->user()->canAccess('locations.manage'))
                                                <a href="{{ route('tambak.edit', $location) }}" data-crud-modal data-crud-modal-size="xl" class="flex size-9 items-center justify-center rounded-lg text-neutral-500 hover:bg-neutral-100 hover:text-neutral-900" aria-label="Edit {{ $location->name }}" title="Edit">
                                                    <x-icon name="edit" class="size-4" />
                                                </a>
                                                <form method="POST" action="{{ route('tambak.status', $location) }}" data-confirm="{{ $location->status === 'ACTIVE' ? 'Nonaktifkan lokasi ini?' : 'Aktifkan lokasi ini?' }}" data-confirm-title="{{ $location->status === 'ACTIVE' ? 'Nonaktifkan Lokasi' : 'Aktifkan Lokasi' }}" data-confirm-action="{{ $location->status === 'ACTIVE' ? 'Nonaktifkan' : 'Aktifkan' }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="flex size-9 items-center justify-center rounded-lg text-neutral-500 hover:bg-neutral-100 hover:text-neutral-900" aria-label="{{ $location->status === 'ACTIVE' ? 'Nonaktifkan' : 'Aktifkan' }} {{ $location->name }}" title="{{ $location->status === 'ACTIVE' ? 'Nonaktifkan' : 'Aktifkan' }}">
                                                        <x-icon name="power" class="size-4" />
                                                    </button>
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

            @if ($locations->hasPages())
                <div class="mt-4">{{ $locations->links() }}</div>
            @endif
        </div>
    </div>
</x-layouts.app>
