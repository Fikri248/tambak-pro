<dialog
    data-crud-modal-shell
    data-size="lg"
    class="m-auto w-[min(64rem,calc(100vw-2rem))] max-w-none overflow-hidden rounded-xl border border-neutral-200 bg-white p-0 text-neutral-900 shadow-2xl shadow-neutral-950/20 backdrop:bg-neutral-950/45"
    aria-labelledby="crud-modal-title"
>
    <div class="flex max-h-[calc(100dvh-2rem)] min-h-0 flex-col">
        <header class="flex shrink-0 items-center justify-between gap-4 border-b border-neutral-200 px-5 py-4 sm:px-6">
            <h2 id="crud-modal-title" data-crud-modal-title class="min-w-0 truncate text-base font-semibold text-neutral-950">
                Detail
            </h2>
            <button
                data-crud-modal-close
                type="button"
                class="flex size-9 shrink-0 items-center justify-center rounded-lg text-neutral-500 transition-colors hover:bg-neutral-100 hover:text-neutral-900 focus:outline-none focus:ring-2 focus:ring-neutral-300"
                aria-label="Tutup modal"
            >
                <x-icon name="close" class="size-4" />
            </button>
        </header>

        <div data-crud-modal-body class="min-h-0 flex-1 overflow-y-auto overscroll-contain p-5 sm:p-6"></div>
    </div>
</dialog>
