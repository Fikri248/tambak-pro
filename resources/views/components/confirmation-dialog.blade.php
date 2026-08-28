<dialog
    data-confirmation-dialog
    class="m-auto w-[min(28rem,calc(100vw-2rem))] rounded-xl border border-neutral-200 bg-white p-0 text-neutral-900 shadow-2xl shadow-neutral-950/20 backdrop:bg-neutral-950/45"
    aria-labelledby="confirmation-dialog-title"
    aria-describedby="confirmation-dialog-message confirmation-dialog-description"
>
    <div class="p-5 sm:p-6">
        <div class="flex items-start gap-3">
            <div data-confirmation-dialog-icon class="flex size-9 shrink-0 items-center justify-center rounded-full bg-neutral-100 text-neutral-700">
                <x-icon name="warning" class="size-4" />
            </div>
            <div class="min-w-0">
                <h2 id="confirmation-dialog-title" data-confirmation-dialog-title class="text-base font-semibold text-neutral-950">Konfirmasi Tindakan</h2>
                <p id="confirmation-dialog-message" data-confirmation-dialog-message class="mt-1.5 text-sm leading-6 text-neutral-700"></p>
                <p id="confirmation-dialog-description" data-confirmation-dialog-description class="mt-2 hidden text-xs leading-5 text-neutral-500"></p>
            </div>
        </div>

        <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
            <button data-confirmation-dialog-cancel type="button" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-neutral-200 bg-white px-4 py-2 text-sm font-medium text-neutral-700 transition-colors hover:border-neutral-300 hover:bg-neutral-50 hover:text-neutral-950">
                Batal
            </button>
            <button data-confirmation-dialog-confirm type="button" class="inline-flex min-h-10 items-center justify-center rounded-lg bg-neutral-900 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-neutral-800">
                Lanjutkan
            </button>
        </div>
    </div>
</dialog>
