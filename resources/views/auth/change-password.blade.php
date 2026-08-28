<x-layouts.app title="Ubah Password">
    <div class="mx-auto max-w-xl space-y-6">
        <div>
            <a href="{{ route('dashboard') }}" class="mb-4 inline-flex items-center gap-2 text-sm text-neutral-500 hover:text-neutral-900">
                <x-icon name="arrow-left" class="size-4" />
                Dashboard
            </a>
            <h1 class="text-2xl font-semibold tracking-tight text-neutral-950">Ubah Password</h1>
            <p class="mt-1 text-sm text-neutral-500">Gunakan password yang kuat dan berbeda dari password saat ini.</p>
        </div>

        <x-flash-message />

        <x-card>
            <form method="POST" action="{{ route('account.password.update') }}" class="space-y-5">
                @csrf
                @method('PATCH')

                <x-form.password name="current_password" label="Password Saat Ini" autocomplete="current-password" autofocus />
                <x-form.password name="password" label="Password Baru" autocomplete="new-password" />
                <x-form.password name="password_confirmation" label="Konfirmasi Password Baru" autocomplete="new-password" />

                <div class="flex flex-col-reverse gap-2 border-t border-neutral-200 pt-5 sm:flex-row sm:justify-end">
                    @if (request()->boolean('modal'))
                        <x-button variant="secondary" data-crud-modal-cancel>Batal</x-button>
                    @else
                        <x-button variant="secondary" :href="route('dashboard')">Batal</x-button>
                    @endif
                    <x-button type="submit">Simpan Password</x-button>
                </div>
            </form>
        </x-card>
    </div>
</x-layouts.app>
