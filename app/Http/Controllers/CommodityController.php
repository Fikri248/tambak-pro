<?php

namespace App\Http\Controllers;

use App\Http\Requests\CommodityRequest;
use App\Models\Commodity;
use App\Models\CommodityBatch;
use App\Models\PondStock;
use App\Services\BusinessCodeGenerator;
use App\Support\PageSize;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CommodityController extends Controller
{
    public function index(Request $request): View
    {
        $search = mb_substr(trim((string) $request->query('search')), 0, 255);
        $category = trim((string) $request->query('category'));
        $status = in_array($request->query('status'), ['ACTIVE', 'INACTIVE'], true)
            ? $request->query('status')
            : null;
        $categories = Commodity::query()
            ->whereNotNull('category')
            ->where('category', '<>', '')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        if ($category !== '' && ! $categories->contains($category)) {
            $category = '';
        }

        $commodities = Commodity::query()
            ->withCount([
                'batches as active_batches_count' => fn (Builder $query) => $query->where('status', 'ACTIVE'),
            ])
            ->addSelect([
                'current_stock' => PondStock::query()
                    ->selectRaw('COALESCE(SUM(pond_stocks.quantity), 0)')
                    ->join('commodity_batches', 'commodity_batches.id', '=', 'pond_stocks.batch_id')
                    ->whereColumn('commodity_batches.commodity_id', 'commodities.id'),
            ])
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('category', 'like', "%{$search}%");
                });
            })
            ->when($category !== '', fn (Builder $query) => $query->where('category', $category))
            ->when($status, fn (Builder $query, string $status) => $query->where('status', $status))
            ->orderBy('name')
            ->paginate(PageSize::resolve($request))
            ->withQueryString();
        $stockUnits = Commodity::query()
            ->whereHas('pondStocks', fn (Builder $query) => $query->where('quantity', '>', 0))
            ->distinct()
            ->pluck('unit');

        return view('commodities.index', [
            'commodities' => $commodities,
            'categories' => $categories,
            'filters' => compact('search', 'category', 'status'),
            'summary' => [
                'total' => Commodity::query()->count(),
                'active' => Commodity::query()->where('status', 'ACTIVE')->count(),
                'active_batches' => CommodityBatch::query()->where('status', 'ACTIVE')->count(),
                'current_stock' => PondStock::query()->sum('quantity'),
                'stock_unit' => $stockUnits->count() === 1 ? $stockUnits->first() : 'unit',
            ],
        ]);
    }

    public function create(): View
    {
        return view('commodities.create');
    }

    public function store(CommodityRequest $request, BusinessCodeGenerator $codes): RedirectResponse
    {
        $commodity = $codes->create(Commodity::class, 'code', 'KMD', [
            ...$request->safe()->except(['code', 'status']),
            'status' => 'ACTIVE',
        ]);

        return redirect()
            ->route('commodities.show', $commodity)
            ->with('success', 'Komoditas berhasil ditambahkan.');
    }

    public function show(Commodity $commodity): View
    {
        $activeBatches = $commodity->batches()
            ->with('vendor:id,code,name')
            ->withSum([
                'pondStocks as current_stock' => fn (Builder $query) => $query->where('quantity', '>', 0),
            ], 'quantity')
            ->where('status', 'ACTIVE')
            ->latest('purchase_date')
            ->latest('id')
            ->get();

        $stockDistribution = PondStock::query()
            ->with([
                'location:id,code,name,status',
                'batch:id,batch_code,commodity_id,unit_cost,status',
            ])
            ->whereHas('batch', fn (Builder $query) => $query->where('commodity_id', $commodity->id))
            ->where('quantity', '>', 0)
            ->orderBy('location_id')
            ->orderBy('batch_id')
            ->get();

        $totalStock = $stockDistribution->sum(fn (PondStock $stock): float => (float) $stock->quantity);
        $totalStockValue = $stockDistribution->sum(
            fn (PondStock $stock): float => (float) $stock->quantity * (float) $stock->batch->unit_cost,
        );

        return view('commodities.show', [
            'commodity' => $commodity,
            'activeBatches' => $activeBatches,
            'stockDistribution' => $stockDistribution,
            'summary' => [
                'total_stock' => $totalStock,
                'active_batches' => $activeBatches->count(),
                'active_locations' => $stockDistribution->pluck('location_id')->unique()->count(),
                'stock_value' => $totalStockValue,
            ],
        ]);
    }

    public function edit(Commodity $commodity): View
    {
        return view('commodities.edit', compact('commodity'));
    }

    public function update(CommodityRequest $request, Commodity $commodity): RedirectResponse
    {
        $commodity->update($request->safe()->except(['code', 'status']));

        return redirect()
            ->route('commodities.show', $commodity)
            ->with('success', 'Komoditas berhasil diperbarui.');
    }

    public function status(Commodity $commodity): RedirectResponse
    {
        $status = DB::transaction(function () use ($commodity): string {
            $lockedCommodity = Commodity::query()->lockForUpdate()->findOrFail($commodity->id);

            if ($lockedCommodity->status === 'ACTIVE') {
                if ($lockedCommodity->pondStocks()->where('quantity', '>', 0)->exists()) {
                    return 'HAS_STOCK';
                }

                if ($lockedCommodity->batches()->where('status', 'ACTIVE')->exists()) {
                    return 'HAS_ACTIVE_BATCH';
                }
            }

            $lockedCommodity->update([
                'status' => $lockedCommodity->status === 'ACTIVE' ? 'INACTIVE' : 'ACTIVE',
            ]);

            return $lockedCommodity->status;
        });

        if ($status === 'HAS_STOCK') {
            return back()->with('error', 'Komoditas tidak dapat dinonaktifkan karena masih memiliki stok aktif.');
        }

        if ($status === 'HAS_ACTIVE_BATCH') {
            return back()->with('error', 'Komoditas tidak dapat dinonaktifkan karena masih memiliki batch aktif.');
        }

        $message = $status === 'ACTIVE'
            ? 'Komoditas berhasil diaktifkan.'
            : 'Komoditas berhasil dinonaktifkan.';

        return back()->with('success', $message);
    }
}
