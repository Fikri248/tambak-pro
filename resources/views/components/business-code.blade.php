@props(['value' => null, 'label' => 'Kode'])

<div {{ $attributes }}>
    <p class="text-sm font-medium text-neutral-700">{{ $label }}</p>
    <div class="mt-1.5 rounded-lg border border-neutral-200 bg-neutral-50 px-3 py-2.5">
        @if ($value)
            <p class="font-mono text-sm font-medium text-neutral-900">{{ $value }}</p>
            <p class="mt-0.5 text-xs text-neutral-500">Dibuat oleh sistem dan tidak dapat diubah.</p>
        @else
            <p class="text-sm text-neutral-600">Dibuat otomatis oleh sistem</p>
        @endif
    </div>
</div>
