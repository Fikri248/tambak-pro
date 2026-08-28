@props(['name', 'strokeWidth' => 1.8])

<svg {{ $attributes->class('fill-none') }} viewBox="0 0 24 24" stroke="currentColor" stroke-width="{{ $strokeWidth }}" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    @switch($name)
        @case('waves')
            <path d="M3 8.5c2.3 0 2.3-1.5 4.6-1.5s2.3 1.5 4.6 1.5S14.5 7 16.8 7s2.3 1.5 4.2 1.5" />
            <path d="M3 12.5c2.3 0 2.3-1.5 4.6-1.5s2.3 1.5 4.6 1.5 2.3-1.5 4.6-1.5 2.3 1.5 4.2 1.5" />
            <path d="M3 16.5c2.3 0 2.3-1.5 4.6-1.5s2.3 1.5 4.6 1.5 2.3-1.5 4.6-1.5 2.3 1.5 4.2 1.5" />
            @break
        @case('dashboard')
            <rect x="3" y="3" width="7" height="7" rx="1" />
            <rect x="14" y="3" width="7" height="7" rx="1" />
            <rect x="3" y="14" width="7" height="7" rx="1" />
            <rect x="14" y="14" width="7" height="7" rx="1" />
            @break
        @case('map')
            <path d="m3 6 5-3 8 3 5-3v15l-5 3-8-3-5 3V6Z" />
            <path d="M8 3v15M16 6v15" />
            @break
        @case('package')
            <path d="m12 3 8 4.5v9L12 21l-8-4.5v-9L12 3Z" />
            <path d="m4.5 7.5 7.5 4 7.5-4M12 11.5V21" />
            @break
        @case('truck')
            <path d="M3 6h11v10H3zM14 10h4l3 3v3h-7z" />
            <circle cx="7" cy="18" r="2" />
            <circle cx="18" cy="18" r="2" />
            @break
        @case('seedling')
            <path d="M12 21V10" />
            <path d="M12 13c-4.5 0-7-2.5-7-7 4.5 0 7 2.5 7 7Z" />
            <path d="M12 10c0-4 2.5-6 6.5-6 0 4-2.5 6-6.5 6Z" />
            @break
        @case('transfer')
            <path d="M4 7h14M15 4l3 3-3 3M20 17H6M9 14l-3 3 3 3" />
            @break
        @case('history')
            <path d="M3 12a9 9 0 1 0 3-6.7L3 8" />
            <path d="M3 3v5h5M12 7v5l3 2" />
            @break
        @case('menu')
            <path d="M4 7h16M4 12h16M4 17h16" />
            @break
        @case('close')
            <path d="m6 6 12 12M18 6 6 18" />
            @break
        @case('search')
            <circle cx="11" cy="11" r="7" />
            <path d="m20 20-4-4" />
            @break
        @case('filter')
            <path d="M4 5h16l-6.5 7.5V19l-3 1v-7.5L4 5Z" />
            @break
        @case('chevron-down')
            <path d="m6 9 6 6 6-6" />
            @break
        @case('chevron-left')
            <path d="m15 18-6-6 6-6" />
            @break
        @case('whatsapp')
            <path d="M20 11.5a8 8 0 0 1-11.8 7L4 20l1.4-4.1A8 8 0 1 1 20 11.5Z" />
            <path d="M9.1 8.1c.2-.4.4-.4.7-.4h.5c.2 0 .3.1.4.4l.8 1.9c.1.3 0 .4-.1.6l-.6.7c-.2.2-.1.4 0 .6.6 1.1 1.5 2 2.6 2.6.2.1.4.2.6 0l.8-.9c.2-.2.4-.2.6-.1l1.9.9c.3.1.4.3.4.5 0 .3-.2 1.5-.9 2.1-.6.6-1.5.8-2.4.6-1.2-.3-2.8-1-4.5-2.5-1.4-1.3-2.5-2.9-2.8-4.2-.3-1.1.1-2.1.5-2.6.4-.4.9-.6 1.5-.2Z" />
            @break
        @case('logout')
            <path d="M10 5H5v14h5M14 8l4 4-4 4M8 12h10" />
            @break
        @case('key')
            <circle cx="8" cy="15" r="4" />
            <path d="m11 12 9-9M15 8l3 3M17 6l3 3" />
            @break
        @case('building')
            <path d="M4 21V5l8-3v19M12 8h8v13M8 7v1M8 12v1M8 17v1M16 12v1M16 17v1M2 21h20" />
            @break
        @case('adjustment')
            <path d="M4 7h10M18 7h2M4 17h2M10 17h10" />
            <circle cx="16" cy="7" r="2" />
            <circle cx="8" cy="17" r="2" />
            @break
        @case('feed')
            <path d="M7 4h10l2 5-2 11H7L5 9l2-5Z" />
            <path d="M5 9h14M9 14h6" />
            @break
        @case('calendar')
            <rect x="3" y="5" width="18" height="16" rx="2" />
            <path d="M16 3v4M8 3v4M3 10h18" />
            @break
        @case('plus')
            <path d="M12 5v14M5 12h14" />
            @break
        @case('arrow-left')
            <path d="m15 18-6-6 6-6M9 12h11" />
            @break
        @case('eye')
            <path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z" />
            <circle cx="12" cy="12" r="2.5" />
            @break
        @case('eye-off')
            <path d="m3 3 18 18M10.6 6.2A9.8 9.8 0 0 1 12 6c6.5 0 10 6 10 6a17.6 17.6 0 0 1-2.1 2.8M6.6 6.6C3.6 8.3 2 12 2 12s3.5 6 10 6a9.8 9.8 0 0 0 4.1-.9M9.9 9.9a3 3 0 0 0 4.2 4.2" />
            @break
        @case('edit')
            <path d="M4 20h4l11-11-4-4L4 16v4ZM13.5 6.5l4 4" />
            @break
        @case('trash')
            <path d="M4 7h16M9 7V4h6v3M7 7l1 13h8l1-13M10 11v5M14 11v5" />
            @break
        @case('power')
            <path d="M12 3v9M6.2 5.8a8 8 0 1 0 11.6 0" />
            @break
        @case('check')
            <path d="m5 12 4 4L19 6" />
            @break
        @case('warning')
            <path d="M12 3 2.8 20h18.4L12 3Z" />
            <path d="M12 9v4M12 17h.01" />
            @break
        @case('coins')
            <ellipse cx="12" cy="6" rx="7" ry="3" />
            <path d="M5 6v5c0 1.7 3.1 3 7 3s7-1.3 7-3V6M5 11v5c0 1.7 3.1 3 7 3s7-1.3 7-3v-5" />
            @break
        @case('report')
            <path d="M5 3h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z" />
            <path d="M7 16v-3M12 16V8M17 16v-5" />
            @break
        @case('download')
            <path d="M12 3v12M7 10l5 5 5-5M4 21h16" />
            @break
        @case('printer')
            <path d="M7 8V3h10v5M7 17H5a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2" />
            <path d="M7 14h10v7H7zM17 11h.01" />
            @break
        @default
            <circle cx="12" cy="12" r="9" />
            <path d="M12 8v4M12 16h.01" />
    @endswitch
</svg>
