<?php

namespace App\Http\Controllers;

use App\Http\Requests\FeedingTransactionRequest;
use App\Http\Requests\TransactionIndexFilterRequest;
use App\Http\Requests\UpdateFeedingTransactionRequest;
use App\Models\AuditLog;
use App\Models\CommodityBatch;
use App\Models\FeedingTransaction;
use App\Models\FeedItem;
use App\Models\Location;
use App\Models\PondStock;
use App\Models\Vendor;
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

class FeedingTransactionController extends Controller
{
    private const MAX_QUANTITY = '999999999999999.999';

    private const MAX_TOTAL_COST = '9999999999999999.99';

    public function index(TransactionIndexFilterRequest $request): View
    {
        $search = mb_substr(trim((string) $request->query('search')), 0, 255);
        $type = in_array($request->query('type'), array_keys(UserFacing::FEED_ITEM_TYPES), true)
            ? $request->query('type')
            : null;
        $locationId = $this->validPositiveId($request->query('location_id'));
        $feedItemId = $this->validPositiveId($request->query('feed_item_id'));
        $dateFrom = $request->validated('date_from');
        $dateTo = $request->validated('date_to');

        $transactions = FeedingTransaction::query()
            ->with([
                'location:id,code,name',
                'batch:id,batch_code,commodity_id',
                'batch.commodity:id,code,name',
                'feedItem:id,code,name,item_type,unit',
                'vendor:id,code,name',
            ])
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('transaction_number', 'like', "%{$search}%")
                        ->orWhereHas('feedItem', fn (Builder $query) => $query->where(
                            fn (Builder $query) => $query
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('code', 'like', "%{$search}%"),
                        ))
                        ->orWhereHas('location', fn (Builder $query) => $query->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('batch', fn (Builder $query) => $query->where('batch_code', 'like', "%{$search}%"))
                        ->orWhereHas('batch.commodity', fn (Builder $query) => $query->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('vendor', fn (Builder $query) => $query->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('createdBy', fn (Builder $query) => $query->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($type, fn (Builder $query, string $type) => $query->whereHas(
                'feedItem',
                fn (Builder $query) => $query->where('item_type', $type),
            ))
            ->when($locationId, fn (Builder $query, int $id) => $query->where('location_id', $id))
            ->when($feedItemId, fn (Builder $query, int $id) => $query->where('feed_item_id', $id))
            ->when($dateFrom, fn (Builder $query, string $date) => $query->whereDate('transaction_date', '>=', $date))
            ->when($dateTo, fn (Builder $query, string $date) => $query->whereDate('transaction_date', '<=', $date))
            ->latest('transaction_date')
            ->latest('id')
            ->paginate(PageSize::resolve($request))
            ->withQueryString();

        $now = Carbon::now(config('app.timezone'));

        return view('feeding.index', [
            'transactions' => $transactions,
            'typeLabels' => UserFacing::FEED_ITEM_TYPES,
            'locations' => Location::query()->where('location_type', 'PETAK')->orderBy('name')->get(['id', 'code', 'name']),
            'feedItems' => FeedItem::query()->orderBy('name')->get(['id', 'code', 'name']),
            'filters' => compact('search', 'type', 'locationId', 'feedItemId', 'dateFrom', 'dateTo'),
            'summary' => [
                'total' => FeedingTransaction::query()->count(),
                'cost' => (float) FeedingTransaction::query()->sum('total_cost'),
                'items' => FeedingTransaction::query()->distinct()->count('feed_item_id'),
                'currentMonth' => FeedingTransaction::query()
                    ->whereBetween('transaction_date', [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()])
                    ->count(),
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
            ->orderBy('id')
            ->get();
        $locations = Location::query()
            ->with('parent:id,name')
            ->whereIn('id', $availableStocks->pluck('location_id')->unique())
            ->orderBy('name')
            ->get(['id', 'parent_id', 'code', 'name']);
        $scopeOptions = $availableStocks
            ->groupBy('location_id')
            ->map(function (Collection $stocks): array {
                $units = $stocks->pluck('batch.commodity.unit')->unique();

                return [
                    'total' => (float) $stocks->sum('quantity'),
                    'unit' => $units->count() === 1 ? $units->first() : 'unit',
                    'batches' => $stocks->map(fn (PondStock $stock): array => [
                        'id' => $stock->batch_id,
                        'batch_code' => $stock->batch->batch_code,
                        'commodity' => $stock->batch->commodity->name,
                        'unit' => $stock->batch->commodity->unit,
                        'quantity' => (float) $stock->quantity,
                    ])->values()->all(),
                ];
            })
            ->all();
        $feedItems = FeedItem::query()
            ->with('defaultVendor:id,status,vendor_type')
            ->where('status', 'ACTIVE')
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'item_type', 'unit', 'default_price', 'default_vendor_id']);
        $itemOptions = $feedItems->mapWithKeys(function (FeedItem $item): array {
            $defaultVendor = $item->defaultVendor;
            $validDefaultVendor = $defaultVendor
                && $defaultVendor->status === 'ACTIVE'
                && in_array($defaultVendor->vendor_type, ['FEED', 'MULTIPLE'], true);

            return [(string) $item->id => [
                'id' => $item->id,
                'code' => $item->code,
                'name' => $item->name,
                'type' => $item->item_type,
                'type_label' => UserFacing::FEED_ITEM_TYPES[$item->item_type],
                'unit' => $item->unit,
                'default_price' => (float) $item->default_price,
                'default_vendor_id' => $validDefaultVendor ? $item->default_vendor_id : null,
            ]];
        })->all();

        return view('feeding.create', [
            'locations' => $locations,
            'scopeOptions' => $scopeOptions,
            'feedItems' => $feedItems,
            'itemOptions' => $itemOptions,
            'vendors' => Vendor::query()
                ->where('status', 'ACTIVE')
                ->whereIn('vendor_type', ['FEED', 'MULTIPLE'])
                ->orderBy('name')
                ->get(['id', 'code', 'name', 'vendor_type']),
            'typeLabels' => UserFacing::FEED_ITEM_TYPES,
            'vendorTypeLabels' => UserFacing::VENDOR_TYPES,
        ]);
    }

    public function store(FeedingTransactionRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        try {
            $feedingTransaction = DB::transaction(function () use ($request, $validated): FeedingTransaction {
                $location = Location::query()
                    ->whereKey($validated['location_id'])
                    ->lockForUpdate()
                    ->first();

                if (! $location || $location->location_type !== 'PETAK' || $location->status !== 'ACTIVE') {
                    throw ValidationException::withMessages(['location_id' => 'Petak yang dipilih tidak valid.']);
                }

                $feedItem = FeedItem::query()
                    ->whereKey($validated['feed_item_id'])
                    ->lockForUpdate()
                    ->first();

                if (! $feedItem || $feedItem->status !== 'ACTIVE') {
                    throw ValidationException::withMessages(['feed_item_id' => 'Pakan, nutrisi, atau obat yang dipilih tidak aktif.']);
                }

                $vendor = null;

                if ($validated['vendor_id'] ?? null) {
                    $vendor = Vendor::query()
                        ->whereKey($validated['vendor_id'])
                        ->lockForUpdate()
                        ->first();

                    if (! $vendor || $vendor->status !== 'ACTIVE' || ! in_array($vendor->vendor_type, ['FEED', 'MULTIPLE'], true)) {
                        throw ValidationException::withMessages(['vendor_id' => 'Vendor yang dipilih tidak valid.']);
                    }
                }

                $batch = null;

                if ($validated['batch_id'] ?? null) {
                    $batch = CommodityBatch::query()
                        ->with('commodity:id,name')
                        ->whereKey($validated['batch_id'])
                        ->lockForUpdate()
                        ->first();

                    $stock = $batch
                        ? PondStock::query()
                            ->where('location_id', $location->id)
                            ->where('batch_id', $batch->id)
                            ->lockForUpdate()
                            ->first()
                        : null;

                    if (! $batch || ! $stock || (float) $stock->quantity <= 0) {
                        throw ValidationException::withMessages(['batch_id' => 'Batch tidak tersedia pada petak tersebut.']);
                    }

                    $snapshot = (string) $stock->quantity;
                } else {
                    $stocks = PondStock::query()
                        ->where('location_id', $location->id)
                        ->where('quantity', '>', 0)
                        ->orderBy('id')
                        ->lockForUpdate()
                        ->get();

                    if ($stocks->isEmpty()) {
                        throw ValidationException::withMessages(['location_id' => 'Petak tidak memiliki stok aktif.']);
                    }

                    $snapshot = $stocks->reduce(
                        fn (string $total, PondStock $stock): string => bcadd($total, (string) $stock->quantity, 3),
                        '0.000',
                    );
                }

                if (bccomp($snapshot, self::MAX_QUANTITY, 3) === 1) {
                    throw ValidationException::withMessages([
                        'location_id' => 'Total stok petak melebihi kapasitas pencatatan.',
                    ]);
                }

                $feedQuantity = (string) $validated['feed_quantity'];
                $unitCost = (string) $validated['unit_cost'];
                $totalCost = bcadd(bcmul($feedQuantity, $unitCost, 3), '0.005', 2);

                if (bccomp($totalCost, self::MAX_TOTAL_COST, 2) === 1) {
                    throw ValidationException::withMessages([
                        'unit_cost' => 'Total biaya hasil perhitungan melebihi kapasitas penyimpanan.',
                    ]);
                }
                $feedingTransaction = FeedingTransaction::query()->create([
                    'transaction_number' => 'FDG-TMP-'.Str::uuid(),
                    'transaction_date' => Carbon::parse($validated['transaction_date']),
                    'location_id' => $location->id,
                    'batch_id' => $batch?->id,
                    'feed_item_id' => $feedItem->id,
                    'vendor_id' => $vendor?->id,
                    'stock_quantity_snapshot' => $snapshot,
                    'feed_quantity' => $feedQuantity,
                    'unit_cost' => $unitCost,
                    'total_cost' => $totalCost,
                    'created_by' => $request->user()->id,
                    'notes' => $validated['notes'] ?? null,
                ]);
                $feedingTransaction->update([
                    'transaction_number' => sprintf('FDG-%06d', $feedingTransaction->id),
                ]);

                $quantity = number_format((float) $feedQuantity, floor((float) $feedQuantity) === (float) $feedQuantity ? 0 : 3, ',', '.');
                AuditLog::query()->create([
                    'user_id' => $request->user()->id,
                    'action' => 'CREATE',
                    'module' => 'FEEDING_TRANSACTION',
                    'record_id' => $feedingTransaction->id,
                    'transaction_number' => $feedingTransaction->transaction_number,
                    'description' => $batch
                        ? "Pemberian {$quantity} {$feedItem->unit} {$feedItem->name} di {$location->name} untuk {$batch->batch_code}"
                        : "Penggunaan {$quantity} {$feedItem->unit} {$feedItem->name} di {$location->name}",
                    'new_values' => [
                        'location_id' => $location->id,
                        'location' => $location->name,
                        'batch_id' => $batch?->id,
                        'batch' => $batch?->batch_code,
                        'feed_item_id' => $feedItem->id,
                        'feed_item' => $feedItem->name,
                        'vendor_id' => $vendor?->id,
                        'vendor' => $vendor?->name,
                        'feed_quantity' => (float) $feedQuantity,
                        'unit_cost' => (float) $unitCost,
                        'total_cost' => (float) $totalCost,
                        'stock_quantity_snapshot' => (float) $snapshot,
                    ],
                ]);

                return $feedingTransaction;
            });
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);

            return back()
                ->withInput()
                ->with('error', 'Pemberian pakan gagal dicatat. Silakan coba kembali.');
        }

        return redirect()
            ->route('feeding.show', $feedingTransaction)
            ->with('success', 'Pemberian pakan berhasil dicatat.');
    }

    public function edit(FeedingTransaction $feedingTransaction): View
    {
        $feedingTransaction->load([
            'location:id,parent_id,code,name',
            'location.parent:id,name',
            'batch:id,batch_code,commodity_id',
            'batch.commodity:id,name,unit',
            'feedItem:id,code,name,item_type,unit',
            'vendor:id,code,name,vendor_type',
        ]);

        $locations = Location::query()
            ->with('parent:id,name')
            ->where('location_type', 'PETAK')
            ->where(function (Builder $query) use ($feedingTransaction): void {
                $query->where('status', 'ACTIVE')->orWhere('id', $feedingTransaction->location_id);
            })
            ->orderBy('name')
            ->get(['id', 'parent_id', 'code', 'name']);
        $batches = CommodityBatch::query()
            ->with('commodity:id,name,unit')
            ->where(function (Builder $query) use ($feedingTransaction): void {
                $query->whereHas('pondStocks', fn (Builder $query) => $query->where('quantity', '>', 0));

                if ($feedingTransaction->batch_id) {
                    $query->orWhere('id', $feedingTransaction->batch_id);
                }
            })
            ->orderBy('batch_code')
            ->get(['id', 'batch_code', 'commodity_id']);
        $feedItems = FeedItem::query()
            ->where(function (Builder $query) use ($feedingTransaction): void {
                $query->where('status', 'ACTIVE')->orWhere('id', $feedingTransaction->feed_item_id);
            })
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'item_type', 'unit']);
        $vendors = Vendor::query()
            ->where(function (Builder $query) use ($feedingTransaction): void {
                $query->where(function (Builder $query): void {
                    $query->where('status', 'ACTIVE')->whereIn('vendor_type', ['FEED', 'MULTIPLE']);
                });

                if ($feedingTransaction->vendor_id) {
                    $query->orWhere('id', $feedingTransaction->vendor_id);
                }
            })
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'vendor_type']);

        return view('feeding.edit', compact('feedingTransaction', 'locations', 'batches', 'feedItems', 'vendors'));
    }

