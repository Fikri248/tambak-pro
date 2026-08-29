<x-layouts.app title="Tambah Chart of Accounts">
    <div class="mx-auto max-w-3xl space-y-6">
        <div>
            <a href="{{ route('chart-of-accounts.index') }}" class="mb-4 inline-flex items-center gap-2 text-sm text-neutral-500 hover:text-neutral-900"><x-icon name="arrow-left" class="size-4" />Chart of Accounts</a>
            <x-page-header title="Tambah Chart of Accounts" description="Tambahkan akun dengan nomor yang ditentukan Admin." />
        </div>
        <x-flash-message />
        <x-card>@include('chart-of-accounts.partials.form')</x-card>
    </div>
</x-layouts.app>
