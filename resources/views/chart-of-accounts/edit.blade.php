<x-layouts.app title="Edit Chart of Accounts">
    <div class="mx-auto max-w-3xl space-y-6">
        <div>
            <a href="{{ route('chart-of-accounts.show', $account) }}" class="mb-4 inline-flex items-center gap-2 text-sm text-neutral-500 hover:text-neutral-900"><x-icon name="arrow-left" class="size-4" />Akun {{ $account->number_code }}</a>
            <x-page-header title="Edit Chart of Accounts" description="Perbarui akun {{ $account->number_code }}." />
        </div>
        <x-flash-message />
        <x-card>@include('chart-of-accounts.partials.form')</x-card>
    </div>
</x-layouts.app>
