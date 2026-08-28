@props(['variant' => 'primary', 'href' => null, 'type' => 'button'])

@php
    $baseClasses = 'inline-flex min-h-9 items-center justify-center gap-2 rounded-lg px-3.5 py-2 text-sm font-medium transition-colors disabled:cursor-not-allowed disabled:opacity-50';
    $variantClasses = match ($variant) {
        'secondary' => 'border border-neutral-200 bg-white text-neutral-800 hover:bg-neutral-50',
        default => 'border border-neutral-900 bg-neutral-900 text-white hover:bg-neutral-800',
    };
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class([$baseClasses, $variantClasses]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->class([$baseClasses, $variantClasses]) }}>{{ $slot }}</button>
@endif
