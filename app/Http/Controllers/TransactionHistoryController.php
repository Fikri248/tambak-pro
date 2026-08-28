<?php

namespace App\Http\Controllers;

use App\Http\Requests\TransactionHistoryRequest;
use App\Models\Commodity;
use App\Models\Location;
use App\Models\User;
use App\Support\PageSize;
use App\Support\UserFacing;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class TransactionHistoryController extends Controller
{
    public function index(TransactionHistoryRequest $request): View
    {
        $filters = $request->validated();
        $filters['search'] = $filters['search'] ?? '';
        $filters['type'] = $filters['type'] ?? null;
        $filters['location_id'] = isset($filters['location_id']) ? (int) $filters['location_id'] : null;
        $filters['commodity_id'] = isset($filters['commodity_id']) ? (int) $filters['commodity_id'] : null;
        $filters['user_id'] = isset($filters['user_id']) ? (int) $filters['user_id'] : null;
        $filters['date_from'] = $filters['date_from'] ?? null;
        $filters['date_to'] = $filters['date_to'] ?? null;

        $union = $this->buildUnion($filters);
        $historyQuery = DB::query()->fromSub($union, 'history');
        $now = Carbon::now(config('app.timezone'));
        $summary = [
            'total' => (clone $historyQuery)->count(),
            'stock' => (clone $historyQuery)->whereIn('type', ['STOCKING', 'MOVEMENT', 'ADJUSTMENT'])->count(),
            'feeding' => (clone $historyQuery)->where('type', 'FEEDING')->count(),
            'currentMonth' => (clone $historyQuery)
                ->whereBetween('transaction_date', [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()])
                ->count(),
        ];
        $history = $historyQuery
            ->orderByDesc('transaction_date')
            ->orderByDesc('created_at')
            ->orderByDesc('type')
            ->orderByDesc('source_id')
            ->paginate(PageSize::resolve($request))
            ->withQueryString();

        $this->normalizePage($history);

        return view('history.index', [
            'history' => $history,
            'summary' => $summary,
            'filters' => $filters,
            'typeLabels' => UserFacing::TRANSACTION_TYPES,
            'locations' => Location::query()->where('location_type', 'PETAK')->orderBy('name')->get(['id', 'code', 'name']),
            'commodities' => Commodity::query()->orderBy('name')->get(['id', 'code', 'name']),
            'users' => User::query()->orderBy('name')->get(['id', 'name', 'email']),
        ]);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function buildUnion(array $filters): Builder
    {
        $types = $filters['type'] ? [$filters['type']] : array_keys(UserFacing::TRANSACTION_TYPES);
        $queries = [];

        if (in_array('STOCKING', $types, true)) {
            $queries[] = $this->stockingQuery($filters);
        }

        if (in_array('MOVEMENT', $types, true)) {
            $queries[] = $this->movementQuery($filters);
        }

        if (in_array('ADJUSTMENT', $types, true)) {
            $queries[] = $this->adjustmentQuery($filters);
        }

        if (in_array('FEEDING', $types, true)) {
            $queries[] = $this->feedingQuery($filters);
        }

        $union = array_shift($queries);

        foreach ($queries as $query) {
            $union->unionAll($query);
        }

        return $union;
    }

    /** @param array<string, mixed> $filters */
    private function stockingQuery(array $filters): Builder
    {
        $query = DB::table('stocking_transactions as source')
            ->join('locations as location', 'location.id', '=', 'source.location_id')
            ->join('commodity_batches as batch', 'batch.id', '=', 'source.batch_id')
            ->join('commodities as commodity', 'commodity.id', '=', 'batch.commodity_id')
            ->leftJoin('users as creator', 'creator.id', '=', 'source.created_by')
            ->selectRaw("source.id as source_id, 'STOCKING' as type")
            ->addSelect([
                'source.transaction_number',
                'source.transaction_date',
                'location.id as location_id',
                'location.name as location_name',
                DB::raw('NULL as secondary_location_id'),
                DB::raw('NULL as secondary_location_name'),
                'batch.id as batch_id',
                'batch.batch_code',
                'commodity.id as commodity_id',
                'commodity.name as commodity_name',
                DB::raw('NULL as feed_item_name'),
                DB::raw('NULL as adjustment_type'),
                'source.quantity',
                'commodity.unit',
                'source.total_cost as amount',
                'source.created_by as user_id',
                'creator.name as user_name',
                'source.created_at',
            ]);

        $this->applyCommonFilters($query, $filters, 'source');

        if ($filters['search'] !== '') {
            $search = '%'.$filters['search'].'%';
            $query->where(function (Builder $query) use ($search): void {
                $query->where('source.transaction_number', 'like', $search)
                    ->orWhere('batch.batch_code', 'like', $search)
                    ->orWhere('commodity.name', 'like', $search)
                    ->orWhere('location.name', 'like', $search)
                    ->orWhere('source.notes', 'like', $search)
                    ->orWhere('creator.name', 'like', $search);
            });
        }

        if ($filters['location_id']) {
            $query->where('source.location_id', $filters['location_id']);
        }

        if ($filters['commodity_id']) {
            $query->where('batch.commodity_id', $filters['commodity_id']);
        }

        return $query;
    }

    /** @param array<string, mixed> $filters */
    private function movementQuery(array $filters): Builder
    {
        $query = DB::table('stock_movements as source')
            ->join('locations as location', 'location.id', '=', 'source.from_location_id')
            ->join('locations as destination', 'destination.id', '=', 'source.to_location_id')
            ->join('commodity_batches as batch', 'batch.id', '=', 'source.batch_id')
            ->join('commodities as commodity', 'commodity.id', '=', 'batch.commodity_id')
            ->leftJoin('users as creator', 'creator.id', '=', 'source.created_by')
            ->selectRaw("source.id as source_id, 'MOVEMENT' as type")
            ->addSelect([
                'source.transaction_number',
                'source.transaction_date',
                'location.id as location_id',
                'location.name as location_name',
                'destination.id as secondary_location_id',
                'destination.name as secondary_location_name',
                'batch.id as batch_id',
                'batch.batch_code',
                'commodity.id as commodity_id',
                'commodity.name as commodity_name',
                DB::raw('NULL as feed_item_name'),
                DB::raw('NULL as adjustment_type'),
                'source.quantity',
                'commodity.unit',
                DB::raw('NULL as amount'),
                'source.created_by as user_id',
                'creator.name as user_name',
                'source.created_at',
            ]);

        $this->applyCommonFilters($query, $filters, 'source');

        if ($filters['search'] !== '') {
            $search = '%'.$filters['search'].'%';
            $query->where(function (Builder $query) use ($search): void {
                $query->where('source.transaction_number', 'like', $search)
                    ->orWhere('batch.batch_code', 'like', $search)
                    ->orWhere('commodity.name', 'like', $search)
                    ->orWhere('location.name', 'like', $search)
                    ->orWhere('destination.name', 'like', $search)
                    ->orWhere('source.notes', 'like', $search)
                    ->orWhere('creator.name', 'like', $search);
            });
        }

        if ($filters['location_id']) {
            $query->where(function (Builder $query) use ($filters): void {
                $query->where('source.from_location_id', $filters['location_id'])
                    ->orWhere('source.to_location_id', $filters['location_id']);
            });
        }

        if ($filters['commodity_id']) {
            $query->where('batch.commodity_id', $filters['commodity_id']);
        }

        return $query;
    }

    /** @param array<string, mixed> $filters */
    private function adjustmentQuery(array $filters): Builder
    {
        $query = DB::table('stock_adjustments as source')
            ->join('locations as location', 'location.id', '=', 'source.location_id')
            ->join('commodity_batches as batch', 'batch.id', '=', 'source.batch_id')
            ->join('commodities as commodity', 'commodity.id', '=', 'batch.commodity_id')
            ->leftJoin('users as creator', 'creator.id', '=', 'source.created_by')
            ->selectRaw("source.id as source_id, 'ADJUSTMENT' as type")
            ->addSelect([
                'source.transaction_number',
                'source.transaction_date',
                'location.id as location_id',
                'location.name as location_name',
                DB::raw('NULL as secondary_location_id'),
                DB::raw('NULL as secondary_location_name'),
                'batch.id as batch_id',
                'batch.batch_code',
                'commodity.id as commodity_id',
                'commodity.name as commodity_name',
                DB::raw('NULL as feed_item_name'),
                'source.adjustment_type',
                'source.quantity_change as quantity',
                'commodity.unit',
                DB::raw('NULL as amount'),
                'source.created_by as user_id',
                'creator.name as user_name',
                'source.created_at',
            ]);

        $this->applyCommonFilters($query, $filters, 'source');

        if ($filters['search'] !== '') {
            $search = '%'.$filters['search'].'%';
            $query->where(function (Builder $query) use ($search): void {
                $query->where('source.transaction_number', 'like', $search)
                    ->orWhere('batch.batch_code', 'like', $search)
                    ->orWhere('commodity.name', 'like', $search)
                    ->orWhere('location.name', 'like', $search)
                    ->orWhere('source.reason', 'like', $search)
                    ->orWhere('creator.name', 'like', $search);
            });
        }

        if ($filters['location_id']) {
            $query->where('source.location_id', $filters['location_id']);
        }

        if ($filters['commodity_id']) {
            $query->where('batch.commodity_id', $filters['commodity_id']);
        }

        return $query;
    }

    /** @param array<string, mixed> $filters */
    private function feedingQuery(array $filters): Builder
    {
        $query = DB::table('feeding_transactions as source')
            ->join('locations as location', 'location.id', '=', 'source.location_id')
            ->leftJoin('commodity_batches as batch', 'batch.id', '=', 'source.batch_id')
            ->leftJoin('commodities as commodity', 'commodity.id', '=', 'batch.commodity_id')
            ->join('feed_items as feed_item', 'feed_item.id', '=', 'source.feed_item_id')
            ->leftJoin('vendors as vendor', 'vendor.id', '=', 'source.vendor_id')
            ->leftJoin('users as creator', 'creator.id', '=', 'source.created_by')
            ->selectRaw("source.id as source_id, 'FEEDING' as type")
            ->addSelect([
                'source.transaction_number',
                'source.transaction_date',
                'location.id as location_id',
                'location.name as location_name',
                DB::raw('NULL as secondary_location_id'),
                DB::raw('NULL as secondary_location_name'),
                'batch.id as batch_id',
                'batch.batch_code',
                'commodity.id as commodity_id',
                'commodity.name as commodity_name',
                'feed_item.name as feed_item_name',
                DB::raw('NULL as adjustment_type'),
                'source.feed_quantity as quantity',
                'feed_item.unit',
                'source.total_cost as amount',
                'source.created_by as user_id',
                'creator.name as user_name',
                'source.created_at',
            ]);

        $this->applyCommonFilters($query, $filters, 'source');

        if ($filters['search'] !== '') {
            $search = '%'.$filters['search'].'%';
            $query->where(function (Builder $query) use ($search): void {
                $query->where('source.transaction_number', 'like', $search)
                    ->orWhere('feed_item.name', 'like', $search)
                    ->orWhere('feed_item.code', 'like', $search)
                    ->orWhere('batch.batch_code', 'like', $search)
                    ->orWhere('commodity.name', 'like', $search)
                    ->orWhere('location.name', 'like', $search)
                    ->orWhere('vendor.name', 'like', $search)
                    ->orWhere('source.notes', 'like', $search)
                    ->orWhere('creator.name', 'like', $search);
            });
        }

        if ($filters['location_id']) {
            $query->where('source.location_id', $filters['location_id']);
        }

        if ($filters['commodity_id']) {
            $query->where('batch.commodity_id', $filters['commodity_id']);
        }

        return $query;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applyCommonFilters(Builder $query, array $filters, string $alias): void
    {
        if ($filters['user_id']) {
            $query->where("{$alias}.created_by", $filters['user_id']);
        }

        if ($filters['date_from']) {
            $query->whereDate("{$alias}.transaction_date", '>=', $filters['date_from']);
        }

        if ($filters['date_to']) {
            $query->whereDate("{$alias}.transaction_date", '<=', $filters['date_to']);
        }
    }

    private function normalizePage(LengthAwarePaginator $history): void
    {
        $history->setCollection($history->getCollection()->map(function (object $row): object {
            $row->type_label = UserFacing::TRANSACTION_TYPES[$row->type];
            $row->transaction_date = Carbon::parse($row->transaction_date);
            $row->created_at = Carbon::parse($row->created_at);
            $row->quantity = (float) $row->quantity;
            $row->amount = $row->amount !== null ? (float) $row->amount : null;
            $row->activity = $this->activity($row);
            $row->location_display = $row->secondary_location_name
                ? "{$row->location_name} → {$row->secondary_location_name}"
                : $row->location_name;
            $row->detail_url = match ($row->type) {
                'STOCKING' => route('stocking.show', $row->source_id),
                'MOVEMENT' => route('movements.show', $row->source_id),
                'ADJUSTMENT' => route('adjustments.show', $row->source_id),
                'FEEDING' => route('feeding.show', $row->source_id),
            };

            return $row;
        }));
    }

    private function activity(object $row): string
    {
        return match ($row->type) {
            'STOCKING', 'MOVEMENT' => "{$row->batch_code} · {$row->commodity_name}",
            'ADJUSTMENT' => (UserFacing::ADJUSTMENT_TYPES[$row->adjustment_type] ?? 'Lainnya')
                ." · {$row->batch_code} · {$row->commodity_name}",
            'FEEDING' => $row->feed_item_name.' · '.($row->batch_code ?: 'Seluruh Petak'),
        };
    }
}
