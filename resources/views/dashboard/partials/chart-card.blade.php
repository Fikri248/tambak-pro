<x-card :padding="false" class="{{ $cardClass ?? '' }}">
    <div class="flex items-start justify-between gap-4 border-b border-neutral-200 px-5 py-4 sm:px-6">
        <div class="min-w-0">
            <h3 class="text-base font-semibold text-neutral-950">{{ $title }}</h3>
            <p class="mt-1 text-xs leading-5 text-neutral-500">{{ $description }}</p>
        </div>
        <a href="{{ $reportUrl }}" class="shrink-0 text-xs font-medium text-neutral-600 hover:text-neutral-950 hover:underline">
            {{ $reportLabel }}
        </a>
    </div>

    @if ($chart['hasData'])
        <div class="h-64 px-3 py-4 sm:h-72 sm:px-5">
            <canvas data-dashboard-chart="{{ $chartKey }}" aria-hidden="true"></canvas>
        </div>
        <p class="sr-only">
            {{ $title }}:
            @if ($chartKey === 'transactionActivity')
                @foreach ($chart['datasets'] as $dataset)
                    {{ $dataset['label'] }} {{ number_format(array_sum($dataset['values']), 0, ',', '.') }} transaksi.
                @endforeach
            @else
                @foreach ($chart['labels'] as $index => $label)
                    @if (($chart['values'][$index] ?? 0) > 0)
                        {{ $label }} {{ number_format((float) $chart['values'][$index], 3, ',', '.') }}.
                    @endif
                @endforeach
            @endif
        </p>
    @else
        <x-empty-state :title="$emptyTitle" :description="$emptyDescription" icon="report" class="min-h-64 sm:min-h-72" />
    @endif
</x-card>
