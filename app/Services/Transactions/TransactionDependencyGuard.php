<?php

namespace App\Services\Transactions;

use App\Models\FeedingTransaction;
use App\Models\StockAdjustment;
use App\Models\StockingTransaction;
use App\Models\StockMovement;

class TransactionDependencyGuard
{
    /** @param list<int> $locationIds */
    public function stockingHasDownstreamActivity(StockingTransaction $transaction, array $locationIds): bool
    {
        $createdAt = $transaction->getRawOriginal('created_at') ?? $transaction->created_at;
        $batchId = (int) $transaction->batch_id;
        $locationIds = $this->ids($locationIds);

        return StockMovement::query()
            ->where('batch_id', $batchId)
            ->where('created_at', '>=', $createdAt)
            ->exists()
            || StockAdjustment::query()
                ->where('batch_id', $batchId)
                ->where('created_at', '>=', $createdAt)
                ->exists()
            || FeedingTransaction::query()
                ->where('created_at', '>=', $createdAt)
                ->where(function ($query) use ($batchId, $locationIds): void {
                    $query->where('batch_id', $batchId);

                    if ($locationIds !== []) {
                        $query->orWhere(function ($query) use ($locationIds): void {
                            $query->whereNull('batch_id')->whereIn('location_id', $locationIds);
                        });
                    }
                })
                ->exists()
            || StockingTransaction::query()
                ->where('batch_id', $batchId)
                ->whereKeyNot($transaction->id)
                ->where('created_at', '>=', $createdAt)
                ->exists();
    }

    /**
     * @param  list<int>  $batchIds
     * @param  list<int>  $locationIds
     */
    public function movementHasDownstreamActivity(StockMovement $transaction, array $batchIds, array $locationIds): bool
    {
        $createdAt = $transaction->getRawOriginal('created_at') ?? $transaction->created_at;
        $batchIds = $this->ids($batchIds);
        $locationIds = $this->ids($locationIds);

        return StockMovement::query()
            ->whereKeyNot($transaction->id)
            ->whereIn('batch_id', $batchIds)
            ->where('created_at', '>=', $createdAt)
            ->where(function ($query) use ($locationIds): void {
                $query->whereIn('from_location_id', $locationIds)
                    ->orWhereIn('to_location_id', $locationIds);
            })
            ->exists()
            || StockAdjustment::query()
                ->whereIn('batch_id', $batchIds)
                ->whereIn('location_id', $locationIds)
                ->where('created_at', '>=', $createdAt)
                ->exists()
            || FeedingTransaction::query()
                ->whereIn('location_id', $locationIds)
                ->where('created_at', '>=', $createdAt)
                ->where(function ($query) use ($batchIds): void {
                    $query->whereIn('batch_id', $batchIds)->orWhereNull('batch_id');
                })
                ->exists()
            || StockingTransaction::query()
                ->whereIn('batch_id', $batchIds)
                ->where('batch_id', '!=', $transaction->batch_id)
                ->where('created_at', '>=', $createdAt)
                ->exists();
    }

    /** @param array<int, int|string> $ids
     * @return list<int>
     */
    private function ids(array $ids): array
    {
        return collect($ids)->map(fn (int|string $id): int => (int) $id)->unique()->sort()->values()->all();
    }
}
