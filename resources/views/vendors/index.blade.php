<x-layouts.app title="Master Vendor">
    <div class="space-y-6">
        <x-page-header title="Master Vendor" description="Kelola Vendor bibit, pakan, obat, jasa, dan kebutuhan operasional tambak.">
            @if (auth()->user()->canAccess('vendors.manage'))
                <x-slot:actions>
                    <x-button :href="route('vendors.create')" data-crud-modal data-crud-modal-size="xl">
                        <x-icon name="plus" class="size-4" />
                        Tambah Vendor
                    </x-button>
                </x-slot:actions>
            @endif
        </x-page-header>

        <x-flash-message />

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Ringkasan Vendor">
            <x-kpi-card label="Total Vendor" :value="number_format($summary['total'], 0, ',', '.')" icon="truck" />
            <x-kpi-card label="Vendor Aktif" :value="number_format($summary['active'], 0, ',', '.')" icon="check" />
            <x-kpi-card label="Vendor Bibit" :value="number_format($summary['seed'], 0, ',', '.')" icon="seedling" />
            <x-kpi-card label="Vendor Terpakai" :value="number_format($summary['used'], 0, ',', '.')" icon="package" />
        </section>

        @php
            $vendorFilterCount = collect([$filters['type'], $filters['status']])->filter()->count();
        @endphp
        <x-card>
            <form method="GET" action="{{ route('vendors.index') }}" class="flex flex-col gap-3 lg:flex-row lg:items-start">
                <div class="min-w-0 flex-1">
                    <label for="search" class="sr-only">Cari Vendor</label>
                    <div class="relative">
                        <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-neutral-400" />
                        <input id="search" name="search" type="search" value="{{ $filters['search'] }}" placeholder="Cari Vendor..." class="h-10 w-full rounded-lg border border-neutral-200 bg-white pl-9 pr-3 text-sm placeholder:text-neutral-400 hover:border-neutral-300">
                    </div>
                </div>
                <x-filters.panel id="vendor-filters" :active-count="$vendorFilterCount" class="w-full lg:w-auto lg:shrink-0">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-filters.select name="type" label="Jenis Vendor" :options="$typeLabels" :value="$filters['type']" placeholder="Semua Jenis" />
                        <x-filters.select name="status" label="Status" :options="['ACTIVE' => 'Aktif', 'INACTIVE' => 'Tidak Aktif']" :value="$filters['status']" placeholder="Semua Status" />
                    </div>
                    <div class="mt-5 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                        @if ($filters['search'] !== '' || $filters['type'] || $filters['status'])
                            <x-button variant="secondary" :href="route('vendors.index')">Reset</x-button>
                        @endif
                        <x-button type="submit">Terapkan Filter</x-button>
                    </div>
                </x-filters.panel>
                <x-page-size id="vendors-per-page" :value="$vendors->perPage()" />
            </form>
        </x-card>

        <div>
            <x-table-wrapper title="Daftar Vendor" description="Vendor yang menyediakan bibit, pakan, obat, atau jasa untuk kegiatan operasional.">
                @if ($vendors->isEmpty())
                    <x-empty-state title="Belum ada data Vendor" description="Tambahkan Vendor untuk mulai menghubungkan kebutuhan operasional." icon="truck">
                        @if (auth()->user()->canAccess('vendors.manage'))
                            <x-button :href="route('vendors.create')" data-crud-modal data-crud-modal-size="xl">Tambah Vendor</x-button>
                        @endif
                    </x-empty-state>
                @else
                    <table data-responsive-table="vendors" class="w-full min-w-[960px] text-left">
                        <thead>
                            <tr class="border-b border-neutral-200 bg-neutral-50/70 text-[11px] font-semibold uppercase tracking-wider text-neutral-500">
                                <th scope="col" class="px-5 py-3 sm:px-6">Kode</th>
                                <th scope="col" class="px-5 py-3">Nama Vendor</th>
                                <th scope="col" class="px-5 py-3">Jenis</th>
                                <th scope="col" class="px-5 py-3">Kontak</th>
                                <th scope="col" class="px-5 py-3">Digunakan Untuk</th>
                                <th scope="col" class="px-5 py-3">Status</th>
                                <th scope="col" class="px-5 py-3 pr-6 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-100">
                            @foreach ($vendors as $vendor)
                                @php
                                    $whatsAppUrl = \App\Support\WhatsApp::url($vendor->phone);
                                @endphp
                                <tr class="transition-colors hover:bg-neutral-50/70">
                                    <td class="px-5 py-3.5 font-mono text-xs text-neutral-600 sm:px-6">{{ $vendor->code }}</td>
                                    <td class="px-5 py-3.5">
                                        <a href="{{ route('vendors.show', $vendor) }}" data-crud-modal data-crud-modal-size="lg" class="font-medium text-neutral-900 hover:underline">{{ $vendor->name }}</a>
                                        @if ($vendor->address)
                                            <p class="mt-0.5 max-w-[220px] truncate text-xs text-neutral-500">{{ $vendor->address }}</p>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3.5 text-center"><x-badge>{{ $typeLabels[$vendor->vendor_type] ?? 'Lainnya' }}</x-badge></td>
                                    <td class="px-5 py-3.5 text-center">
                                        @if ($vendor->phone || $vendor->email)
                                            <div class="space-y-0.5 text-center text-xs text-neutral-600">
                                                @if ($vendor->phone)
                                                    <p class="flex flex-wrap items-center justify-center gap-1.5" data-vendor-phone>
                                                        @if ($whatsAppUrl)
                                                            <a href="{{ $whatsAppUrl }}" target="_blank" rel="noopener noreferrer" class="inline-flex size-7 shrink-0 items-center justify-center rounded-md text-emerald-700 transition-colors hover:bg-emerald-50 hover:text-emerald-800" aria-label="Chat WhatsApp dengan {{ $vendor->name }}" title="Chat WhatsApp">
                                                                <x-icon name="whatsapp" class="size-4" />
                                                            </a>
                                                        @endif
                                                        <span>{{ $vendor->phone }}</span>
                                                    </p>
                                                @endif
                                                @if ($vendor->email)<p class="max-w-[210px] truncate">{{ $vendor->email }}</p>@endif
                                            </div>
                                        @else
                                            <span class="text-neutral-400">Belum tersedia</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3.5 text-xs text-neutral-600">
                                        @if ($vendor->commodity_batches_count || $vendor->default_feed_items_count)
                                            @if ($vendor->commodity_batches_count)<span>{{ number_format($vendor->commodity_batches_count, 0, ',', '.') }} Batch</span>@endif
                                            @if ($vendor->commodity_batches_count && $vendor->default_feed_items_count)<span aria-hidden="true"> · </span>@endif
                                            @if ($vendor->default_feed_items_count)<span>{{ number_format($vendor->default_feed_items_count, 0, ',', '.') }} kebutuhan</span>@endif
                                        @else
                                            <span class="text-neutral-400">Belum digunakan</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-3.5"><x-badge>{{ $vendor->status === 'ACTIVE' ? 'Aktif' : 'Tidak Aktif' }}</x-badge></td>
                                    <td class="px-5 py-3 pr-6">
                                        <div class="flex justify-end gap-1">
                                            <a href="{{ route('vendors.show', $vendor) }}" data-crud-modal data-crud-modal-size="lg" class="flex size-9 items-center justify-center rounded-lg text-neutral-500 hover:bg-neutral-100 hover:text-neutral-900" aria-label="Detail {{ $vendor->name }}" title="Detail"><x-icon name="eye" class="size-4" /></a>
                                            @if (auth()->user()->canAccess('vendors.manage'))
                                                <a href="{{ route('vendors.edit', $vendor) }}" data-crud-modal data-crud-modal-size="xl" class="flex size-9 items-center justify-center rounded-lg text-neutral-500 hover:bg-neutral-100 hover:text-neutral-900" aria-label="Edit {{ $vendor->name }}" title="Edit"><x-icon name="edit" class="size-4" /></a>
                                                <form method="POST" action="{{ route('vendors.status', $vendor) }}" data-confirm="{{ $vendor->status === 'ACTIVE' ? 'Nonaktifkan Vendor ini?' : 'Aktifkan Vendor ini?' }}" data-confirm-title="{{ $vendor->status === 'ACTIVE' ? 'Nonaktifkan Vendor' : 'Aktifkan Vendor' }}" data-confirm-action="{{ $vendor->status === 'ACTIVE' ? 'Nonaktifkan' : 'Aktifkan' }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="flex size-9 items-center justify-center rounded-lg text-neutral-500 hover:bg-neutral-100 hover:text-neutral-900" aria-label="{{ $vendor->status === 'ACTIVE' ? 'Nonaktifkan' : 'Aktifkan' }} {{ $vendor->name }}" title="{{ $vendor->status === 'ACTIVE' ? 'Nonaktifkan' : 'Aktifkan' }}"><x-icon name="power" class="size-4" /></button>
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

            @if ($vendors->hasPages())
                <div class="mt-4">{{ $vendors->links() }}</div>
            @endif
        </div>
    </div>
</x-layouts.app>
