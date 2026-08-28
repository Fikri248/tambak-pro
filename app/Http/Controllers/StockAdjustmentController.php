<?php

namespace App\Http\Controllers;

use App\Http\Requests\StockAdjustmentRequest;
use App\Http\Requests\TransactionIndexFilterRequest;
use App\Http\Requests\UpdateStockAdjustmentRequest;
use App\Models\AuditLog;
use App\Models\Commodity;
use App\Models\CommodityBatch;
use App\Models\FeedingTransaction;
use App\Models\Location;
use App\Models\PondStock;
use App\Models\StockAdjustment;
use App\Models\StockMovement;
use App\Support\PageSize;
use App\Support\UserFacing;
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

class StockAdjustmentController extends Controller
{
    private const MAX_QUANTITY = '999999999999999.999';

    public function index(TransactionIndexFilterRequest $request): View
    {
        $search = mb_substr(trim((string) $request->query('search')), 0, 255);
        $type = in_array($request->query('type'), array_keys(UserFacing::ADJUSTMENT_TYPES), true)
            ? $request->query('type')
            : null;
        $locationId = $this->validPositiveId($request->query('location_id'));
        $commodityId = $this->validPositiveId($request->query('commodity_id'));
        $dateFrom = $request->validated('date_from');
        $dateTo = $request->validated('date_to');

        $adjustments = StockAdjustment::query()
            ->with([
                'location:id,code,name',
                'batch:id,batch_code,commodity_id',
                'batch.commodity:id,code,name,unit',
            ])
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('transaction_number', 'like', "%{$search}%")
                        ->orWhere('reason', 'like', "%{$search}%")
                        ->orWhereHas('batch', fn (Builder $query) => $query->where('batch_code', 'like', "%{$search}%"))
                        ->orWhereHas('batch.commodity', fn (Builder $query) => $query->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('location', fn (Builder $query) => $query->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('createdBy', fn (Builder $query) => $query->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($type, fn (Builder $query, string $type) => $query->where('adjustment_type', $type))
            ->when($locationId, fn (Builder $query, int $id) => $query->where('location_id', $id))
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

        $units = StockAdjustment::query()
            ->join('commodity_batches', 'commodity_batches.id', '=', 'stock_adjustments.batch_id')
            ->join('commodities', 'commodities.id', '=', 'commodity_batches.commodity_id')
            ->distinct()
            ->pluck('commodities.unit');

        return view('adjustments.index', [
            'adjustments' => $adjustments,
            'typeLabels' => UserFacing::ADJUSTMENT_TYPES,
            'locations' => Location::query()->where('location_type', 'PETAK')->orderBy('name')->get(['id', 'name', 'code']),
            'commodities' => Commodity::query()->orderBy('name')->get(['id', 'name', 'code']),
            'filters' => compact('search', 'type', 'locationId', 'commodityId', 'dateFrom', 'dateTo'),
            'summary' => [
                'total' => StockAdjustment::query()->count(),
                'mortality' => abs((float) StockAdjustment::query()->where('adjustment_type', 'MORTALITY')->sum('quantity_change')),
                'loss' => abs((float) StockAdjustment::query()->where('adjustment_type', 'LOSS')->sum('quantity_change')),
                'corrections' => StockAdjustment::query()->whereIn('adjustment_type', ['CORRECTION_IN', 'CORRECTION_OUT'])->count(),
                'unit' => $units->count() === 1 ? $units->first() : 'unit',
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
        $locations = Location::query()
            ->with('parent:id,name')
            ->whereIn('id', $availableStocks->pluck('location_id')->unique())
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

        return view('adjustments.create', [
            'locations' => $locations,
            'batchOptions' => $batchOptions,
            'typeLabels' => UserFacing::ADJUSTMENT_TYPES,
        ]);
    }

    public function store(StockAdjustmentRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        try {
            $adjustment = DB::transaction(function () use ($request, $validated): StockAdjustment {
                $location = Location::query()
                    ->whereKey($validated['location_id'])
                    ->lockForUpdate()
                    ->first();

                if (! $location || $location->location_type !== 'PETAK' || $location->status !== 'ACTIVE') {
                    throw ValidationException::withMessages(['location_id' => 'Petak yang dipilih tidak valid.']);
                }

                $batch = CommodityBatch::query()
                    ->with('commodity:id,name,unit')
                    ->whereKey($validated['batch_id'])
                    ->lockForUpdate()
                    ->first();

                if (! $batch) {
                    throw ValidationException::withMessages(['batch_id' => 'Batch tidak tersedia pada petak tersebut.']);
                }

                $stock = PondStock::query()
                    ->where('location_id', $location->id)
                    ->where('batch_id', $batch->id)
                    ->lockForUpdate()
                    ->first();

                if (! $stock || bccomp((string) $stock->quantity, '0', 3) !== 1) {
                    throw ValidationException::withMessages(['batch_id' => 'Batch tidak tersedia pada petak tersebut.']);
                }

                $quantity = (string) $validated['quantity'];
                $quantityBefore = (string) $stock->quantity;
                $isIncrease = $this->isIncrease($validated['adjustment_type'], $validated['direction'] ?? null);
                $quantityChange = $isIncrease ? $quantity : '-'.$quantity;
                $quantityAfter = bcadd($quantityBefore, $quantityChange, 3);

                if (bccomp($quantityAfter, '0', 3) === -1) {
                    throw ValidationException::withMessages(['quantity' => 'Stok tidak mencukupi untuk perubahan ini.']);
                }

                if (bccomp($quantityAfter, self::MAX_QUANTITY, 3) === 1) {
                    throw ValidationException::withMessages([
                        'quantity' => 'Perubahan membuat stok melebihi kapasitas penyimpanan.',
                    ]);
                }

                $adjustment = StockAdjustment::query()->create([
                    'transaction_number' => 'ADJ-TMP-'.Str::uuid(),
                    'transaction_date' => Carbon::parse($validated['transaction_date']),
                    'location_id' => $location->id,
                    'batch_id' => $batch->id,
                    'adjustment_type' => $validated['adjustment_type'],
                    'quantity_change' => $quantityChange,
                    'quantity_before' => $quantityBefore,
                    'quantity_after' => $quantityAfter,
                    'reason' => $validated['reason'],
                    'created_by' => $request->user()->id,
                ]);
                $adjustment->update([
                    'transaction_number' => sprintf('ADJ-%06d', $adjustment->id),
                ]);

                $stock->update(['quantity' => $quantityAfter]);

                $typeLabel = UserFacing::ADJUSTMENT_TYPES[$adjustment->adjustment_type];
                AuditLog::query()->create([
                    'user_id' => $request->user()->id,
                    'action' => 'CREATE',
                    'module' => 'STOCK_ADJUSTMENT',
                    'record_id' => $adjustment->id,
                    'transaction_number' => $adjustment->transaction_number,
                    'description' => sprintf(
                        '%s %s %s %s di %s',
                        $typeLabel,
                        number_format((float) $quantity, floor((float) $quantity) === (float) $quantity ? 0 : 3, ',', '.'),
                        $batch->commodity->unit,
                        $batch->batch_code,
                        $location->name,
                    ),
                    'old_values' => [
                        'location_id' => $location->id,
                        'batch_id' => $batch->id,
                        'batch_code' => $batch->batch_code,
                        'commodity' => $batch->commodity->name,
                        'quantity' => (float) $quantityBefore,
                    ],
                    'new_values' => [
                        'location_id' => $location->id,
                        'batch_id' => $batch->id,
                        'batch_code' => $batch->batch_code,
                        'commodity' => $batch->commodity->name,
                        'quantity_change' => (float) $quantityChange,
                        'quantity' => (float) $quantityAfter,
                        'adjustment_type' => $adjustment->adjustment_type,
                        'reason' => $adjustment->reason,
                    ],
                ]);

                return $adjustment;
            });
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);

            return back()
                ->withInput()
                ->with('error', 'Perubahan jumlah gagal dicatat. Silakan coba kembali.');
        }

        return redirect()
            ->route('adjustments.show', $adjustment)
            ->with('success', 'Perubahan jumlah berhasil dicatat.');
    }

    public function edit(StockAdjustment $stockAdjustment): View
    {
        $stockAdjustment->load([
            'location:id,parent_id,code,name',
            'location.parent:id,name',
            'batch:id,batch_code,commodity_id',
            'batch.commodity:id,code,name,unit',
        ]);

        return view('adjustments.edit', [
            'stockAdjustment' => $stockAdjustment,
            'typeLabels' => UserFacing::ADJUSTMENT_TYPES,
        ]);
    }

    public function update(UpdateStockAdjustmentRequest $request, StockAdjustment $stockAdjustment): RedirectResponse
    {
        $validated = $request->validated();

        try {
            DB::transaction(function () use ($request, $stockAdjustment, $validated): void {
                $lockedAdjustment = StockAdjustment::query()
                    ->whereKey($stockAdjustment->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ((int) $validated['location_id'] !== $lockedAdjustment->location_id
                    || (int) $validated['batch_id'] !== $lockedAdjustment->batch_id) {
                    throw ValidationException::withMessages([
                        'transaction' => 'Batch dan Petak transaksi tidak dapat diubah untuk menjaga konsistensi riwayat stok.',
                    ]);
                }

                $location = Location::query()->whereKey($lockedAdjustment->location_id)->lockForUpdate()->firstOrFail();
                $batch = CommodityBatch::query()
                    ->with('commodity:id,name,unit')
                    ->whereKey($lockedAdjustment->batch_id)
                    ->lockForUpdate()
                    ->firstOrFail();
                $stock = PondStock::query()
                    ->where('location_id', $lockedAdjustment->location_id)
                    ->where('batch_id', $lockedAdjustment->batch_id)
                    ->lockForUpdate()
                    ->first();

                if ($this->hasLaterActivity($lockedAdjustment)) {
                    throw ValidationException::withMessages([
                        'transaction' => 'Transaksi ini sudah memiliki aktivitas lanjutan sehingga perubahan stok tidak dapat diedit.',
                    ]);
                }

                if (! $stock || bccomp((string) $stock->quantity, (string) $lockedAdjustment->quantity_after, 3) !== 0) {
                    throw ValidationException::withMessages([
                        'transaction' => 'Perubahan jumlah tidak dapat diedit karena posisi stok tidak lagi sama dengan catatan transaksi.',
                    ]);
                }

                $quantityBefore = bcsub((string) $stock->quantity, (string) $lockedAdjustment->quantity_change, 3);
                $quantity = (string) $validated['quantity'];
                $isIncrease = $this->isIncrease($validated['adjustment_type'], $validated['direction'] ?? null);
                $quantityChange = $isIncrease ? $quantity : '-'.$quantity;
                $quantityAfter = bcadd($quantityBefore, $quantityChange, 3);

                if (bccomp($quantityAfter, '0', 3) === -1) {
                    throw ValidationException::withMessages(['quantity' => 'Stok tidak mencukupi untuk perubahan ini.']);
                }

                if (bccomp($quantityAfter, self::MAX_QUANTITY, 3) === 1) {
                    throw ValidationException::withMessages(['quantity' => 'Perubahan membuat stok melebihi kapasitas penyimpanan.']);
                }

                $oldValues = [
                    'transaction_date' => $lockedAdjustment->transaction_date->toDateTimeString(),
                    'location_id' => $lockedAdjustment->location_id,
                    'batch_id' => $lockedAdjustment->batch_id,
                    'adjustment_type' => $lockedAdjustment->adjustment_type,
                    'quantity_before' => (string) $lockedAdjustment->quantity_before,
                    'quantity_change' => (string) $lockedAdjustment->quantity_change,
                    'quantity_after' => (string) $lockedAdjustment->quantity_after,
                    'reason' => $lockedAdjustment->reason,
                ];

                $lockedAdjustment->update([
                    'transaction_date' => Carbon::parse($validated['transaction_date']),
                    'adjustment_type' => $validated['adjustment_type'],
                    'quantity_before' => $quantityBefore,
                    'quantity_change' => $quantityChange,
                    'quantity_after' => $quantityAfter,
                    'reason' => $validated['reason'],
                ]);
                $stock->update(['quantity' => $quantityAfter]);

                AuditLog::query()->create([
                    'user_id' => $request->user()->id,
                    'action' => 'UPDATE',
                    'module' => 'STOCK_ADJUSTMENT',
                    'record_id' => $lockedAdjustment->id,
                    'transaction_number' => $lockedAdjustment->transaction_number,
                    'description' => "Perubahan jumlah {$lockedAdjustment->transaction_number} diperbarui di {$location->name}",
                    'old_values' => $oldValues,
                    'new_values' => [
                        'transaction_date' => $lockedAdjustment->transaction_date->toDateTimeString(),
                        'location_id' => $lockedAdjustment->location_id,
                        'location' => $location->name,
                        'batch_id' => $lockedAdjustment->batch_id,
                        'batch_code' => $batch->batch_code,
                        'adjustment_type' => $lockedAdjustment->adjustment_type,
                        'quantity_before' => (string) $lockedAdjustment->quantity_before,
                        'quantity_change' => (string) $lockedAdjustment->quantity_change,
                        'quantity_after' => (string) $lockedAdjustment->quantity_after,
                        'reason' => $lockedAdjustment->reason,
                    ],
                ]);
            });
        } catch (ValidationException $exception) {
            $transactionError = $exception->errors()['transaction'][0] ?? null;

            if ($transactionError) {
                return back()
                    ->withInput()
                    ->withErrors(['transaction' => $transactionError])
                    ->with('error', $transactionError);
            }

            throw $exception;
        } catch (Throwable $exception) {
            report($exception);

            return back()->withInput()->with('error', 'Perubahan jumlah gagal diperbarui. Silakan coba kembali.');
        }

        return redirect()
            ->route('adjustments.show', $stockAdjustment)
            ->with('success', 'Perubahan jumlah berhasil diperbarui.');
    }

    public function destroy(Request $request, StockAdjustment $stockAdjustment): RedirectResponse
    {
        try {
            DB::transaction(function () use ($request, $stockAdjustment): void {
                $lockedAdjustment = StockAdjustment::query()
                    ->whereKey($stockAdjustment->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                Location::query()->whereKey($lockedAdjustment->location_id)->lockForUpdate()->firstOrFail();
                CommodityBatch::query()->whereKey($lockedAdjustment->batch_id)->lockForUpdate()->firstOrFail();
                $stock = PondStock::query()
                    ->where('location_id', $lockedAdjustment->location_id)
                    ->where('batch_id', $lockedAdjustment->batch_id)
                    ->lockForUpdate()
                    ->first();

                if ($this->hasLaterActivity($lockedAdjustment)) {
                    throw ValidationException::withMessages([
                        'transaction' => 'Transaksi Perubahan Jumlah ini tidak dapat dihapus karena sudah memiliki aktivitas lanjutan.',
                    ]);
                }

                if (! $stock || bccomp((string) $stock->quantity, (string) $lockedAdjustment->quantity_after, 3) !== 0) {
                    throw ValidationException::withMessages([
                        'transaction' => 'Transaksi tidak dapat dihapus karena posisi stok tidak lagi sama dengan catatan perubahan.',
                    ]);
                }

                $restoredQuantity = bcsub((string) $stock->quantity, (string) $lockedAdjustment->quantity_change, 3);

                if (bccomp($restoredQuantity, '0', 3) === -1 || bccomp($restoredQuantity, self::MAX_QUANTITY, 3) === 1) {
                    throw ValidationException::withMessages([
                        'transaction' => 'Transaksi tidak dapat dihapus karena pemulihan stok menghasilkan jumlah yang tidak valid.',
                    ]);
                }

                AuditLog::query()->create([
                    'user_id' => $request->user()->id,
                    'action' => 'DELETE',
                    'module' => 'STOCK_ADJUSTMENT',
                    'record_id' => $lockedAdjustment->id,
                    'transaction_number' => $lockedAdjustment->transaction_number,
                    'description' => "Perubahan jumlah {$lockedAdjustment->transaction_number} dihapus dan dampak stok dibatalkan",
                    'old_values' => [
                        'transaction_date' => $lockedAdjustment->transaction_date->toDateTimeString(),
                        'location_id' => $lockedAdjustment->location_id,
                        'batch_id' => $lockedAdjustment->batch_id,
                        'adjustment_type' => $lockedAdjustment->adjustment_type,
                        'quantity_before' => (string) $lockedAdjustment->quantity_before,
                        'quantity_change' => (string) $lockedAdjustment->quantity_change,
                        'quantity_after' => (string) $lockedAdjustment->quantity_after,
                        'reason' => $lockedAdjustment->reason,
                        'created_by' => $lockedAdjustment->created_by,
                        'created_at' => $lockedAdjustment->created_at?->toDateTimeString(),
                    ],
                    'new_values' => [
                        'restored_stock_quantity' => $restoredQuantity,
                    ],
                ]);

                $stock->update(['quantity' => $restoredQuantity]);
                $lockedAdjustment->delete();
            });
        } catch (ValidationException $exception) {
            return back()->with('error', collect($exception->errors())->flatten()->first());
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('error', 'Perubahan jumlah gagal dihapus. Silakan coba kembali.');
        }

        return redirect()->route('adjustments.index')->with('success', 'Perubahan jumlah berhasil dihapus dan stok dipulihkan.');
    }

    public function show(StockAdjustment $stockAdjustment): View
    {
        $stockAdjustment->load([
            'location:id,parent_id,code,name',
            'location.parent:id,name',
            'batch:id,batch_code,commodity_id,status',
            'batch.commodity:id,code,name,unit',
            'createdBy:id,name',
        ]);
        $currentStocks = PondStock::query()
            ->with('location:id,parent_id,code,name')
            ->where('batch_id', $stockAdjustment->batch_id)
            ->where('quantity', '>', 0)
            ->orderBy('location_id')
            ->get();

        return view('adjustments.show', [
            'stockAdjustment' => $stockAdjustment,
            'currentStocks' => $currentStocks,
            'typeLabels' => UserFacing::ADJUSTMENT_TYPES,
        ]);
    }

    private function isIncrease(string $type, ?string $direction): bool
    {
        return $type === 'CORRECTION_IN' || ($type === 'OTHER' && $direction === 'IN');
    }

    private function hasLaterActivity(StockAdjustment $adjustment): bool
    {
        $laterThan = function (Builder $query) use ($adjustment): void {
            $query->where('transaction_date', '>=', $adjustment->transaction_date)
                ->orWhere('created_at', '>=', $adjustment->created_at);
        };

        $laterMovement = StockMovement::query()
            ->where('batch_id', $adjustment->batch_id)
            ->where(function (Builder $query) use ($adjustment): void {
                $query->where('from_location_id', $adjustment->location_id)
                    ->orWhere('to_location_id', $adjustment->location_id);
            })
            ->where(fn (Builder $query) => $laterThan($query))
            ->exists();
        $laterAdjustment = StockAdjustment::query()
            ->whereKeyNot($adjustment->id)
            ->where('batch_id', $adjustment->batch_id)
            ->where('location_id', $adjustment->location_id)
            ->where(fn (Builder $query) => $laterThan($query))
            ->exists();
        $laterFeeding = FeedingTransaction::query()
            ->where('location_id', $adjustment->location_id)
            ->where(function (Builder $query) use ($adjustment): void {
                $query->where('batch_id', $adjustment->batch_id)->orWhereNull('batch_id');
            })
            ->where(fn (Builder $query) => $laterThan($query))
            ->exists();

        return $laterMovement || $laterAdjustment || $laterFeeding;
    }

    private function validPositiveId(mixed $value): ?int
    {
        return filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: null;
    }
}
