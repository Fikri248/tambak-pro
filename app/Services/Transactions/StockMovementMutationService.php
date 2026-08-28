<?php

namespace App\Services\Transactions;

use App\Exceptions\TransactionMutationBlocked;
use App\Models\AuditLog;
use App\Models\CommodityBatch;
use App\Models\Location;
use App\Models\PondStock;
use App\Models\StockMovement;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockMovementMutationService
{
    private const MAX_QUANTITY = '999999999999999.999';

    public function __construct(private readonly TransactionDependencyGuard $dependencies) {}

    /** @param array<string, mixed> $validated */
    public function update(StockMovement $transaction, array $validated, int $actorId): StockMovement
    {
        return DB::transaction(function () use ($transaction, $validated, $actorId): StockMovement {
            $locked = StockMovement::query()->lockForUpdate()->findOrFail($transaction->id);
            $transactionDate = Carbon::parse($validated['transaction_date']);
            $newBatchId = (int) $validated['batch_id'];
            $newSourceId = (int) $validated['from_location_id'];
            $newDestinationId = (int) $validated['to_location_id'];
            $newQuantity = (string) $validated['quantity'];
            $locationIds = $this->ids([
                $locked->from_location_id,
                $locked->to_location_id,
                $newSourceId,
                $newDestinationId,
            ]);
            $batchIds = $this->ids([$locked->batch_id, $newBatchId]);

            $locations = Location::query()->whereIn('id', $locationIds)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $batches = CommodityBatch::query()
                ->with('commodity:id,name,unit')
                ->whereIn('id', $batchIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');
            $stocks = PondStock::query()
                ->whereIn('batch_id', $batchIds)
                ->whereIn('location_id', $locationIds)
                ->orderBy('batch_id')
                ->orderBy('location_id')
                ->lockForUpdate()
                ->get();

            $source = $locations->get($newSourceId);
            $destination = $locations->get($newDestinationId);
            $batch = $batches->get($newBatchId);
            $this->validateRequestedContext($locked, $source, $destination, $batch);

            $oldValues = $this->snapshot($locked, $stocks);
            $stockSensitive = $locked->transaction_date->format('Y-m-d H:i:s') !== $transactionDate->format('Y-m-d H:i:s')
                || $locked->batch_id !== $newBatchId
                || $locked->from_location_id !== $newSourceId
                || $locked->to_location_id !== $newDestinationId
                || bccomp((string) $locked->quantity, $newQuantity, 3) !== 0;
            $notesChanged = ($locked->notes ?? null) !== ($validated['notes'] ?? null);

            if (! $stockSensitive && ! $notesChanged) {
                return $locked;
            }

            if ($stockSensitive) {
                if ($this->dependencies->movementHasDownstreamActivity($locked, $batchIds, $locationIds)) {
                    throw new TransactionMutationBlocked(
                        'Pemindahan stok ini tidak dapat diubah karena stok pada petak terkait sudah digunakan oleh transaksi berikutnya.'
                    );
                }

                $balances = $this->balances($stocks);
                $this->reverse($balances, (int) $locked->batch_id, (int) $locked->from_location_id, (int) $locked->to_location_id, (string) $locked->quantity);
                $this->apply($balances, $newBatchId, $newSourceId, $newDestinationId, $newQuantity);
                $this->persistBalances($balances, $stocks);
            }

            $locked->update([
                'transaction_date' => $transactionDate,
                'batch_id' => $newBatchId,
                'from_location_id' => $newSourceId,
                'to_location_id' => $newDestinationId,
                'quantity' => $newQuantity,
                'notes' => $validated['notes'] ?? null,
            ]);

            $freshStocks = PondStock::query()
                ->whereIn('batch_id', $batchIds)
                ->whereIn('location_id', $locationIds)
                ->orderBy('batch_id')
                ->orderBy('location_id')
                ->get();
            AuditLog::query()->create([
                'user_id' => $actorId,
                'action' => 'UPDATE',
                'module' => 'STOCK_MOVEMENT',
                'record_id' => $locked->id,
                'transaction_number' => $locked->transaction_number,
                'description' => "Pemindahan stok {$locked->transaction_number} diperbarui.",
                'old_values' => $oldValues,
                'new_values' => $this->snapshot($locked, $freshStocks),
            ]);

            return $locked;
        });
    }

    public function delete(StockMovement $transaction, int $actorId): string
    {
        return DB::transaction(function () use ($transaction, $actorId): string {
            $locked = StockMovement::query()->lockForUpdate()->findOrFail($transaction->id);
            $locationIds = $this->ids([$locked->from_location_id, $locked->to_location_id]);
            Location::query()->whereIn('id', $locationIds)->orderBy('id')->lockForUpdate()->get();
            CommodityBatch::query()->whereKey($locked->batch_id)->lockForUpdate()->firstOrFail();
            $stocks = PondStock::query()
                ->where('batch_id', $locked->batch_id)
                ->whereIn('location_id', $locationIds)
                ->orderBy('location_id')
                ->lockForUpdate()
                ->get();

            if ($this->dependencies->movementHasDownstreamActivity(
                $locked,
                [(int) $locked->batch_id],
                $locationIds,
            )) {
                throw new TransactionMutationBlocked(
                    'Pemindahan stok tidak dapat dihapus karena stok tujuan sudah digunakan oleh transaksi berikutnya.'
                );
            }

            $oldValues = $this->snapshot($locked, $stocks);
            $balances = $this->balances($stocks);
            $this->reverse(
                $balances,
                (int) $locked->batch_id,
                (int) $locked->from_location_id,
                (int) $locked->to_location_id,
                (string) $locked->quantity,
            );
            $this->persistBalances($balances, $stocks);
            $transactionNumber = $locked->transaction_number;

            AuditLog::query()->create([
                'user_id' => $actorId,
                'action' => 'DELETE',
                'module' => 'STOCK_MOVEMENT',
                'record_id' => $locked->id,
                'transaction_number' => $transactionNumber,
                'description' => "Pemindahan stok {$transactionNumber} dihapus dan dampak stoknya dibatalkan.",
                'old_values' => $oldValues,
                'new_values' => [
                    'transaction_number' => $transactionNumber,
                    'stock_balances_after_reversal' => $balances,
                ],
            ]);

            $locked->delete();

            return $transactionNumber;
        });
    }

    private function validateRequestedContext(
        StockMovement $transaction,
        ?Location $source,
        ?Location $destination,
        ?CommodityBatch $batch,
    ): void {
        if (! $source || $source->location_type !== 'PETAK'
            || ($source->status !== 'ACTIVE' && $source->id !== $transaction->from_location_id)) {
            throw ValidationException::withMessages(['from_location_id' => 'Petak asal tidak valid.']);
        }

        if (! $destination || $destination->location_type !== 'PETAK'
            || ($destination->status !== 'ACTIVE' && $destination->id !== $transaction->to_location_id)) {
            throw ValidationException::withMessages(['to_location_id' => 'Petak tujuan tidak valid.']);
        }

        if ($source->is($destination)) {
            throw ValidationException::withMessages(['to_location_id' => 'Petak asal dan petak tujuan harus berbeda.']);
        }

        if (! $batch) {
            throw ValidationException::withMessages(['batch_id' => 'Batch tidak tersedia.']);
        }
    }

    /** @param array<string, string> $balances */
    private function reverse(array &$balances, int $batchId, int $sourceId, int $destinationId, string $quantity): void
    {
        $sourceKey = $this->key($batchId, $sourceId);
        $destinationKey = $this->key($batchId, $destinationId);
        $source = $balances[$sourceKey] ?? '0.000';
        $destination = $balances[$destinationKey] ?? '0.000';

        if (bccomp($destination, $quantity, 3) === -1) {
            throw new TransactionMutationBlocked(
                'Pemindahan stok tidak dapat dibatalkan karena stok pada petak tujuan sudah tidak mencukupi.'
            );
        }

        $restoredSource = bcadd($source, $quantity, 3);

        if (bccomp($restoredSource, self::MAX_QUANTITY, 3) === 1) {
            throw new TransactionMutationBlocked(
                'Pemindahan stok tidak dapat dibatalkan karena stok petak asal akan melebihi batas penyimpanan.'
            );
        }

        $balances[$sourceKey] = $restoredSource;
        $balances[$destinationKey] = bcsub($destination, $quantity, 3);
    }

    /** @param array<string, string> $balances */
    private function apply(array &$balances, int $batchId, int $sourceId, int $destinationId, string $quantity): void
    {
        $sourceKey = $this->key($batchId, $sourceId);
        $destinationKey = $this->key($batchId, $destinationId);
        $source = $balances[$sourceKey] ?? '0.000';
        $destination = $balances[$destinationKey] ?? '0.000';

        if (bccomp($source, '0', 3) !== 1) {
            throw ValidationException::withMessages(['batch_id' => 'Batch tidak memiliki stok yang dapat dipindahkan dari petak asal.']);
        }

        if (bccomp($quantity, $source, 3) === 1) {
            throw ValidationException::withMessages(['quantity' => 'Stok tidak mencukupi untuk pemindahan.']);
        }

        $destinationAfter = bcadd($destination, $quantity, 3);

        if (bccomp($destinationAfter, self::MAX_QUANTITY, 3) === 1) {
            throw ValidationException::withMessages([
                'quantity' => 'Jumlah yang dipindahkan membuat stok tujuan melebihi kapasitas penyimpanan.',
            ]);
        }

        $balances[$sourceKey] = bcsub($source, $quantity, 3);
        $balances[$destinationKey] = $destinationAfter;
    }

    /**
     * @param  array<string, string>  $balances
     * @param  Collection<int, PondStock>  $stocks
     */
    private function persistBalances(array $balances, Collection $stocks): void
    {
        $existing = $stocks->keyBy(fn (PondStock $stock): string => $this->key($stock->batch_id, $stock->location_id));

        foreach ($balances as $key => $quantity) {
            [$batchId, $locationId] = array_map('intval', explode(':', $key));
            $stock = $existing->get($key);

            if ($stock) {
                $stock->update(['quantity' => $quantity]);
            } else {
                PondStock::query()->create([
                    'batch_id' => $batchId,
                    'location_id' => $locationId,
                    'quantity' => $quantity,
                ]);
            }
        }
    }

    /** @param Collection<int, PondStock> $stocks
     * @return array<string, string>
     */
    private function balances(Collection $stocks): array
    {
        return $stocks->mapWithKeys(
            fn (PondStock $stock): array => [$this->key($stock->batch_id, $stock->location_id) => (string) $stock->quantity]
        )->all();
    }

    /** @param Collection<int, PondStock> $stocks
     * @return array<string, mixed>
     */
    private function snapshot(StockMovement $transaction, Collection $stocks): array
    {
        return [
            'transaction_number' => $transaction->transaction_number,
            'transaction_date' => $transaction->transaction_date->format('Y-m-d H:i:s'),
            'batch_id' => (int) $transaction->batch_id,
            'from_location_id' => (int) $transaction->from_location_id,
            'to_location_id' => (int) $transaction->to_location_id,
            'quantity' => (string) $transaction->quantity,
            'notes' => $transaction->notes,
            'stock_balances' => $this->balances($stocks),
        ];
    }

    private function key(int $batchId, int $locationId): string
    {
        return "{$batchId}:{$locationId}";
    }

    /** @param array<int, int|string> $ids
     * @return list<int>
     */
    private function ids(array $ids): array
    {
        return collect($ids)->map(fn (int|string $id): int => (int) $id)->unique()->sort()->values()->all();
    }
}
