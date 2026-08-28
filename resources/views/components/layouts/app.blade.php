@props(['title' => 'Tambak Management'])

@if (request()->boolean('modal'))
    <div data-crud-modal-fragment data-crud-modal-title="{{ $title }}">
        {{ $slot }}
    </div>
@else
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }} - Tambak Management</title>
    <script>
        try {
            if (localStorage.getItem('sidebar-collapsed') === 'true') {
                document.documentElement.dataset.sidebarCollapsed = 'true';
            }
        } catch (_) {}
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-neutral-50 font-sans text-sm text-neutral-900">
    <div data-sidebar-backdrop data-sidebar-close class="fixed inset-0 z-40 hidden bg-neutral-950/40 lg:hidden"></div>

    @include('partials.sidebar')

    <div data-app-shell class="min-h-screen min-w-0 lg:pl-60">
        @include('partials.header', ['pageTitle' => $title])

        <main class="min-w-0 px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
            <div class="mx-auto min-w-0 max-w-[1440px]">
                {{ $slot }}
            </div>
        </main>
    </div>

    <x-confirmation-dialog />
    <x-crud-modal />
</body>
</html>
@endif
