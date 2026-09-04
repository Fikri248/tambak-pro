@php
    $reportColumnWidths = [
        'stock' => [10, 10, 9, 13, 13, 10, 11, 12, 12],
        'stocking' => [11, 9, 8, 8, 12, 12, 9, 10, 11, 10],
        'movements' => [13, 12, 11, 14, 14, 14, 11, 11],
        'adjustments' => [10, 9, 8, 8, 8, 10, 8, 8, 8, 13, 10],
        'purchases' => [13, 11, 15, 12, 13, 10, 10, 9, 7],
        'feeding' => [9, 8, 7, 8, 12, 7, 10, 7, 8, 8, 9, 7],
        'items' => [12, 22, 15, 9, 16, 14, 12],
        'vendors' => [12, 20, 12, 10, 10, 13, 10, 13],
        'commodities' => [9, 15, 9, 7, 8, 8, 9, 9, 8, 8, 10],
        'locations' => [9, 12, 12, 7, 7, 7, 10, 10, 8, 10, 8],
    ];
    $mainColumnWidths = $reportColumnWidths[$reportKey] ?? [];
    $secondaryColumnWidths = $reportKey === 'feeding' ? [24, 12, 10, 14, 18, 22] : [];
@endphp

<header>
    <p class="identity">TAMBAK PRO · LAPORAN OPERASIONAL</p>
    <h1>{{ $title }}</h1>
    <p class="description">{{ $description }}</p>
    <p class="generated"><strong>Dicetak:</strong> {{ $generatedAt }}</p>
</header>

<section class="section" aria-labelledby="filter-title">
    <h2 id="filter-title" class="section-title">Filter</h2>
    <table class="filter-table">
        <tbody>
            @forelse ($filterSummary as $filter)
                <tr>
                    <th scope="row">{{ $filter['label'] }}</th>
                    <td>{{ $filter['value'] }}</td>
                </tr>
            @empty
                <tr>
                    <th scope="row">Filter</th>
                    <td>Semua Data</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</section>

<section class="section" aria-labelledby="summary-title">
    <h2 id="summary-title" class="section-title">Ringkasan</h2>
    <table class="summary-table">
        <tr>
            @foreach ($summaryCards as $card)
                <td>
                    <span class="summary-label">{{ $card['label'] }}</span>
                    <span class="summary-value">{{ $card['value'] }}@if ($card['suffix']) {{ $card['suffix'] }}@endif</span>
                </td>
            @endforeach
        </tr>
    </table>
</section>

@if ($notice)
    <p class="notice">{{ $notice }}</p>
@endif

@if (isset($secondary) && ! empty($secondary['rows']))
    <section class="section" aria-labelledby="secondary-title">
        <h2 id="secondary-title" class="section-title">{{ $secondary['title'] }}</h2>
        <p class="table-description">{{ $secondary['description'] }}</p>
        <div class="report-scroll">
            <table class="report-table report-table--{{ $reportKey }} report-table--secondary">
                @if (count($secondaryColumnWidths) === count($secondary['columns']))
                    <colgroup>
                        @foreach ($secondaryColumnWidths as $width)
                            <col style="width: {{ $width }}%;">
                        @endforeach
                    </colgroup>
                @endif
                <thead>
                    <tr>
                        @foreach ($secondary['columns'] as $column)
                            <th scope="col">{{ $column }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($secondary['rows'] as $row)
                        @include('reports.print.row', ['row' => $row])
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
@endif

<section class="section" aria-labelledby="table-title">
    <h2 id="table-title" class="section-title">{{ $tableTitle }}</h2>
    @if ($rows->isEmpty())
        <div class="empty-state">Tidak ada data yang sesuai dengan filter.</div>
    @else
        <div class="report-scroll">
            <table class="report-table report-table--{{ $reportKey }}">
                @if (count($mainColumnWidths) === count($columns))
                    <colgroup>
                        @foreach ($mainColumnWidths as $width)
                            <col style="width: {{ $width }}%;">
                        @endforeach
                    </colgroup>
                @endif
                <thead>
                    <tr>
                        @foreach ($columns as $column)
                            <th scope="col">{{ $column }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $row)
                        @include('reports.print.row', ['row' => $row])
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</section>

<footer class="document-footer">Dicetak dari Tambak Pro · {{ $generatedAt }}</footer>
