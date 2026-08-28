<?php

namespace App\Http\Controllers;

use App\Http\Requests\LocationRequest;
use App\Models\FeedingTransaction;
use App\Models\Location;
use App\Models\PondStock;
use App\Models\StockAdjustment;
use App\Models\StockingTransaction;
use App\Models\StockMovement;
use App\Services\BusinessCodeGenerator;
use App\Support\PageSize;
use App\Support\UserFacing;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class LocationController extends Controller
{
    public function index(Request $request): View
    {
        $search = mb_substr(trim((string) $request->query('search')), 0, 255);
        $type = in_array($request->query('type'), array_keys(UserFacing::LOCATION_TYPES), true)
            ? $request->query('type')
            : null;
        $status = in_array($request->query('status'), ['ACTIVE', 'INACTIVE'], true)
            ? $request->query('status')
            : null;

        $locations = Location::query()
            ->with('parent:id,code,name')
            ->withCount('children')
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%");
                });
            })
            ->when($type, fn (Builder $query, string $type) => $query->where('location_type', $type))
            ->when($status, fn (Builder $query, string $status) => $query->where('status', $status))
            ->orderByRaw("CASE location_type WHEN 'AREA' THEN 1 WHEN 'TAMBAK' THEN 2 WHEN 'PETAK' THEN 3 ELSE 4 END")
            ->orderBy('name')
            ->paginate(PageSize::resolve($request))
            ->withQueryString();

        return view('tambak.index', [
            'locations' => $locations,
            'typeLabels' => UserFacing::LOCATION_TYPES,
            'filters' => compact('search', 'type', 'status'),
            'summary' => [
                'areas' => Location::query()->where('location_type', 'AREA')->count(),
                'tambak' => Location::query()->where('location_type', 'TAMBAK')->count(),
                'petak' => Location::query()->where('location_type', 'PETAK')->count(),
                'active' => Location::query()->where('status', 'ACTIVE')->count(),
            ],
        ]);
    }

    public function create(): View
    {
        return view('tambak.create', [
            'parentOptions' => $this->parentOptions(),
            'typeLabels' => UserFacing::LOCATION_TYPES,
        ]);
    }

    public function store(LocationRequest $request, BusinessCodeGenerator $codes): RedirectResponse
    {
        $validated = $request->validated();
        $prefix = match ($validated['location_type']) {
            'AREA' => 'AREA',
            'TAMBAK' => 'TMB',
            'PETAK' => 'PTK',
            default => 'LKS',
        };
        $location = $codes->create(Location::class, 'code', $prefix, [
            ...collect($validated)->except('code')->all(),
            'status' => 'ACTIVE',
        ]);

        return redirect()
            ->route('tambak.show', $location)
            ->with('success', 'Lokasi berhasil ditambahkan.');
    }

    public function show(Location $location): View
    {
        $location->loadMissing('parent.parent');

        $children = $location->children()
            ->withSum([
                'pondStocks as active_stock_sum' => fn (Builder $query) => $query->where('quantity', '>', 0),
            ], 'quantity')
            ->orderBy('name')
            ->get();

        $stockLocationIds = $this->stockLocationIds($location);
        $scopedStocks = PondStock::query()
            ->with([
                'location:id,code,name',
                'batch:id,batch_code,commodity_id,vendor_id,purchase_date,unit_cost,status',
                'batch.commodity:id,code,name,unit',
                'batch.vendor:id,code,name',
            ])
            ->whereIn('location_id', $stockLocationIds)
            ->where('quantity', '>', 0)
            ->orderBy('location_id')
            ->orderBy('batch_id')
            ->get();

        $recentActivities = $this->recentActivities($stockLocationIds);
        $summaryCards = $this->summaryCards($location, $children, $scopedStocks, $recentActivities);
        $aggregatedStocks = $scopedStocks
            ->groupBy(fn (PondStock $stock) => $stock->batch->commodity_id)
            ->map(function (Collection $stocks): array {
                /** @var PondStock $first */
                $first = $stocks->first();

                return [
                    'commodity' => $first->batch->commodity,
                    'quantity' => $stocks->sum(fn (PondStock $stock): float => (float) $stock->quantity),
                    'batch_count' => $stocks->pluck('batch_id')->unique()->count(),
                ];
            })
            ->values();

        return view('tambak.show', [
            'location' => $location,
            'hierarchy' => $location->hierarchy(),
            'children' => $children,
            'currentStocks' => $location->location_type === 'PETAK' ? $scopedStocks : collect(),
            'aggregatedStocks' => $location->location_type !== 'PETAK' ? $aggregatedStocks : collect(),
            'summaryCards' => $summaryCards,
            'recentActivities' => $recentActivities,
            'typeLabels' => UserFacing::LOCATION_TYPES,
        ]);
    }

    public function edit(Location $location): View
    {
        return view('tambak.edit', [
            'location' => $location,
            'parentOptions' => $this->parentOptions($location),
            'typeLabels' => UserFacing::LOCATION_TYPES,
        ]);
    }

    public function update(LocationRequest $request, Location $location): RedirectResponse
    {
        $location->update($request->safe()->except('code'));

        return redirect()
            ->route('tambak.show', $location)
            ->with('success', 'Lokasi berhasil diperbarui.');
    }

    public function status(Location $location): RedirectResponse
    {
        $status = DB::transaction(function () use ($location): string {
            $locationIds = [$location->id, ...$location->descendantIds()];
            sort($locationIds);
            $lockedLocations = Location::query()
                ->whereIn('id', $locationIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();
            $lockedLocation = $lockedLocations->firstWhere('id', $location->id);

            abort_unless($lockedLocation instanceof Location, 404);

            if ($lockedLocation->status === 'ACTIVE') {
                if (PondStock::query()->whereIn('location_id', $locationIds)->where('quantity', '>', 0)->exists()) {
                    return 'HAS_STOCK';
                }

                if ($lockedLocations->where('id', '!=', $lockedLocation->id)->contains('status', 'ACTIVE')) {
                    return 'HAS_ACTIVE_CHILD';
                }
            }

            $lockedLocation->update([
                'status' => $lockedLocation->status === 'ACTIVE' ? 'INACTIVE' : 'ACTIVE',
            ]);

            return $lockedLocation->status;
        });

        if ($status === 'HAS_STOCK') {
            return back()->with('error', 'Lokasi tidak dapat dinonaktifkan karena masih memiliki stok aktif.');
        }

        if ($status === 'HAS_ACTIVE_CHILD') {
            return back()->with('error', 'Lokasi tidak dapat dinonaktifkan karena masih memiliki lokasi anak aktif.');
        }

        $message = $status === 'ACTIVE'
            ? 'Lokasi berhasil diaktifkan.'
            : 'Lokasi berhasil dinonaktifkan.';

        return back()->with('success', $message);
    }

    /**
     * @return Collection<int, Location>
     */
    private function parentOptions(?Location $location = null): Collection
    {
        $excludedIds = $location ? [$location->id, ...$location->descendantIds()] : [];

        return Location::query()
            ->when($excludedIds !== [], fn (Builder $query) => $query->whereNotIn('id', $excludedIds))
            ->orderByRaw("CASE location_type WHEN 'AREA' THEN 1 WHEN 'TAMBAK' THEN 2 WHEN 'PETAK' THEN 3 ELSE 4 END")
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'location_type', 'status']);
    }

    /**
     * @return list<int>
     */
    private function stockLocationIds(Location $location): array
    {
        if ($location->location_type === 'PETAK') {
            return [$location->id];
        }

        if (in_array($location->location_type, ['AREA', 'TAMBAK'], true)) {
            return Location::query()
                ->whereIn('id', $location->descendantIds())
                ->where('location_type', 'PETAK')
                ->pluck('id')
                ->map(fn ($id): int => (int) $id)
                ->all();
        }

        return [$location->id];
    }

    /**
     * @param  Collection<int, Location>  $children
     * @param  Collection<int, PondStock>  $stocks
     * @param  Collection<int, array<string, mixed>>  $activities
     * @return list<array{label: string, value: string, suffix: ?string, icon: string}>
     */
    private function summaryCards(Location $location, Collection $children, Collection $stocks, Collection $activities): array
    {
        $totalStock = $stocks->sum(fn (PondStock $stock): float => (float) $stock->quantity);
        $commodityCount = $stocks->pluck('batch.commodity_id')->filter()->unique()->count();
        $format = fn (float|int $value): string => number_format($value, 0, ',', '.');

        if ($location->location_type === 'PETAK') {
            $latestActivity = $activities->first();

            return [
                ['label' => 'Total Bibit', 'value' => $format($totalStock), 'suffix' => 'ekor', 'icon' => 'seedling'],
                ['label' => 'Jumlah Batch', 'value' => $format($stocks->pluck('batch_id')->unique()->count()), 'suffix' => null, 'icon' => 'package'],
                ['label' => 'Jumlah Komoditas', 'value' => $format($commodityCount), 'suffix' => null, 'icon' => 'waves'],
                ['label' => 'Aktivitas Terakhir', 'value' => $latestActivity ? $latestActivity['date']->locale('id')->translatedFormat('d M Y') : 'Belum ada', 'suffix' => null, 'icon' => 'history'],
            ];
        }

        if ($location->location_type === 'TAMBAK') {
            return [
                ['label' => 'Jumlah Petak', 'value' => $format($children->where('location_type', 'PETAK')->count()), 'suffix' => null, 'icon' => 'map'],
                ['label' => 'Total Bibit', 'value' => $format($totalStock), 'suffix' => 'ekor', 'icon' => 'seedling'],
                ['label' => 'Jumlah Komoditas', 'value' => $format($commodityCount), 'suffix' => null, 'icon' => 'package'],
                ['label' => 'Petak Aktif', 'value' => $format($children->where('location_type', 'PETAK')->where('status', 'ACTIVE')->count()), 'suffix' => null, 'icon' => 'building'],
            ];
        }

        $descendants = Location::query()->whereIn('id', $location->descendantIds())->get();

        return [
            ['label' => 'Jumlah Tambak', 'value' => $format($descendants->where('location_type', 'TAMBAK')->count()), 'suffix' => null, 'icon' => 'building'],
            ['label' => 'Jumlah Petak', 'value' => $format($descendants->where('location_type', 'PETAK')->count()), 'suffix' => null, 'icon' => 'map'],
            ['label' => 'Total Bibit', 'value' => $format($totalStock), 'suffix' => 'ekor', 'icon' => 'seedling'],
            ['label' => 'Lokasi Aktif', 'value' => $format($descendants->where('status', 'ACTIVE')->count()), 'suffix' => null, 'icon' => 'waves'],
        ];
    }

    /**
     * @param  list<int>  $locationIds
     * @return Collection<int, array<string, mixed>>
     */
    private function recentActivities(array $locationIds): Collection
    {
        if ($locationIds === []) {
            return collect();
        }

        $stocking = StockingTransaction::query()
            ->with(['batch:id,batch_code', 'createdBy:id,name'])
            ->whereIn('location_id', $locationIds)
            ->latest('transaction_date')
            ->limit(8)
            ->get()
            ->map(fn (StockingTransaction $item): array => [
                'id' => "stocking-{$item->id}",
                'type' => 'Pembibitan',
                'icon' => 'seedling',
                'description' => $this->formatQuantity($item->quantity)." ekor {$item->batch->batch_code} ditempatkan di lokasi.",
                'date' => $item->transaction_date,
                'user' => $item->createdBy?->name ?? 'Sistem',
            ]);

        $movements = StockMovement::query()
            ->with(['batch:id,batch_code', 'fromLocation:id,name', 'toLocation:id,name', 'createdBy:id,name'])
            ->where(function (Builder $query) use ($locationIds): void {
                $query->whereIn('from_location_id', $locationIds)
                    ->orWhereIn('to_location_id', $locationIds);
            })
            ->latest('transaction_date')
            ->limit(8)
            ->get()
            ->map(function (StockMovement $item) use ($locationIds): array {
                $fromScope = in_array($item->from_location_id, $locationIds, true);
                $toScope = in_array($item->to_location_id, $locationIds, true);
                $type = $fromScope && $toScope ? 'Pemindahan Internal' : ($fromScope ? 'Pemindahan Keluar' : 'Pemindahan Masuk');
                $target = $fromScope ? $item->toLocation->name : $item->fromLocation->name;

                return [
                    'id' => "movement-{$item->id}",
                    'type' => $type,
                    'icon' => 'transfer',
                    'description' => $this->formatQuantity($item->quantity)." ekor {$item->batch->batch_code} ".($fromScope ? 'dipindahkan ke' : 'diterima dari')." {$target}.",
                    'date' => $item->transaction_date,
                    'user' => $item->createdBy?->name ?? 'Sistem',
                ];
            });

        $adjustments = StockAdjustment::query()
            ->with(['batch:id,batch_code', 'createdBy:id,name'])
            ->whereIn('location_id', $locationIds)
            ->latest('transaction_date')
            ->limit(8)
            ->get()
            ->map(function (StockAdjustment $item): array {
                $type = UserFacing::ADJUSTMENT_TYPES[$item->adjustment_type] ?? 'Penyesuaian Stok';

                return [
                    'id' => "adjustment-{$item->id}",
                    'type' => $type,
                    'icon' => 'adjustment',
                    'description' => $this->formatQuantity(abs((float) $item->quantity_change))." ekor {$item->batch->batch_code} dicatat sebagai ".mb_strtolower($type).'.',
                    'date' => $item->transaction_date,
                    'user' => $item->createdBy?->name ?? 'Sistem',
                ];
            });

        $feeding = FeedingTransaction::query()
            ->with(['batch:id,batch_code', 'feedItem:id,name,unit', 'createdBy:id,name'])
            ->whereIn('location_id', $locationIds)
            ->latest('transaction_date')
            ->limit(8)
            ->get()
            ->map(fn (FeedingTransaction $item): array => [
                'id' => "feeding-{$item->id}",
                'type' => 'Pemberian Pakan',
                'icon' => 'feed',
                'description' => $this->formatQuantity($item->feed_quantity)." {$item->feedItem->unit} {$item->feedItem->name} diberikan".($item->batch ? " untuk {$item->batch->batch_code}" : '').'.',
                'date' => $item->transaction_date,
                'user' => $item->createdBy?->name ?? 'Sistem',
            ]);

        return collect()
            ->concat($stocking)
            ->concat($movements)
            ->concat($adjustments)
            ->concat($feeding)
            ->sortByDesc(fn (array $activity) => $activity['date']?->getTimestamp() ?? 0)
            ->take(8)
            ->values();
    }

    private function formatQuantity(float|string $quantity): string
    {
        $value = (float) $quantity;
        $decimals = floor($value) === $value ? 0 : 3;

        return number_format($value, $decimals, ',', '.');
    }
}
