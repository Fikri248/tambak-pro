@props([
    'from' => null,
    'to' => null,
    'fromName' => 'date_from',
    'toName' => 'date_to',
    'fromLabel' => 'Tanggal Mulai',
    'toLabel' => 'Tanggal Selesai',
    'help' => 'Pilih rentang tanggal transaksi yang ingin ditampilkan.',
    'id' => null,
])

@php
    $id = $id ?: "{$fromName}-{$toName}";
    $fromId = $fromName;
    $toId = $toName;
    $helpId = "{$id}-help";
    $fromErrorId = "{$fromId}-error";
    $toErrorId = "{$toId}-error";
    $fromValue = old($fromName, $from);
    $toValue = old($toName, $to);
    $fromDescribedBy = collect([$help ? $helpId : null, $errors->has($fromName) ? $fromErrorId : null])->filter()->implode(' ');
    $toDescribedBy = collect([$help ? $helpId : null, $errors->has($toName) ? $toErrorId : null])->filter()->implode(' ');
@endphp

<fieldset data-date-range-filter {{ $attributes->class('min-w-0') }}>
    <legend class="sr-only">Rentang tanggal</legend>
    @if ($help)
        <p id="{{ $helpId }}" class="mb-3 text-xs leading-5 text-neutral-500">{{ $help }}</p>
    @endif

    <div class="grid gap-3 sm:grid-cols-2">
        <div>
            <label for="{{ $fromId }}" class="mb-1.5 block text-xs font-medium text-neutral-700">{{ $fromLabel }}</label>
            <input
                id="{{ $fromId }}"
                name="{{ $fromName }}"
                data-date-range-from
                type="date"
                value="{{ $fromValue }}"
                @if ($toValue) max="{{ $toValue }}" @endif
                class="h-10 w-full rounded-lg border bg-white px-3 text-sm text-neutral-800 transition-colors focus:border-neutral-400 focus:outline-none focus:ring-2 focus:ring-neutral-200 {{ $errors->has($fromName) ? 'border-neutral-400' : 'border-neutral-200 hover:border-neutral-300' }}"
                @if ($fromDescribedBy !== '') aria-describedby="{{ $fromDescribedBy }}" @endif
                @if ($errors->has($fromName)) aria-invalid="true" @endif
            >
            @error($fromName)
                <p id="{{ $fromErrorId }}" class="mt-1.5 text-xs font-medium text-neutral-700">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="{{ $toId }}" class="mb-1.5 block text-xs font-medium text-neutral-700">{{ $toLabel }}</label>
            <input
                id="{{ $toId }}"
                name="{{ $toName }}"
                data-date-range-to
                type="date"
                value="{{ $toValue }}"
                @if ($fromValue) min="{{ $fromValue }}" @endif
                class="h-10 w-full rounded-lg border bg-white px-3 text-sm text-neutral-800 transition-colors focus:border-neutral-400 focus:outline-none focus:ring-2 focus:ring-neutral-200 {{ $errors->has($toName) ? 'border-neutral-400' : 'border-neutral-200 hover:border-neutral-300' }}"
                @if ($toDescribedBy !== '') aria-describedby="{{ $toDescribedBy }}" @endif
                @if ($errors->has($toName)) aria-invalid="true" @endif
            >
            @error($toName)
                <p id="{{ $toErrorId }}" class="mt-1.5 text-xs font-medium text-neutral-700">{{ $message }}</p>
            @enderror
        </div>
    </div>
</fieldset>
