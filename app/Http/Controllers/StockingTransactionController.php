<?php

namespace App\Http\Controllers;

use App\Exceptions\TransactionMutationBlocked;
use App\Http\Requests\StockingTransactionRequest;
use App\Http\Requests\TransactionIndexFilterRequest;
use App\Http\Requests\UpdateStockingTransactionRequest;
use App\Models\AuditLog;
use App\Models\Commodity;
use App\Models\CommodityBatch;
use App\Models\Location;
use App\Models\PondStock;
use App\Models\StockingTransaction;
use App\Models\Vendor;
use App\Services\BusinessCodeGenerator;
use App\Services\Transactions\StockingTransactionMutationService;
use App\Support\PageSize;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class StockingTransactionController extends Controller
{
    private const MAX_UNIT_COST = '99999999999999.9999';

    public function index(TransactionIndexFilterRequest $request): View
    {
        $search = mb_substr(trim((string) $request->query('search')), 0, 255);
        $locationId = $this->validPositiveId($request->query('location_id'));
        $commodityId = $this->validPositiveId($request->query('commodity_id'));
        $dateFrom = $request->validated('date_from');
        $dateTo = $request->validated('date_to');

        $transactions = StockingTransaction::query()
            ->with([
                'location:id,code,name',
                'batch:id,batch_code,commodity_id,vendor_id',
                'batch.commodity:id,code,name,unit',
                'batch.vendor:id,code,name',
            ])
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('transaction_number', 'like', "%{$search}%")
                        ->orWhereHas('batch', fn (Builder $query) => $query->where('batch_code', 'like', "%{$search}%"))
                        ->orWhereHas('batch.commodity', fn (Builder $query) => $query->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('batch.vendor', fn (Builder $query) => $query->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('location', fn (Builder $query) => $query->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($locationId, fn (Builder $query, int $locationId) => $query->where('location_id', $locationId))
            ->when($commodityId, fn (Builder $query, int $commodityId) => $query->whereHas(
                'batch',
                fn (Builder $query) => $query->where('commodity_id', $commodityId),
            ))
            ->when($dateFrom, fn (Builder $query, string $dateFrom) => $query->whereDate('transaction_date', '>=', $dateFrom))
            ->when($dateTo, fn (Builder $query, string $dateTo) => $query->whereDate('transaction_date', '<=', $dateTo))
            ->latest('transaction_date')
            ->latest('id')
            ->paginate(PageSize::resolve($request))
            ->withQueryString();

        $units = StockingTransaction::query()
            ->join('commodity_batches', 'commodity_batches.id', '=', 'stocking_transactions.batch_id')
            ->join('commodities', 'commodities.id', '=', 'commodity_batches.commodity_id')
            ->distinct()
            ->pluck('commodities.unit');

        return view('stocking.index', [
            'transactions' => $transactions,
            'locations' => Location::query()->where('location_type', 'PETAK')->orderBy('name')->get(['id', 'name', 'code']),
            'commodities' => Commodity::query()->orderBy('name')->get(['id', 'name', 'code']),
            'filters' => compact('search', 'locationId', 'commodityId', 'dateFrom', 'dateTo'),
            'summary' => [
                'total' => StockingTransaction::query()->count(),
                'quantity' => StockingTransaction::query()->sum('quantity'),
                'unit' => $units->count() === 1 ? $units->first() : 'unit',
                'active_batches' => CommodityBatch::query()->where('status', 'ACTIVE')->count(),
                'total_cost' => StockingTransaction::query()->sum('total_cost'),
            ],
        ]);
    }

    public function create(): View
    {
        return view('stocking.create', [
            'locations' => Location::query()
                ->with('parent:id,name')
                ->where('location_type', 'PETAK')
                ->where('status', 'ACTIVE')
                ->orderBy('name')
                ->get(['id', 'parent_id', 'code', 'name']),
            'commodities' => Commodity::query()
                ->where('status', 'ACTIVE')
                ->orderBy('name')
                ->get(['id', 'code', 'name', 'unit']),
            'vendors' => Vendor::query()
                ->where('status', 'ACTIVE')
                ->whereIn('vendor_type', ['SEED', 'MULTIPLE'])
                ->orderBy('name')
                ->get(['id', 'code', 'name', 'vendor_type']),
        ]);
    }

    public function store(StockingTransactionRequest $request, BusinessCodeGenerator $codes): RedirectResponse
    {
        $validated = $request->validated();

        try {
            $transaction = DB::transaction(function () use ($request, $validated, $codes): StockingTransaction {
                $location = Location::query()
                    ->whereKey($validated['location_id'])
                    ->where('location_type', 'PETAK')
                    ->where('status', 'ACTIVE')
                    ->lockForUpdate()
                    ->first();
                $commodity = Commodity::query()
                    ->whereKey($validated['commodity_id'])
                    ->where('status', 'ACTIVE')
                    ->lockForUpdate()
                    ->first();
                $vendor = Vendor::query()
                    ->whereKey($validated['vendor_id'])
                    ->where('status', 'ACTIVE')
                    ->whereIn('vendor_type', ['SEED', 'MULTIPLE'])
                    ->lockForUpdate()
                    ->first();

                if (! $location) {
                    throw ValidationException::withMessages(['location_id' => 'Lokasi petak yang dipilih tidak valid.']);
                }

                if (! $commodity) {
                    throw ValidationException::withMessages(['commodity_id' => 'Komoditas yang dipilih tidak aktif.']);
                }

                if (! $vendor) {
                    throw ValidationException::withMessages(['vendor_id' => 'Vendor yang dipilih tidak dapat digunakan untuk pembibitan.']);
                }

                $quantity = (string) $validated['quantity'];
                $totalCost = (string) $validated['total_cost'];
                $unitCost = bcadd(bcdiv($totalCost, $quantity, 5), '0.00005', 4);

                if (bccomp($unitCost, self::MAX_UNIT_COST, 4) === 1) {
                    throw ValidationException::withMessages([
                        'total_cost' => 'Harga per satuan hasil perhitungan melebihi kapasitas penyimpanan.',
                    ]);
                }

                $transactionDate = Carbon::parse($validated['transaction_date']);

                $batch = $codes->create(CommodityBatch::class, 'batch_code', 'BT', [
                    'commodity_id' => $commodity->id,
                    'vendor_id' => $vendor->id,
                    'purchase_date' => $transactionDate->toDateString(),
                    'initial_quantity' => $quantity,
                    'total_cost' => $totalCost,
                    'unit_cost' => $unitCost,
                    'status' => 'ACTIVE',
                    'notes' => $validated['notes'] ?? null,
                ]);

                $transaction = StockingTransaction::query()->create([
                    'transaction_number' => 'PBT-TMP-'.Str::uuid(),
                    'transaction_date' => $transactionDate,
                    'location_id' => $location->id,
                    'batch_id' => $batch->id,
                    'quantity' => $quantity,
                    'total_cost' => $totalCost,
                    'unit_cost' => $unitCost,
                    'created_by' => $request->user()->id,
                    'notes' => $validated['notes'] ?? null,
                ]);

                $transaction->update([
                    'transaction_number' => sprintf('PBT-%06d', $transaction->id),
                ]);

                PondStock::query()->create([
                    'location_id' => $location->id,
                    'batch_id' => $batch->id,
                    'quantity' => $quantity,
                ]);

                AuditLog::query()->create([
                    'user_id' => $request->user()->id,
                    'action' => 'CREATE',
                    'module' => 'STOCKING_TRANSACTION',
                    'record_id' => $transaction->id,
                    'transaction_number' => $transaction->transaction_number,
                    'description' => sprintf(
                        'Pembibitan %s %s %s ke %s',
                        number_format((float) $quantity, floor((float) $quantity) === (float) $quantity ? 0 : 3, ',', '.'),
                        $commodity->unit,
                        $commodity->name,
                        $location->name,
                    ),
                    'old_values' => null,
                    'new_values' => [
                        'batch_code' => $batch->batch_code,
                        'commodity_id' => $commodity->id,
                        'commodity' => $commodity->name,
                        'vendor_id' => $vendor->id,
                        'vendor' => $vendor->name,
                        'location_id' => $location->id,
                        'location' => $location->name,
                        'quantity' => (float) $quantity,
                        'total_cost' => (float) $totalCost,
                        'unit_cost' => (float) $unitCost,
                    ],
                ]);

                return $transaction;
            });
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);

            return back()
                ->withInput()
                ->with('error', 'Transaksi pembibitan gagal disimpan. Silakan coba kembali.');
        }

        return redirect()
            ->route('stocking.show', $transaction)
            ->with('success', 'Transaksi pembibitan berhasil disimpan.');
    }

    public function edit(StockingTransaction $stockingTransaction): View
    {
        abort_unless(request()->user()?->canAccess('stocking.update'), 403);

        $stockingTransaction->load('batch:id,batch_code,commodity_id,vendor_id');

        return view('stocking.edit', [
            'stockingTransaction' => $stockingTransaction,
            'locations' => Location::query()
                ->with('parent:id,name')
                ->where('location_type', 'PETAK')
                ->where(fn (Builder $query) => $query
                    ->where('status', 'ACTIVE')
                    ->orWhere('id', $stockingTransaction->location_id))
                ->orderBy('name')
                ->get(['id', 'parent_id', 'code', 'name']),
            'commodities' => Commodity::query()
                ->where(fn (Builder $query) => $query
                    ->where('status', 'ACTIVE')
                    ->orWhere('id', $stockingTransaction->batch->commodity_id))
                ->orderBy('name')
                ->get(['id', 'code', 'name', 'unit']),
            'vendors' => Vendor::query()
                ->whereIn('vendor_type', ['SEED', 'MULTIPLE'])
                ->where(fn (Builder $query) => $query
                    ->where('status', 'ACTIVE')
                    ->orWhere('id', $stockingTransaction->batch->vendor_id))
                ->orderBy('name')
                ->get(['id', 'code', 'name', 'vendor_type']),
        ]);
    }

    public function update(
        UpdateStockingTransactionRequest $request,
        StockingTransaction $stockingTransaction,
        StockingTransactionMutationService $mutations,
    ): RedirectResponse {
        try {
            $updated = $mutations->update(
                $stockingTransaction,
                $request->validated(),
                (int) $request->user()->id,
            );
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (TransactionMutationBlocked $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        } catch (Throwable $exception) {
            report($exception);

            return back()->withInput()->with('error', 'Transaksi pembibitan gagal diperbarui. Silakan coba kembali.');
        }

        return redirect()
            ->route('stocking.show', $updated)
            ->with('success', 'Transaksi pembibitan berhasil diperbarui.');
    }

    public function destroy(
        Request $request,
        StockingTransaction $stockingTransaction,
        StockingTransactionMutationService $mutations,
    ): RedirectResponse {
        abort_unless($request->user()?->canAccess('stocking.delete'), 403);

        try {
            $transactionNumber = $mutations->delete($stockingTransaction, (int) $request->user()->id);
        } catch (TransactionMutationBlocked $exception) {
            return back()->with('error', $exception->getMessage());
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('error', 'Transaksi pembibitan gagal dihapus. Silakan coba kembali.');
        }

        return redirect()
            ->route('stocking.index')
            ->with('success', "Transaksi pembibitan {$transactionNumber} berhasil dihapus dan dampak stoknya dibatalkan.");
    }

    public function show(StockingTransaction $stockingTransaction): View
    {
        $stockingTransaction->load([
            'location:id,code,name,parent_id',
            'location.parent:id,name',
            'batch:id,batch_code,commodity_id,vendor_id,purchase_date,unit_cost,status',
            'batch.commodity:id,code,name,unit',
            'batch.vendor:id,code,name',
            'createdBy:id,name',
        ]);

        $currentStocks = PondStock::query()
            ->with('location:id,code,name,parent_id')
            ->where('batch_id', $stockingTransaction->batch_id)
            ->where('quantity', '>', 0)
            ->orderBy('location_id')
            ->get();

        return view('stocking.show', compact('stockingTransaction', 'currentStocks'));
    }

    private function validPositiveId(mixed $value): ?int
    {
        return filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: null;
    }
}