    public function update(UpdateFeedingTransactionRequest $request, FeedingTransaction $feedingTransaction): RedirectResponse
    {
        $validated = $request->validated();

        try {
            DB::transaction(function () use ($request, $feedingTransaction, $validated): void {
                $lockedTransaction = FeedingTransaction::query()
                    ->whereKey($feedingTransaction->id)
                    ->lockForUpdate()
                    ->firstOrFail();
                $location = Location::query()->whereKey($validated['location_id'])->lockForUpdate()->first();
                $feedItem = FeedItem::query()->whereKey($validated['feed_item_id'])->lockForUpdate()->first();

                if (! $location || $location->location_type !== 'PETAK' || $location->status !== 'ACTIVE') {
                    throw ValidationException::withMessages(['location_id' => 'Petak yang dipilih tidak valid.']);
                }

                if (! $feedItem || $feedItem->status !== 'ACTIVE') {
                    throw ValidationException::withMessages(['feed_item_id' => 'Pakan, nutrisi, atau obat yang dipilih tidak aktif.']);
                }

                $vendor = null;

                if ($validated['vendor_id'] ?? null) {
                    $vendor = Vendor::query()->whereKey($validated['vendor_id'])->lockForUpdate()->first();

                    if (! $vendor || $vendor->status !== 'ACTIVE' || ! in_array($vendor->vendor_type, ['FEED', 'MULTIPLE'], true)) {
                        throw ValidationException::withMessages(['vendor_id' => 'Vendor yang dipilih tidak valid.']);
                    }
                }

                $batchId = isset($validated['batch_id']) ? (int) $validated['batch_id'] : null;
                $contextChanged = $lockedTransaction->location_id !== $location->id
                    || $lockedTransaction->batch_id !== $batchId;
                $snapshot = (string) $lockedTransaction->stock_quantity_snapshot;

                if ($contextChanged) {
                    if ($batchId) {
                        $batch = CommodityBatch::query()->whereKey($batchId)->lockForUpdate()->first();
                        $stock = $batch
                            ? PondStock::query()
                                ->where('location_id', $location->id)
                                ->where('batch_id', $batch->id)
                                ->lockForUpdate()
                                ->first()
                            : null;

                        if (! $batch || ! $stock || bccomp((string) $stock->quantity, '0', 3) !== 1) {
                            throw ValidationException::withMessages(['batch_id' => 'Batch tidak tersedia pada petak tersebut.']);
                        }

                        $snapshot = (string) $stock->quantity;
                    } else {
                        $stocks = PondStock::query()
                            ->where('location_id', $location->id)
                            ->where('quantity', '>', 0)
                            ->orderBy('id')
                            ->lockForUpdate()
                            ->get();

                        if ($stocks->isEmpty()) {
                            throw ValidationException::withMessages(['location_id' => 'Petak tidak memiliki stok aktif.']);
                        }

                        $snapshot = $stocks->reduce(
                            fn (string $total, PondStock $stock): string => bcadd($total, (string) $stock->quantity, 3),
                            '0.000',
                        );
                    }
                }

                if (bccomp($snapshot, self::MAX_QUANTITY, 3) === 1) {
                    throw ValidationException::withMessages(['location_id' => 'Total stok petak melebihi kapasitas pencatatan.']);
                }

                $feedQuantity = (string) $validated['feed_quantity'];
                $unitCost = (string) $validated['unit_cost'];
                $totalCost = bcadd(bcmul($feedQuantity, $unitCost, 3), '0.005', 2);

                if (bccomp($totalCost, self::MAX_TOTAL_COST, 2) === 1) {
                    throw ValidationException::withMessages(['unit_cost' => 'Total biaya hasil perhitungan melebihi kapasitas penyimpanan.']);
                }

                $oldValues = [
                    'transaction_date' => $lockedTransaction->transaction_date->toDateTimeString(),
                    'location_id' => $lockedTransaction->location_id,
                    'batch_id' => $lockedTransaction->batch_id,
                    'feed_item_id' => $lockedTransaction->feed_item_id,
                    'vendor_id' => $lockedTransaction->vendor_id,
                    'stock_quantity_snapshot' => (string) $lockedTransaction->stock_quantity_snapshot,
                    'feed_quantity' => (string) $lockedTransaction->feed_quantity,
                    'unit_cost' => (string) $lockedTransaction->unit_cost,
                    'total_cost' => (string) $lockedTransaction->total_cost,
                    'notes' => $lockedTransaction->notes,
                ];

                $lockedTransaction->update([
                    'transaction_date' => Carbon::parse($validated['transaction_date']),
                    'location_id' => $location->id,
                    'batch_id' => $batchId,
                    'feed_item_id' => $feedItem->id,
                    'vendor_id' => $vendor?->id,
                    'stock_quantity_snapshot' => $snapshot,
                    'feed_quantity' => $feedQuantity,
                    'unit_cost' => $unitCost,
                    'total_cost' => $totalCost,
                    'notes' => $validated['notes'] ?? null,
                ]);

                AuditLog::query()->create([
                    'user_id' => $request->user()->id,
                    'action' => 'UPDATE',
                    'module' => 'FEEDING_TRANSACTION',
                    'record_id' => $lockedTransaction->id,
                    'transaction_number' => $lockedTransaction->transaction_number,
                    'description' => "Pemberian pakan {$lockedTransaction->transaction_number} diperbarui",
                    'old_values' => $oldValues,
                    'new_values' => [
                        'transaction_date' => $lockedTransaction->transaction_date->toDateTimeString(),
                        'location_id' => $lockedTransaction->location_id,
                        'batch_id' => $lockedTransaction->batch_id,
                        'feed_item_id' => $lockedTransaction->feed_item_id,
                        'vendor_id' => $lockedTransaction->vendor_id,
                        'stock_quantity_snapshot' => (string) $lockedTransaction->stock_quantity_snapshot,
                        'feed_quantity' => (string) $lockedTransaction->feed_quantity,
                        'unit_cost' => (string) $lockedTransaction->unit_cost,
                        'total_cost' => (string) $lockedTransaction->total_cost,
                        'notes' => $lockedTransaction->notes,
                    ],
                ]);
            });
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);

            return back()->withInput()->with('error', 'Pemberian pakan gagal diperbarui. Silakan coba kembali.');
        }

