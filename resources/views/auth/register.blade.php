<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Registrasi Admin - Tambak Management</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-neutral-50 font-sans text-sm text-neutral-900">
    <main class="flex min-h-screen items-center justify-center px-4 py-12">
        <div class="w-full max-w-md">
            <div class="mb-7 flex flex-col items-center text-center">
                <span class="flex size-10 items-center justify-center rounded-xl bg-neutral-900 text-white">
                    <x-icon name="waves" class="size-5" />
                </span>
                <h1 class="mt-4 text-xl font-semibold tracking-tight text-neutral-950">Registrasi Admin</h1>
                <p class="mt-1 text-sm text-neutral-500">Buat akun Admin untuk mengelola operasional tambak.</p>
            </div>

            <section class="rounded-xl border border-neutral-200 bg-white p-6">
                @if ($errors->any())
                    <div class="mb-5 rounded-lg border border-neutral-300 bg-neutral-50 px-4 py-3 text-sm text-neutral-800" role="alert">
                        <ul class="space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('register.store') }}" class="space-y-5">
                    @csrf

                    <x-form.input name="name" label="Nama" required autofocus autocomplete="name" />
                    <x-form.input name="email" label="Email" type="email" required autocomplete="email" />
                    <x-form.password name="password" label="Password" autocomplete="new-password" minlength="8" />
                    <x-form.password name="password_confirmation" label="Konfirmasi Password" autocomplete="new-password" minlength="8" />

                    <x-button type="submit" class="w-full">Daftar</x-button>
                </form>
            </section>

            <p class="mt-5 text-center text-xs text-neutral-400">Akun baru selalu dibuat sebagai Admin aktif.</p>
        </div>
    </main>
</body>
</html>
