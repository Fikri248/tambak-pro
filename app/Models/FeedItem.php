<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeedItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'item_type_id',
        'item_type',
        'default_vendor_id',
        'unit',
        'default_price',
        'description',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'default_price' => 'decimal:2',
        ];
    }

    public function defaultVendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'default_vendor_id');
    }

    public function itemType(): BelongsTo
    {
        return $this->belongsTo(ItemType::class);
    }

    public function hasItemSemantic(string ...$semantics): bool
    {
        return $this->itemType?->hasSemantic(...$semantics) === true;
    }

    public function setItemTypeAttribute(?string $semantic): void
    {
        if ($semantic === null || $semantic === '') {
            return;
        }

        $this->attributes['item_type_id'] = ItemType::query()
            ->where('code', mb_strtoupper($semantic))
            ->valueOrFail('id');
    }

    public function feedingTransactions(): HasMany
    {
        return $this->hasMany(FeedingTransaction::class);
    }

    public function purchaseTransactions(): HasMany
    {
        return $this->hasMany(ItemPurchaseTransaction::class);
    }
}
