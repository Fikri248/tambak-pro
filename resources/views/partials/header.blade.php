@php
    $navbarUser = auth()->user()->loadMissing('role');
    $navbarInitial = mb_strtoupper(mb_substr(trim($navbarUser->name), 0, 1));
@endphp

<header class="sticky top-0 z-30 flex h-16 min-w-0 items-center justify-between gap-3 border-b border-neutral-200 bg-white px-4 sm:px-6 lg:px-8">
    <div class="flex min-w-0 flex-1 items-center gap-3">
        <button data-sidebar-open type="button" class="flex size-9 shrink-0 items-center justify-center rounded-lg text-neutral-600 hover:bg-neutral-100 hover:text-neutral-950 lg:hidden" aria-label="Buka navigasi" aria-controls="app-sidebar" aria-expanded="false">
            <x-icon name="menu" class="size-5" />
        </button>
        <div class="min-w-0">
            <p class="truncate text-sm font-medium text-neutral-900">{{ $pageTitle }}</p>
            <p class="hidden text-xs text-neutral-500 sm:block">Workspace operasional</p>
        </div>
    </div>

    <div data-account-menu class="relative shrink-0">
        <button
            id="account-menu-trigger"
            data-account-menu-trigger
            type="button"
            class="flex h-10 max-w-48 items-center gap-2 rounded-lg border border-transparent px-1.5 text-left text-neutral-700 transition-colors hover:border-neutral-200 hover:bg-neutral-50 hover:text-neutral-950 sm:max-w-64 sm:px-2.5"
            aria-expanded="false"
            aria-controls="account-menu"
            aria-haspopup="menu"
            title="Akun {{ $navbarUser->name }}"
        >
            <span class="flex size-8 shrink-0 items-center justify-center rounded-lg border border-neutral-200 bg-neutral-100 text-xs font-semibold text-neutral-700" aria-hidden="true">
                {{ $navbarInitial }}
            </span>
            <span class="hidden min-w-0 truncate text-sm font-medium sm:block">{{ $navbarUser->name }}</span>
            <x-icon name="chevron-down" data-account-menu-chevron class="size-4 shrink-0 transition-transform duration-200 ease-out" />
            <span class="sr-only">Buka menu akun {{ $navbarUser->name }}</span>
        </button>

        <div
            id="account-menu"
            data-account-menu-panel
            role="menu"
            aria-labelledby="account-menu-trigger"
            aria-hidden="true"
            class="pointer-events-none invisible absolute right-0 top-full z-50 mt-2 w-56 origin-top-right -translate-y-1 scale-[.98] rounded-xl border border-neutral-200 bg-white opacity-0 shadow-lg shadow-neutral-950/10 transition duration-200 ease-out"
        >
            <div class="border-b border-neutral-200 px-4 py-3">
                <p class="truncate text-sm font-semibold text-neutral-950" title="{{ $navbarUser->name }}">{{ $navbarUser->name }}</p>
                <p class="mt-0.5 text-xs text-neutral-500">{{ $navbarUser->role->name }}</p>
            </div>
            <div class="p-1.5">
                <a
                    href="{{ route('account.password.edit') }}"
                    data-crud-modal
                    data-crud-modal-size="sm"
                    data-crud-modal-return-focus="#account-menu-trigger"
                    data-account-menu-action
                    role="menuitem"
                    class="flex min-h-10 items-center gap-3 rounded-lg px-3 py-2 text-sm text-neutral-700 transition-colors hover:bg-neutral-100 hover:text-neutral-950"
                >
                    <x-icon name="key" class="size-[18px] shrink-0" />
                    Ubah Password
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" data-account-menu-action role="menuitem" class="flex min-h-10 w-full items-center gap-3 rounded-lg px-3 py-2 text-left text-sm text-neutral-700 transition-colors hover:bg-neutral-100 hover:text-neutral-950">
                        <x-icon name="logout" class="size-[18px] shrink-0" />
                        Logout
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>
