<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Masuk - Tambak Management</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-neutral-50 font-sans text-sm text-neutral-900">
    <main class="flex min-h-screen items-center justify-center px-4 py-12">
        <div class="w-full max-w-sm">
            <div class="mb-7 flex flex-col items-center text-center">
                <span class="flex size-10 items-center justify-center rounded-xl bg-neutral-900 text-white">
                    <x-icon name="waves" class="size-5" />
                </span>
                <h1 class="mt-4 text-xl font-semibold tracking-tight text-neutral-950">Tambak Management</h1>
                <p class="mt-1 text-sm text-neutral-500">Masuk untuk mengelola operasional tambak.</p>
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

                <form method="POST" action="{{ route('login.store') }}" class="space-y-5">
                    @csrf

                    <div>
                        <label for="email" class="mb-1.5 block text-sm font-medium text-neutral-800">Email</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="email" class="block h-10 w-full rounded-lg border border-neutral-300 bg-white px-3 text-sm text-neutral-900 placeholder:text-neutral-400 hover:border-neutral-400">
                    </div>

                    <div>
                        <label for="password" class="mb-1.5 block text-sm font-medium text-neutral-800">Password</label>
                        <div class="relative">
                            <input id="password" name="password" type="password" required autocomplete="current-password" data-password-input class="block h-10 w-full rounded-lg border border-neutral-300 bg-white py-2 pr-11 pl-3 text-sm text-neutral-900 hover:border-neutral-400">
                            <button type="button" data-password-toggle aria-label="Tampilkan password" aria-pressed="false" class="absolute inset-y-0 right-0 flex w-10 items-center justify-center rounded-r-lg text-neutral-500 hover:text-neutral-900 focus-visible:outline-2 focus-visible:outline-offset-[-2px] focus-visible:outline-neutral-900">
                                <x-icon name="eye" data-password-show-icon class="size-4" aria-hidden="true" />
                                <x-icon name="eye-off" data-password-hide-icon class="hidden size-4" aria-hidden="true" />
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <input id="remember" name="remember" type="checkbox" value="1" @checked(old('remember')) class="size-4 rounded border-neutral-300 text-neutral-900 accent-neutral-900">
                        <label for="remember" class="text-sm text-neutral-600">Ingat saya</label>
                    </div>

                    <x-button type="submit" class="w-full">Masuk</x-button>
                </form>
            </section>

            <p class="mt-5 text-center text-xs text-neutral-400">Akses khusus pengguna terdaftar.</p>
        </div>
    </main>
</body>
</html>
