<?php

namespace App\Http\Controllers;

use App\Exceptions\TransactionMutationBlocked;
use App\Http\Requests\StockMovementRequest;
use App\Http\Requests\TransactionIndexFilterRequest;
use App\Http\Requests\UpdateStockMovementRequest;
use App\Models\AuditLog;
use App\Models\Commodity;
use App\Models\CommodityBatch;
use App\Models\Location;
use App\Models\PondStock;
use App\Models\StockMovement;
use App\Services\Transactions\StockMovementMutationService;
use App\Support\PageSize;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class StockMovementController extends Controller
{
    private const MAX_QUANTITY = '999999999999999.999';

    public function index(TransactionIndexFilterRequest $request): View
    {
        $search = mb_substr(trim((string) $request->query('search')), 0, 255);
        $fromLocationId = $this->validPositiveId($request->query('from_location_id'));
        $toLocationId = $this->validPositiveId($request->query('to_location_id'));
        $commodityId = $this->validPositiveId($request->query('commodity_id'));
        $dateFrom = $request->validated('date_from');
        $dateTo = $request->validated('date_to');

        $movements = StockMovement::query()
            ->with([
                'batch:id,batch_code,commodity_id',
                'batch.commodity:id,code,name,unit',
                'fromLocation:id,code,name',
                'toLocation:id,code,name',
            ])
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('transaction_number', 'like', "%{$search}%")
                        ->orWhereHas('batch', fn (Builder $query) => $query->where('batch_code', 'like', "%{$search}%"))
                        ->orWhereHas('batch.commodity', fn (Builder $query) => $query->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('fromLocation', fn (Builder $query) => $query->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('toLocation', fn (Builder $query) => $query->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('createdBy', fn (Builder $query) => $query->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($fromLocationId, fn (Builder $query, int $id) => $query->where('from_location_id', $id))
            ->when($toLocationId, fn (Builder $query, int $id) => $query->where('to_location_id', $id))
            ->when($commodityId, fn (Builder $query, int $id) => $query->whereHas(
                'batch',
                fn (Builder $query) => $query->where('commodity_id', $id),
            ))
            ->when($dateFrom, fn (Builder $query, string $date) => $query->whereDate('transaction_date', '>=', $date))
            ->when($dateTo, fn (Builder $query, string $date) => $query->whereDate('transaction_date', '<=', $date))
            ->latest('transaction_date')
            ->latest('id')
            ->paginate(PageSize::resolve($request))
            ->withQueryString();

        $units = StockMovement::query()
            ->join('commodity_batches', 'commodity_batches.id', '=', 'stock_movements.batch_id')
            ->join('commodities', 'commodities.id', '=', 'commodity_batches.commodity_id')
            ->distinct()
            ->pluck('commodities.unit');
        $involvedLocations = StockMovement::query()
            ->pluck('from_location_id')
            ->merge(StockMovement::query()->pluck('to_location_id'))
            ->unique()
            ->count();
        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();

        return view('movements.index', [
            'movements' => $movements,
            'locations' => Location::query()->where('location_type', 'PETAK')->orderBy('name')->get(['id', 'name', 'code']),
            'commodities' => Commodity::query()->orderBy('name')->get(['id', 'name', 'code']),
            'filters' => compact('search', 'fromLocationId', 'toLocationId', 'commodityId', 'dateFrom', 'dateTo'),
            'summary' => [
                'total' => StockMovement::query()->count(),
                'quantity' => StockMovement::query()->sum('quantity'),
                'unit' => $units->count() === 1 ? $units->first() : 'unit',
                'this_month' => StockMovement::query()->whereBetween('transaction_date', [$monthStart, $monthEnd])->count(),
                'locations' => $involvedLocations,
            ],
        ]);
    }

    public function create(): View
    {
        $availableStocks = PondStock::query()
            ->with([
                'location:id,parent_id,code,name',
                'location.parent:id,name',
                'batch:id,batch_code,commodity_id',
                'batch.commodity:id,code,name,unit',
            ])
            ->where('quantity', '>', 0)
            ->whereHas('location', fn (Builder $query) => $query
                ->where('location_type', 'PETAK')
                ->where('status', 'ACTIVE'))
            ->orderBy('location_id')
            ->orderBy('batch_id')
            ->get();
        $sourceLocationIds = $availableStocks->pluck('location_id')->unique();
        $sourceLocations = Location::query()
            ->with('parent:id,name')
            ->whereIn('id', $sourceLocationIds)
            ->orderBy('name')
            ->get(['id', 'parent_id', 'code', 'name']);
        $destinations = Location::query()
            ->with('parent:id,name')
            ->where('location_type', 'PETAK')
            ->where('status', 'ACTIVE')
            ->orderBy('name')
            ->get(['id', 'parent_id', 'code', 'name']);

        $batchOptions = $availableStocks
            ->groupBy('location_id')
            ->map(fn (Collection $stocks): array => $stocks->map(fn (PondStock $stock): array => [
                'id' => $stock->batch_id,
                'batch_code' => $stock->batch->batch_code,
                'commodity' => $stock->batch->commodity->name,
                'unit' => $stock->batch->commodity->unit,
                'quantity' => (float) $stock->quantity,
            ])->values()->all())
            ->all();
        $batchIds = $availableStocks->pluck('batch_id')->unique();
        $destinationStocks = PondStock::query()
            ->whereIn('batch_id', $batchIds)
            ->whereIn('location_id', $destinations->pluck('id'))
            ->get(['batch_id', 'location_id', 'quantity'])
            ->groupBy('batch_id')
            ->map(fn (Collection $stocks): array => $stocks->mapWithKeys(
                fn (PondStock $stock): array => [(string) $stock->location_id => (float) $stock->quantity],
            )->all())
            ->all();

        return view('movements.create', compact(
            'sourceLocations',
            'destinations',
            'batchOptions',
            'destinationStocks',
        ));
    }

    public function store(StockMovementRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        try {
            $movement = DB::transaction(function () use ($request, $validated): StockMovement {
                $locationIds = collect([
                    (int) $validated['from_location_id'],
                    (int) $validated['to_location_id'],
                ])->sort()->values();
                $locations = Location::query()
                    ->whereIn('id', $locationIds)
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');
                $source = $locations->get((int) $validated['from_location_id']);
                $destination = $locations->get((int) $validated['to_location_id']);

                if (! $source || $source->location_type !== 'PETAK' || $source->status !== 'ACTIVE') {
                    throw ValidationException::withMessages(['from_location_id' => 'Petak asal tidak valid.']);
                }

                if (! $destination || $destination->location_type !== 'PETAK' || $destination->status !== 'ACTIVE') {
                    throw ValidationException::withMessages(['to_location_id' => 'Petak tujuan tidak valid.']);
                }

                if ($source->is($destination)) {
                    throw ValidationException::withMessages(['to_location_id' => 'Petak asal dan petak tujuan harus berbeda.']);
                }

                $batch = CommodityBatch::query()
                    ->with('commodity:id,name,unit')
                    ->whereKey($validated['batch_id'])
                    ->lockForUpdate()
                    ->first();

                if (! $batch) {
                    throw ValidationException::withMessages(['batch_id' => 'Batch tidak memiliki stok yang dapat dipindahkan dari petak asal.']);
                }

                $stocks = PondStock::query()
                    ->where('batch_id', $batch->id)
                    ->whereIn('location_id', $locationIds)
                    ->orderBy('location_id')
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('location_id');
                $sourceStock = $stocks->get($source->id);
                $destinationStock = $stocks->get($destination->id);
                $quantity = (string) $validated['quantity'];
                $sourceBefore = $sourceStock ? (string) $sourceStock->quantity : '0.000';
                $destinationBefore = $destinationStock ? (string) $destinationStock->quantity : '0.000';

                if (! $sourceStock || bccomp($sourceBefore, '0', 3) !== 1) {
                    throw ValidationException::withMessages(['batch_id' => 'Batch tidak memiliki stok yang dapat dipindahkan dari petak asal.']);
                }

                if (bccomp($quantity, $sourceBefore, 3) === 1) {
                    throw ValidationException::withMessages(['quantity' => 'Stok tidak mencukupi untuk pemindahan.']);
                }

                $sourceAfter = bcsub($sourceBefore, $quantity, 3);
                $destinationAfter = bcadd($destinationBefore, $quantity, 3);

                if (bccomp($destinationAfter, self::MAX_QUANTITY, 3) === 1) {
                    throw ValidationException::withMessages([
                        'quantity' => 'Jumlah yang dipindahkan membuat stok tujuan melebihi kapasitas penyimpanan.',
                    ]);
                }
                $movement = StockMovement::query()->create([
                    'transaction_number' => 'MUT-TMP-'.Str::uuid(),
                    'transaction_date' => Carbon::parse($validated['transaction_date']),
                    'batch_id' => $batch->id,
                    'from_location_id' => $source->id,
                    'to_location_id' => $destination->id,
                    'quantity' => $quantity,
                    'created_by' => $request->user()->id,
                    'notes' => $validated['notes'] ?? null,
                ]);
                $movement->update([
                    'transaction_number' => sprintf('MUT-%06d', $movement->id),
                ]);

                $sourceStock->update(['quantity' => $sourceAfter]);

                if ($destinationStock) {
                    $destinationStock->update(['quantity' => $destinationAfter]);
                } else {
                    PondStock::query()->create([
                        'location_id' => $destination->id,
                        'batch_id' => $batch->id,
                        'quantity' => $destinationAfter,
                    ]);
                }

                AuditLog::query()->create([
                    'user_id' => $request->user()->id,
                    'action' => 'CREATE',
                    'module' => 'STOCK_MOVEMENT',
                    'record_id' => $movement->id,
                    'transaction_number' => $movement->transaction_number,
                    'description' => sprintf(
                        'Pemindahan stok %s %s Batch %s dari %s ke %s',
                        number_format((float) $quantity, floor((float) $quantity) === (float) $quantity ? 0 : 3, ',', '.'),
                        $batch->commodity->unit,
                        $batch->batch_code,
                        $source->name,
                        $destination->name,
                    ),
                    'old_values' => [
                        'batch_id' => $batch->id,
                        'batch_code' => $batch->batch_code,
                        'commodity' => $batch->commodity->name,
                        'source_location_id' => $source->id,
                        'source_quantity' => (float) $sourceBefore,
                        'destination_location_id' => $destination->id,
                        'destination_quantity' => (float) $destinationBefore,
                    ],
                    'new_values' => [
                        'batch_id' => $batch->id,
                        'batch_code' => $batch->batch_code,
                        'commodity' => $batch->commodity->name,
                        'source_location_id' => $source->id,
                        'source_quantity' => (float) $sourceAfter,
                        'destination_location_id' => $destination->id,
                        'destination_quantity' => (float) $destinationAfter,
                        'moved_quantity' => (float) $quantity,
                    ],
                ]);

                return $movement;
            });
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);

            return back()
                ->withInput()
                ->with('error', 'Pemindahan stok gagal dicatat. Silakan coba kembali.');
        }

        return redirect()
            ->route('movements.show', $movement)
            ->with('success', 'Pemindahan stok berhasil dicatat.');
    }

    public function edit(StockMovement $stockMovement): View
    {
        abort_unless(request()->user()?->canAccess('movements.update'), 403);

        return view('movements.edit', $this->movementEditData($stockMovement));
    }

    public function update(
        UpdateStockMovementRequest $request,
        StockMovement $stockMovement,
        StockMovementMutationService $mutations,
    ): RedirectResponse {
        try {
            $updated = $mutations->update(
                $stockMovement,
                $request->validated(),
                (int) $request->user()->id,
            );
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (TransactionMutationBlocked $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        } catch (Throwable $exception) {
            report($exception);

            return back()->withInput()->with('error', 'Pemindahan stok gagal diperbarui. Silakan coba kembali.');
        }

        return redirect()
            ->route('movements.show', $updated)
            ->with('success', 'Pemindahan stok berhasil diperbarui.');
    }

    public function destroy(
        Request $request,
        StockMovement $stockMovement,
        StockMovementMutationService $mutations,
    ): RedirectResponse {
        abort_unless($request->user()?->canAccess('movements.delete'), 403);

        try {
            $transactionNumber = $mutations->delete($stockMovement, (int) $request->user()->id);
        } catch (TransactionMutationBlocked $exception) {
            return back()->with('error', $exception->getMessage());
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('error', 'Pemindahan stok gagal dihapus. Silakan coba kembali.');
        }

        return redirect()
            ->route('movements.index')
            ->with('success', "Pemindahan stok {$transactionNumber} berhasil dihapus dan dampak stoknya dibatalkan.");
    }

    public function show(StockMovement $stockMovement): View
    {
        $stockMovement->load([
            'batch:id,batch_code,commodity_id,status',
            'batch.commodity:id,code,name,unit',
            'fromLocation:id,parent_id,code,name',
            'fromLocation.parent:id,name',
            'toLocation:id,parent_id,code,name',
            'toLocation.parent:id,name',
            'createdBy:id,name',
        ]);
        $currentStocks = PondStock::query()
            ->with('location:id,parent_id,code,name')
            ->where('batch_id', $stockMovement->batch_id)
            ->where('quantity', '>', 0)
            ->orderBy('location_id')
            ->get();

        return view('movements.show', compact('stockMovement', 'currentStocks'));
    }

    /** @return array<string, mixed> */
    private function movementEditData(StockMovement $stockMovement): array
    {
        $stockRows = PondStock::query()
            ->with([
                'location:id,parent_id,code,name,status,location_type',
                'location.parent:id,name',
                'batch:id,batch_code,commodity_id',
                'batch.commodity:id,code,name,unit',
            ])
            ->where(function (Builder $query) use ($stockMovement): void {
                $query->where('quantity', '>', 0)
                    ->orWhere(function (Builder $query) use ($stockMovement): void {
                        $query->where('batch_id', $stockMovement->batch_id)
                            ->whereIn('location_id', [
                                $stockMovement->from_location_id,
                                $stockMovement->to_location_id,
                            ]);
                    });
            })
            ->orderBy('batch_id')
            ->orderBy('location_id')
            ->get();

        foreach ($stockRows as $stock) {
            if ($stock->batch_id !== $stockMovement->batch_id) {
                continue;
            }

            if ($stock->location_id === $stockMovement->from_location_id) {
                $stock->quantity = bcadd((string) $stock->quantity, (string) $stockMovement->quantity, 3);
            }

            if ($stock->location_id === $stockMovement->to_location_id) {
                $reversed = bcsub((string) $stock->quantity, (string) $stockMovement->quantity, 3);
                $stock->quantity = bccomp($reversed, '0', 3) === -1 ? '0.000' : $reversed;
            }
        }

        $availableStocks = $stockRows->filter(fn (PondStock $stock): bool => bccomp((string) $stock->quantity, '0', 3) === 1);
        $sourceLocationIds = $availableStocks->pluck('location_id')->unique()->push($stockMovement->from_location_id)->unique();
        $sourceLocations = Location::query()
            ->with('parent:id,name')
            ->whereIn('id', $sourceLocationIds)
            ->where(fn (Builder $query) => $query
                ->where('status', 'ACTIVE')
                ->orWhere('id', $stockMovement->from_location_id))
            ->orderBy('name')
            ->get(['id', 'parent_id', 'code', 'name']);
        $destinations = Location::query()
            ->with('parent:id,name')
            ->where('location_type', 'PETAK')
            ->where(fn (Builder $query) => $query
                ->where('status', 'ACTIVE')
                ->orWhere('id', $stockMovement->to_location_id))
            ->orderBy('name')
            ->get(['id', 'parent_id', 'code', 'name']);
        $batchOptions = $availableStocks
            ->groupBy('location_id')
            ->map(fn (Collection $stocks): array => $stocks->map(fn (PondStock $stock): array => [
                'id' => $stock->batch_id,
                'batch_code' => $stock->batch->batch_code,
                'commodity' => $stock->batch->commodity->name,
                'unit' => $stock->batch->commodity->unit,
                'quantity' => (float) $stock->quantity,
            ])->values()->all())
            ->all();
        $batchIds = $availableStocks->pluck('batch_id')->unique();
        $destinationStocks = $stockRows
            ->whereIn('batch_id', $batchIds)
            ->whereIn('location_id', $destinations->pluck('id'))
            ->groupBy('batch_id')
            ->map(fn (Collection $stocks): array => $stocks->mapWithKeys(
                fn (PondStock $stock): array => [(string) $stock->location_id => (float) $stock->quantity],
            )->all())
            ->all();

        return compact(
            'stockMovement',
            'sourceLocations',
            'destinations',
            'batchOptions',
            'destinationStocks',
        );
    }

    private function validPositiveId(mixed $value): ?int
    {
        return filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: null;
    }
}
