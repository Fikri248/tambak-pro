<?php

namespace App\Services\Transactions;

use App\Exceptions\TransactionMutationBlocked;
use App\Models\AuditLog;
use App\Models\Commodity;
use App\Models\CommodityBatch;
use App\Models\Location;
use App\Models\PondStock;
use App\Models\StockingTransaction;
use App\Models\Vendor;
use App\Models\VendorType;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockingTransactionMutationService
{
    private const MAX_QUANTITY = '999999999999999.999';

    private const MAX_UNIT_COST = '99999999999999.9999';

    public function __construct(private readonly TransactionDependencyGuard $dependencies) {}

    /** @param array<string, mixed> $validated */
    public function update(StockingTransaction $transaction, array $validated, int $actorId): StockingTransaction
    {
        return DB::transaction(function () use ($transaction, $validated, $actorId): StockingTransaction {
            $locked = StockingTransaction::query()->lockForUpdate()->findOrFail($transaction->id);
            $batchSnapshot = CommodityBatch::query()->findOrFail($locked->batch_id);
            $locationIds = $this->ids([$locked->location_id, $validated['location_id']]);
            $commodityIds = $this->ids([$batchSnapshot->commodity_id, $validated['commodity_id']]);
            $vendorIds = $this->ids(array_filter([$batchSnapshot->vendor_id, $validated['vendor_id']]));

            $locations = Location::query()->whereIn('id', $locationIds)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $commodities = Commodity::query()->whereIn('id', $commodityIds)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $vendors = Vendor::query()->whereIn('id', $vendorIds)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $batch = CommodityBatch::query()->lockForUpdate()->findOrFail($locked->batch_id);
            $stocks = PondStock::query()
                ->where('batch_id', $batch->id)
                ->orderBy('location_id')
                ->lockForUpdate()
                ->get();

            $requestedLocation = $locations->get((int) $validated['location_id']);
            $requestedCommodity = $commodities->get((int) $validated['commodity_id']);
            $requestedVendor = $vendors->get((int) $validated['vendor_id']);
            $this->validateMasters($locked, $batch, $requestedLocation, $requestedCommodity, $requestedVendor);

            $transactionDate = Carbon::parse($validated['transaction_date']);
            $quantity = (string) $validated['quantity'];
            $totalCost = (string) $validated['total_cost'];
            $unitCost = bcadd(bcdiv($totalCost, $quantity, 5), '0.00005', 4);

            if (bccomp($unitCost, self::MAX_UNIT_COST, 4) === 1) {
                throw ValidationException::withMessages([
                    'total_cost' => 'Harga per satuan hasil perhitungan melebihi kapasitas penyimpanan.',
                ]);
            }

            $oldValues = $this->snapshot($locked, $batch, $stocks);
            $stockSensitive = $this->hasStockSensitiveChanges(
                $locked,
                $batch,
                $transactionDate,
                (int) $validated['location_id'],
                (int) $validated['commodity_id'],
                (int) $validated['vendor_id'],
                $quantity,
                $totalCost,
            );
            $notesChanged = ($locked->notes ?? null) !== ($validated['notes'] ?? null)
                || ($batch->notes ?? null) !== ($validated['notes'] ?? null);

            if (! $stockSensitive && ! $notesChanged) {
                return $locked;
            }

            if ($stockSensitive) {
                if ($this->dependencies->stockingHasDownstreamActivity($locked, $locationIds)) {
                    throw new TransactionMutationBlocked(
                        'Transaksi Pembibitan ini sudah memiliki aktivitas lanjutan. Jumlah, Batch, Petak, atau data stok tidak dapat diubah.'
                    );
                }

                $this->assertOriginalStockIsReversible($locked, $stocks);
                $this->applyStockReplacement(
                    $locked,
                    $stocks,
                    (int) $validated['location_id'],
                    $quantity,
                );
            }

            $batch->update([
                'commodity_id' => $requestedCommodity->id,
                'vendor_id' => $requestedVendor->id,
                'purchase_date' => $transactionDate->toDateString(),
                'initial_quantity' => $quantity,
                'total_cost' => $totalCost,
                'unit_cost' => $unitCost,
                'notes' => $validated['notes'] ?? null,
            ]);
            $locked->update([
                'transaction_date' => $transactionDate,
                'location_id' => $requestedLocation->id,
                'quantity' => $quantity,
                'total_cost' => $totalCost,
                'unit_cost' => $unitCost,
                'notes' => $validated['notes'] ?? null,
            ]);

            $freshStocks = PondStock::query()->where('batch_id', $batch->id)->orderBy('location_id')->get();
            AuditLog::query()->create([
                'user_id' => $actorId,
                'action' => 'UPDATE',
                'module' => 'STOCKING_TRANSACTION',
                'record_id' => $locked->id,
                'transaction_number' => $locked->transaction_number,
                'description' => "Pembibitan {$locked->transaction_number} diperbarui.",
                'old_values' => $oldValues,
                'new_values' => $this->snapshot($locked, $batch, $freshStocks),
            ]);

            return $locked;
        });
    }

    public function delete(StockingTransaction $transaction, int $actorId): string
    {
        return DB::transaction(function () use ($transaction, $actorId): string {
            $locked = StockingTransaction::query()->lockForUpdate()->findOrFail($transaction->id);
            Location::query()->whereKey($locked->location_id)->lockForUpdate()->firstOrFail();
            $batch = CommodityBatch::query()->lockForUpdate()->findOrFail($locked->batch_id);
            $stocks = PondStock::query()
                ->where('batch_id', $batch->id)
                ->orderBy('location_id')
                ->lockForUpdate()
                ->get();

            if ($this->dependencies->stockingHasDownstreamActivity($locked, [(int) $locked->location_id])) {
                throw new TransactionMutationBlocked(
                    'Transaksi Pembibitan ini tidak dapat dihapus karena Batch sudah digunakan pada transaksi lain.'
                );
            }

            if (StockingTransaction::query()->where('batch_id', $batch->id)->whereKeyNot($locked->id)->exists()) {
                throw new TransactionMutationBlocked(
                    'Transaksi Pembibitan ini tidak dapat dihapus karena Batch memiliki catatan Pembibitan lain.'
                );
            }

            $this->assertOriginalStockIsReversible($locked, $stocks);
            $transactionNumber = $locked->transaction_number;
            $oldValues = $this->snapshot($locked, $batch, $stocks);

            AuditLog::query()->create([
                'user_id' => $actorId,
                'action' => 'DELETE',
                'module' => 'STOCKING_TRANSACTION',
                'record_id' => $locked->id,
                'transaction_number' => $transactionNumber,
                'description' => "Pembibitan {$transactionNumber} dihapus dan dampak stoknya dibatalkan.",
                'old_values' => $oldValues,
                'new_values' => null,
            ]);

            foreach ($stocks as $stock) {
                $stock->delete();
            }

            $locked->delete();
            $batch->delete();

            return $transactionNumber;
        });
    }

    private function validateMasters(
        StockingTransaction $transaction,
        CommodityBatch $batch,
        ?Location $location,
        ?Commodity $commodity,
        ?Vendor $vendor,
    ): void {
        if (! $location || $location->location_type !== 'PETAK'
            || ($location->status !== 'ACTIVE' && $location->id !== $transaction->location_id)) {
            throw ValidationException::withMessages(['location_id' => 'Lokasi petak yang dipilih tidak valid.']);
        }

        if (! $commodity || ($commodity->status !== 'ACTIVE' && $commodity->id !== $batch->commodity_id)) {
            throw ValidationException::withMessages(['commodity_id' => 'Komoditas yang dipilih tidak aktif.']);
        }

        if (! $vendor || ! $vendor->hasVendorSemantic(VendorType::SEMANTIC_SEED, VendorType::SEMANTIC_MULTIPLE)
            || ($vendor->status !== 'ACTIVE' && $vendor->id !== $batch->vendor_id)) {
            throw ValidationException::withMessages(['vendor_id' => 'Vendor yang dipilih tidak dapat digunakan untuk pembibitan.']);
        }
    }

    /** @param Collection<int, PondStock> $stocks */
    private function assertOriginalStockIsReversible(StockingTransaction $transaction, Collection $stocks): void
    {
        $original = $stocks->firstWhere('location_id', $transaction->location_id);

        if (! $original || bccomp((string) $original->quantity, (string) $transaction->quantity, 3) !== 0) {
            throw new TransactionMutationBlocked(
                'Transaksi Pembibitan tidak dapat diubah karena posisi stok Batch tidak lagi sama dengan pencatatan awal.'
            );
        }

        $hasOtherPositiveStock = $stocks->contains(
            fn (PondStock $stock): bool => $stock->location_id !== $transaction->location_id
                && bccomp((string) $stock->quantity, '0', 3) === 1
        );

        if ($hasOtherPositiveStock) {
            throw new TransactionMutationBlocked(
                'Transaksi Pembibitan tidak dapat diubah karena stok Batch sudah tersebar ke petak lain.'
            );
        }
    }

    /** @param Collection<int, PondStock> $stocks */
    private function applyStockReplacement(
        StockingTransaction $transaction,
        Collection $stocks,
        int $newLocationId,
        string $newQuantity,
    ): void {
        if (bccomp($newQuantity, self::MAX_QUANTITY, 3) === 1) {
            throw ValidationException::withMessages(['quantity' => 'Jumlah bibit melebihi batas penyimpanan.']);
        }

        $oldStock = $stocks->firstWhere('location_id', $transaction->location_id);
        $newStock = $stocks->firstWhere('location_id', $newLocationId);

        if ($transaction->location_id === $newLocationId) {
            $oldStock->update(['quantity' => $newQuantity]);

            return;
        }

        $oldStock->update(['quantity' => '0.000']);

        if ($newStock) {
            $newStock->update(['quantity' => $newQuantity]);
        } else {
            PondStock::query()->create([
                'location_id' => $newLocationId,
                'batch_id' => $transaction->batch_id,
                'quantity' => $newQuantity,
            ]);
        }
    }

    private function hasStockSensitiveChanges(
        StockingTransaction $transaction,
        CommodityBatch $batch,
        Carbon $transactionDate,
        int $locationId,
        int $commodityId,
        int $vendorId,
        string $quantity,
        string $totalCost,
    ): bool {
        return $transaction->transaction_date->format('Y-m-d H:i:s') !== $transactionDate->format('Y-m-d H:i:s')
            || $transaction->location_id !== $locationId
            || $batch->commodity_id !== $commodityId
            || $batch->vendor_id !== $vendorId
            || bccomp((string) $transaction->quantity, $quantity, 3) !== 0
            || bccomp((string) $transaction->total_cost, $totalCost, 2) !== 0;
    }

    /**
     * @param  Collection<int, PondStock>  $stocks
     * @return array<string, mixed>
     */
    private function snapshot(StockingTransaction $transaction, CommodityBatch $batch, Collection $stocks): array
    {
        return [
            'transaction_number' => $transaction->transaction_number,
            'transaction_date' => $transaction->transaction_date->format('Y-m-d H:i:s'),
            'location_id' => (int) $transaction->location_id,
            'batch_id' => (int) $batch->id,
            'batch_code' => $batch->batch_code,
            'commodity_id' => (int) $batch->commodity_id,
            'vendor_id' => $batch->vendor_id !== null ? (int) $batch->vendor_id : null,
            'quantity' => (string) $transaction->quantity,
            'total_cost' => (string) $transaction->total_cost,
            'unit_cost' => (string) $transaction->unit_cost,
            'notes' => $transaction->notes,
            'stock_balances' => $stocks->mapWithKeys(
                fn (PondStock $stock): array => [(string) $stock->location_id => (string) $stock->quantity]
            )->all(),
        ];
    }

    /** @param array<int, int|string> $ids
     * @return list<int>
     */
    private function ids(array $ids): array
    {
        return collect($ids)->map(fn (int|string $id): int => (int) $id)->unique()->sort()->values()->all();
    }
}
