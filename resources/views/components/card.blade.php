@props(['padding' => true])

<section {{ $attributes->class(['min-w-0 rounded-xl border border-neutral-200 bg-white', 'p-5 sm:p-6' => $padding]) }}>
    {{ $slot }}
</section>
