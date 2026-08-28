<x-layouts.app title="Tambah Vendor">
    <div class="mx-auto max-w-3xl space-y-6">
        <div>
            <a href="{{ route('vendors.index') }}" class="mb-4 inline-flex items-center gap-2 text-sm text-neutral-500 hover:text-neutral-900"><x-icon name="arrow-left" class="size-4" />Master Vendor</a>
            <x-page-header title="Tambah Vendor" description="Tambahkan Vendor bibit, pakan, obat, atau jasa baru." />
        </div>
        <x-card>@include('vendors.partials.form')</x-card>
    </div>
</x-layouts.app>
