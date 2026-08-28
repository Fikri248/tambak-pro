<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>403 - Akses Ditolak</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-neutral-50 font-sans text-neutral-900">
    <main class="flex min-h-screen items-center justify-center px-4 text-center">
        <div class="max-w-md">
            <p class="text-sm font-semibold text-neutral-500">403</p>
            <h1 class="mt-2 text-2xl font-semibold tracking-tight text-neutral-950">Akses Ditolak</h1>
            <p class="mt-2 text-sm leading-6 text-neutral-500">Anda tidak memiliki akses ke halaman ini.</p>
            <x-button :href="auth()->check() ? route('dashboard') : route('login')" variant="secondary" class="mt-6">
                Kembali ke Dashboard
            </x-button>
        </div>
    </main>
</body>
</html>
