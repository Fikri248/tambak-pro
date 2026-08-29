<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DashboardAnalyticsService
{
    private const PERIODS = [
        '30d' => ['label' => '30 Hari', 'months' => null],
        '3m' => ['label' => '3 Bulan', 'months' => 3],
        '6m' => ['label' => '6 Bulan', 'months' => 6],
        '12m' => ['label' => '12 Bulan', 'months' => 12],
    ];

    /** @return array<string, mixed> */
    public function get(string $period, ?int $tambakId): array
    {
        $range = $this->periodRange($period);
        $buckets = $this->buckets($range);
        $stocking = $this->historicalTotals(
            'stocking_transactions',
            'st',
            'SUM(st.quantity)',
            $range,
            $tambakId,
        );
        $mortality = $this->historicalTotals(
            'stock_adjustments',
            'sa',
            'SUM(ABS(sa.quantity_change))',
            $range,
            $tambakId,
            fn (Builder $query): Builder => $query->where('sa.adjustment_type', 'MORTALITY'),
        );
        $feedingCost = $this->historicalTotals(
            'feeding_transactions',
            'ft',
            'SUM(ft.total_cost)',
            $range,
            $tambakId,
        );

        return [
            'period' => $range,
            'periodOptions' => collect(self::PERIODS)->map(
                fn (array $definition, string $value): array => ['value' => $value, 'label' => $definition['label']]
            )->values()->all(),
            'tambakOptions' => DB::table('locations')
                ->where('location_type', 'TAMBAK')
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (object $row): array => ['value' => (int) $row->id, 'label' => $row->name])
                ->all(),
            'charts' => [
                'stockByTambak' => $this->stockByTambak($tambakId),
                'stockByCommodity' => $this->stockByCommodity($tambakId),
                'stockingTrend' => $this->singleSeries($buckets, $stocking, 'Bibit masuk', 'quantity'),
                'mortalityTrend' => $this->singleSeries($buckets, $mortality, 'Kematian', 'quantity'),
                'feedingCostTrend' => $this->singleSeries($buckets, $feedingCost, 'Biaya', 'currency'),
                'transactionActivity' => $this->transactionActivity($buckets, $range, $tambakId),
            ],
            'petakSummary' => $this->petakSummary($range, $tambakId),
        ];
    }

    /** @return array<string, mixed> */
    private function periodRange(string $period): array
    {
        $definition = self::PERIODS[$period];
        $now = Carbon::now(config('app.timezone'));
        $start = $definition['months'] === null
            ? $now->copy()->startOfDay()->subDays(29)
            : $now->copy()->startOfMonth()->subMonths($definition['months'] - 1);

        return [
            'value' => $period,
            'label' => $definition['label'],
            'bucket' => $definition['months'] === null ? 'day' : 'month',
            'dateFrom' => $start->toDateString(),
            'dateTo' => $now->toDateString(),
            'start' => $start,
            'end' => $now->copy()->endOfDay(),
        ];
    }

    /** @param array<string, mixed> $range */
    private function buckets(array $range): array
    {
        $buckets = [];
        $cursor = $range['start']->copy();

        while ($cursor->lte($range['end'])) {
            $key = $range['bucket'] === 'day' ? $cursor->format('Y-m-d') : $cursor->format('Y-m');
            $buckets[$key] = [
                'label' => $range['bucket'] === 'day'
                    ? $cursor->locale('id')->translatedFormat('d M')
                    : $cursor->locale('id')->translatedFormat('M Y'),
                'value' => 0.0,
            ];
            $range['bucket'] === 'day' ? $cursor->addDay() : $cursor->addMonth();
        }

        return $buckets;
    }

    /** @return array<string, mixed> */
    private function stockByTambak(?int $tambakId): array
    {
        $query = DB::table('pond_stocks as ps')
            ->join('locations as petak', 'petak.id', '=', 'ps.location_id')
            ->join('locations as tambak', 'tambak.id', '=', 'petak.parent_id')
            ->where('petak.location_type', 'PETAK')
            ->where('tambak.location_type', 'TAMBAK')
            ->where('ps.quantity', '>', 0)
            ->when($tambakId, fn (Builder $query, int $id): Builder => $query->where('tambak.id', $id))
            ->select(['tambak.id', 'tambak.name'])
            ->selectRaw('SUM(ps.quantity) as total')
            ->groupBy('tambak.id', 'tambak.name')
            ->orderByDesc('total')
            ->orderBy('tambak.name')
            ->get();

        return $this->categorySeries($query, 'name', 'total', 'quantity');
    }

    /** @return array<string, mixed> */
    private function stockByCommodity(?int $tambakId): array
    {
        $query = DB::table('pond_stocks as ps')
            ->join('locations as petak', 'petak.id', '=', 'ps.location_id')
            ->join('locations as tambak', 'tambak.id', '=', 'petak.parent_id')
            ->join('commodity_batches as batch', 'batch.id', '=', 'ps.batch_id')
            ->join('commodities as commodity', 'commodity.id', '=', 'batch.commodity_id')
            ->where('ps.quantity', '>', 0)
            ->when($tambakId, fn (Builder $query, int $id): Builder => $query->where('tambak.id', $id))
            ->select(['commodity.id', 'commodity.name', 'commodity.unit'])
            ->selectRaw('SUM(ps.quantity) as total')
            ->groupBy('commodity.id', 'commodity.name', 'commodity.unit')
            ->orderByDesc('total')
            ->orderBy('commodity.name')
            ->get();

        $series = $this->categorySeries($query, 'name', 'total', 'quantity');
        $series['units'] = $query->pluck('unit')->all();

        return $series;
    }

    /**
     * @param  array<string, mixed>  $range
     * @param  callable(Builder): Builder|null  $scope
     * @return array<string, float>
     */
    private function historicalTotals(
        string $table,
        string $alias,
        string $expression,
        array $range,
        ?int $tambakId,
        ?callable $scope = null,
    ): array {
        $query = DB::table("{$table} as {$alias}")
            ->whereBetween("{$alias}.transaction_date", [$range['start'], $range['end']]);
        $this->filterDirectLocation($query, $alias, $tambakId);

        if ($scope) {
            $scope($query);
        }

        return $query
            ->selectRaw("DATE({$alias}.transaction_date) as activity_date, {$expression} as total")
            ->groupByRaw("DATE({$alias}.transaction_date)")
            ->pluck('total', 'activity_date')
            ->map(fn (mixed $value): float => (float) $value)
            ->all();
    }

    private function filterDirectLocation(Builder $query, string $alias, ?int $tambakId): void
    {
        if ($tambakId === null) {
            return;
        }

        $query->join('locations as filtered_petak', 'filtered_petak.id', '=', "{$alias}.location_id")
            ->where('filtered_petak.parent_id', $tambakId)
            ->where('filtered_petak.location_type', 'PETAK');
    }

    /**
     * @param  array<string, array{label: string, value: float}>  $buckets
     * @param  array<string, float>  $dailyValues
     * @return array<string, mixed>
     */
    private function singleSeries(array $buckets, array $dailyValues, string $label, string $format): array
    {
        $normalized = $this->normalizeDailyValues($buckets, $dailyValues);

        return [
            'labels' => array_column($normalized, 'label'),
            'values' => array_column($normalized, 'value'),
            'datasets' => [['label' => $label, 'values' => array_column($normalized, 'value')]],
            'format' => $format,
            'hasData' => collect($normalized)->contains(fn (array $bucket): bool => $bucket['value'] > 0),
        ];
    }

    /**
     * @param  array<string, array{label: string, value: float}>  $buckets
     * @param  array<string, float>  $dailyValues
     * @return array<int, array{label: string, value: float}>
     */
    private function normalizeDailyValues(array $buckets, array $dailyValues): array
    {
        foreach ($dailyValues as $date => $value) {
            $bucket = strlen($date) === 7 || array_key_exists($date, $buckets)
                ? $date
                : Carbon::parse($date, config('app.timezone'))->format('Y-m');

            if (isset($buckets[$bucket])) {
                $buckets[$bucket]['value'] += $value;
            }
        }

        return array_values($buckets);
    }

    /**
     * @param  array<string, array{label: string, value: float}>  $buckets
     * @param  array<string, mixed>  $range
     * @return array<string, mixed>
     */
    private function transactionActivity(array $buckets, array $range, ?int $tambakId): array
    {
        $definitions = [
            ['label' => 'Pembibitan', 'table' => 'stocking_transactions', 'alias' => 'st'],
            ['label' => 'Pemindahan', 'table' => 'stock_movements', 'alias' => 'sm'],
            ['label' => 'Perubahan Jumlah', 'table' => 'stock_adjustments', 'alias' => 'sa'],
            ['label' => 'Pemberian Pakan', 'table' => 'feeding_transactions', 'alias' => 'ft'],
        ];
        $datasets = [];

        foreach ($definitions as $definition) {
            $daily = $definition['alias'] === 'sm'
                ? $this->movementCounts($range, $tambakId)
                : $this->historicalTotals(
                    $definition['table'],
                    $definition['alias'],
                    "COUNT({$definition['alias']}.id)",
                    $range,
                    $tambakId,
                );
            $normalized = $this->normalizeDailyValues($buckets, $daily);
            $datasets[] = [
                'label' => $definition['label'],
                'values' => array_map(fn (array $bucket): int => (int) $bucket['value'], $normalized),
            ];
        }

        return [
            'labels' => array_column(array_values($buckets), 'label'),
            'datasets' => $datasets,
            'format' => 'count',
            'hasData' => collect($datasets)->contains(
                fn (array $dataset): bool => collect($dataset['values'])->contains(fn (int $value): bool => $value > 0)
            ),
        ];
    }

    /** @param array<string, mixed> $range */
    private function movementCounts(array $range, ?int $tambakId): array
    {
        $query = DB::table('stock_movements as sm')
            ->whereBetween('sm.transaction_date', [$range['start'], $range['end']]);

        if ($tambakId !== null) {
            $query->join('locations as source_petak', 'source_petak.id', '=', 'sm.from_location_id')
                ->join('locations as destination_petak', 'destination_petak.id', '=', 'sm.to_location_id')
                ->where(fn (Builder $query): Builder => $query
                    ->where('source_petak.parent_id', $tambakId)
                    ->orWhere('destination_petak.parent_id', $tambakId));
        }

        return $query
            ->selectRaw('DATE(sm.transaction_date) as activity_date, COUNT(sm.id) as total')
            ->groupByRaw('DATE(sm.transaction_date)')
            ->pluck('total', 'activity_date')
            ->map(fn (mixed $value): float => (float) $value)
            ->all();
    }

    /** @return array<string, mixed> */
    private function categorySeries(Collection $rows, string $label, string $value, string $format): array
    {
        $values = $rows->pluck($value)->map(fn (mixed $entry): float => (float) $entry)->all();

        return [
            'labels' => $rows->pluck($label)->all(),
            'values' => $values,
            'datasets' => [['label' => 'Stok saat ini', 'values' => $values]],
            'format' => $format,
            'hasData' => collect($values)->contains(fn (float $entry): bool => $entry > 0),
        ];
    }

    /** @param array<string, mixed> $range */
    private function petakSummary(array $range, ?int $tambakId): Collection
    {
        $activity = $this->locationActivity($range);

        return DB::table('pond_stocks as ps')
            ->join('locations as petak', 'petak.id', '=', 'ps.location_id')
            ->join('locations as tambak', 'tambak.id', '=', 'petak.parent_id')
            ->join('commodity_batches as batch', 'batch.id', '=', 'ps.batch_id')
            ->join('commodities as commodity', 'commodity.id', '=', 'batch.commodity_id')
            ->leftJoinSub($activity, 'activity', 'activity.location_id', '=', 'petak.id')
            ->where('ps.quantity', '>', 0)
            ->when($tambakId, fn (Builder $query, int $id): Builder => $query->where('tambak.id', $id))
            ->select(['tambak.name as tambak_name', 'petak.id as petak_id', 'petak.name as petak_name'])
            ->selectRaw('COUNT(DISTINCT ps.batch_id) as batch_count')
            ->selectRaw('GROUP_CONCAT(DISTINCT commodity.name) as commodity_names')
            ->selectRaw('SUM(ps.quantity) as current_stock')
            ->selectRaw('MAX(activity.last_activity) as last_activity')
            ->groupBy('tambak.name', 'petak.id', 'petak.name')
            ->orderByRaw('MAX(activity.last_activity) IS NULL')
            ->orderByDesc(DB::raw('MAX(activity.last_activity)'))
            ->orderByDesc('current_stock')
            ->limit(10)
            ->get()
            ->map(function (object $row): array {
                $commodities = collect(explode(',', (string) $row->commodity_names))->filter()->sort()->values();

                return [
                    'tambak' => $row->tambak_name,
                    'petak' => $row->petak_name,
                    'petakUrl' => route('tambak.show', $row->petak_id),
                    'batches' => (int) $row->batch_count,
                    'commodities' => $commodities->count() <= 2
                        ? $commodities->implode(', ')
                        : $commodities->take(2)->implode(', ').' +'.($commodities->count() - 2),
                    'stock' => (float) $row->current_stock,
                    'lastActivity' => $row->last_activity
                        ? Carbon::parse($row->last_activity)->locale('id')->translatedFormat('d M Y, H:i')
                        : null,
                ];
            });
    }

    /** @param array<string, mixed> $range */
    private function locationActivity(array $range): Builder
    {
        $stocking = $this->activitySource('stocking_transactions', 'st', 'st.location_id', $range);
        $movementOut = $this->activitySource('stock_movements', 'smo', 'smo.from_location_id', $range);
        $movementIn = $this->activitySource('stock_movements', 'smi', 'smi.to_location_id', $range);
        $adjustment = $this->activitySource('stock_adjustments', 'sa', 'sa.location_id', $range);
        $feeding = $this->activitySource('feeding_transactions', 'ft', 'ft.location_id', $range);
        $union = $stocking->unionAll($movementOut)->unionAll($movementIn)->unionAll($adjustment)->unionAll($feeding);

        return DB::query()->fromSub($union, 'activity')
            ->select('location_id')
            ->selectRaw('MAX(transaction_date) as last_activity')
            ->groupBy('location_id');
    }

    /** @param array<string, mixed> $range */
    private function activitySource(string $table, string $alias, string $locationColumn, array $range): Builder
    {
        return DB::table("{$table} as {$alias}")
            ->whereBetween("{$alias}.transaction_date", [$range['start'], $range['end']])
            ->selectRaw("{$locationColumn} as location_id, {$alias}.transaction_date");
    }
}
