@php
    $currentUser = auth()->user()->loadMissing('role');
@endphp

<aside
    id="app-sidebar"
    data-sidebar
    class="fixed inset-y-0 left-0 z-50 flex w-60 -translate-x-full flex-col border-r border-neutral-200 bg-white transition-transform duration-200 ease-out lg:translate-x-0"
    aria-label="Navigasi utama"
>
    <div class="flex h-16 shrink-0 items-center justify-between border-b border-neutral-200 px-4">
        <a href="{{ route('dashboard') }}" class="flex min-w-0 items-center gap-2.5 rounded-md focus-visible:outline-offset-4">
            <span class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-neutral-900 text-white">
                <x-icon name="waves" class="size-4.5" />
            </span>
            <span data-sidebar-label class="truncate text-sm font-semibold tracking-tight text-neutral-950">Tambak Management</span>
        </a>

        <button data-sidebar-close type="button" class="flex size-9 items-center justify-center rounded-lg text-neutral-500 hover:bg-neutral-100 hover:text-neutral-900 lg:hidden" aria-label="Tutup navigasi">
            <x-icon name="close" class="size-5" />
        </button>
    </div>

    <nav class="flex-1 overflow-y-auto px-3 py-5">
        <div class="space-y-6">
            @foreach (config('navigation.sidebar', []) as $section)
                @continue(isset($section['ability']) && ! $currentUser->canAccess($section['ability']))

                <section aria-labelledby="sidebar-section-{{ $loop->index }}">
                    @if (isset($section['label']))
                        <h2 data-sidebar-label id="sidebar-section-{{ $loop->index }}" class="mb-2 px-3 text-[11px] font-semibold tracking-[0.12em] text-neutral-400">
                            {{ $section['label'] }}
                        </h2>
                    @endif

                    <ul class="space-y-1">
                        @foreach ($section['items'] as $item)
                            @continue(isset($item['ability']) && ! $currentUser->canAccess($item['ability']))
                            @php
                                $routeName = $item['route'] ?? null;
                                $available = $routeName && \Illuminate\Support\Facades\Route::has($routeName);
                                $active = $available && request()->routeIs($item['active'] ?? $routeName);
                                $itemClasses = $active
                                    ? 'bg-neutral-100 font-medium text-neutral-950'
                                    : ($available
                                        ? 'text-neutral-600 hover:bg-neutral-50 hover:text-neutral-950'
                                        : 'cursor-not-allowed text-neutral-400');
                            @endphp

                            <li>
                                @if ($available)
                                    <a href="{{ route($routeName) }}" aria-label="{{ $item['label'] }}" title="{{ $item['label'] }}" @class(['flex min-h-10 items-center gap-3 rounded-lg px-3 py-2 text-sm transition-colors', $itemClasses]) @if ($active) aria-current="page" @endif>
                                        <x-icon :name="$item['icon']" class="size-[18px] shrink-0" />
                                        <span data-sidebar-label>{{ $item['label'] }}</span>
                                    </a>
                                @else
                                    <span aria-disabled="true" title="Modul belum tersedia" @class(['flex min-h-10 items-center gap-3 rounded-lg px-3 py-2 text-sm', $itemClasses])>
                                        <x-icon :name="$item['icon']" class="size-[18px] shrink-0" />
                                        <span data-sidebar-label class="min-w-0 flex-1 truncate">{{ $item['label'] }}</span>
                                        <span data-sidebar-label class="text-[10px] font-medium uppercase tracking-wide text-neutral-400">Segera</span>
                                    </span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endforeach
        </div>
    </nav>

    <div class="border-t border-neutral-200 p-3">
        <button data-sidebar-collapse type="button" class="hidden min-h-10 w-full items-center gap-3 rounded-lg px-3 py-2 text-sm text-neutral-600 transition-colors hover:bg-neutral-100 hover:text-neutral-950 lg:flex" aria-controls="app-sidebar" aria-expanded="true" aria-label="Sembunyikan sidebar" title="Sembunyikan sidebar">
            <x-icon name="chevron-left" class="size-[18px] shrink-0 transition-transform duration-200 ease-out" data-sidebar-collapse-icon />
            <span data-sidebar-label>Sembunyikan sidebar</span>
        </button>
    </div>
</aside>
