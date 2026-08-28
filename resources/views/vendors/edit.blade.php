<x-layouts.app title="Edit Vendor">
    <div class="mx-auto max-w-3xl space-y-6">
        <div>
            <a href="{{ route('vendors.show', $vendor) }}" class="mb-4 inline-flex items-center gap-2 text-sm text-neutral-500 hover:text-neutral-900"><x-icon name="arrow-left" class="size-4" />{{ $vendor->name }}</a>
            <x-page-header title="Edit Vendor" description="Perbarui informasi {{ $vendor->name }}." />
        </div>
        <x-flash-message />
        <x-card>@include('vendors.partials.form')</x-card>
    </div>
</x-layouts.app>