        return redirect()->route('feeding.show', $feedingTransaction)->with('success', 'Pemberian pakan berhasil diperbarui.');
    }

    public function destroy(Request $request, FeedingTransaction $feedingTransaction): RedirectResponse
    {
        try {
            DB::transaction(function () use ($request, $feedingTransaction): void {
                $lockedTransaction = FeedingTransaction::query()
                    ->whereKey($feedingTransaction->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                AuditLog::query()->create([
                    'user_id' => $request->user()->id,
                    'action' => 'DELETE',
                    'module' => 'FEEDING_TRANSACTION',
                    'record_id' => $lockedTransaction->id,
                    'transaction_number' => $lockedTransaction->transaction_number,
                    'description' => "Pemberian pakan {$lockedTransaction->transaction_number} dihapus",
                    'old_values' => [
                        'transaction_date' => $lockedTransaction->transaction_date->toDateTimeString(),
                        'location_id' => $lockedTransaction->location_id,
                        'batch_id' => $lockedTransaction->batch_id,
                        'feed_item_id' => $lockedTransaction->feed_item_id,
                        'vendor_id' => $lockedTransaction->vendor_id,
                        'stock_quantity_snapshot' => (string) $lockedTransaction->stock_quantity_snapshot,
                        'feed_quantity' => (string) $lockedTransaction->feed_quantity,
                        'unit_cost' => (string) $lockedTransaction->unit_cost,
                        'total_cost' => (string) $lockedTransaction->total_cost,
                        'notes' => $lockedTransaction->notes,
                        'created_by' => $lockedTransaction->created_by,
                        'created_at' => $lockedTransaction->created_at?->toDateTimeString(),
                    ],
                    'new_values' => null,
                ]);

                $lockedTransaction->delete();
            });
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('error', 'Pemberian pakan gagal dihapus. Silakan coba kembali.');
        }

        return redirect()->route('feeding.index')->with('success', 'Pemberian pakan berhasil dihapus. Stok tidak berubah.');
    }

    public function show(FeedingTransaction $feedingTransaction): View
    {
        $feedingTransaction->load([
            'location:id,parent_id,code,name',
            'location.parent:id,name',
            'batch:id,batch_code,commodity_id',
            'batch.commodity:id,code,name,unit',
            'feedItem:id,code,name,item_type,unit',
            'vendor:id,code,name',
            'createdBy:id,name',
        ]);
        $currentStocks = $feedingTransaction->batch_id
            ? PondStock::query()
                ->with('location:id,parent_id,code,name')
                ->where('batch_id', $feedingTransaction->batch_id)
                ->where('quantity', '>', 0)
                ->orderBy('location_id')
                ->get()
            : PondStock::query()
                ->with(['batch:id,batch_code,commodity_id', 'batch.commodity:id,name,unit'])
                ->where('location_id', $feedingTransaction->location_id)
                ->where('quantity', '>', 0)
                ->orderBy('id')
                ->get();

        return view('feeding.show', [
            'feedingTransaction' => $feedingTransaction,
            'currentStocks' => $currentStocks,
            'typeLabels' => UserFacing::FEED_ITEM_TYPES,
        ]);
    }

    private function validPositiveId(mixed $value): ?int
    {
        return filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: null;
    }
}
