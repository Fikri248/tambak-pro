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
            <table class="report-table">
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
            <table class="report-table">
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
