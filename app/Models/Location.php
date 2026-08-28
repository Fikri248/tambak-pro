<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Location extends Model
{
    use HasFactory;

    protected $fillable = [
        'parent_id',
        'code',
        'name',
        'location_type',
        'address',
        'description',
        'status',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function pondStocks(): HasMany
    {
        return $this->hasMany(PondStock::class);
    }

    public function stockingTransactions(): HasMany
    {
        return $this->hasMany(StockingTransaction::class);
    }

    public function outgoingMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'from_location_id');
    }

    public function incomingMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'to_location_id');
    }

    public function stockAdjustments(): HasMany
    {
        return $this->hasMany(StockAdjustment::class);
    }

    public function feedingTransactions(): HasMany
    {
        return $this->hasMany(FeedingTransaction::class);
    }

    /**
     * Return every descendant ID without assuming a fixed hierarchy depth.
     *
     * @return list<int>
     */
    public function descendantIds(): array
    {
        $descendantIds = [];
        $parentIds = [$this->getKey()];

        while ($parentIds !== []) {
            $childIds = self::query()
                ->whereIn('parent_id', $parentIds)
                ->whereNotIn('id', $descendantIds)
                ->pluck('id')
                ->map(fn ($id): int => (int) $id)
                ->all();

            if ($childIds === []) {
                break;
            }

            $descendantIds = array_values(array_unique([...$descendantIds, ...$childIds]));
            $parentIds = $childIds;
        }

        return $descendantIds;
    }

    public function wouldCreateCycleWith(self $parent): bool
    {
        if ($parent->is($this)) {
            return true;
        }

        return in_array($parent->getKey(), $this->descendantIds(), true);
    }

    /**
     * @return Collection<int, self>
     */
    public function hierarchy(): Collection
    {
        $locations = collect([$this]);
        $current = $this;

        while ($current->parent) {
            $current = $current->parent;
            $locations->prepend($current);
        }

        return $locations->values();
    }
}
