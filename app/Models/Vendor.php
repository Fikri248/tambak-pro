<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vendor extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'vendor_type_id',
        'vendor_type',
        'phone',
        'email',
        'address',
        'description',
        'status',
    ];

    public function vendorType(): BelongsTo
    {
        return $this->belongsTo(VendorType::class);
    }

    public function hasVendorSemantic(string ...$semantics): bool
    {
        return $this->vendorType?->hasSemantic(...$semantics) === true;
    }

    /**
     * Transitional compatibility for existing internal callers that still build
     * Vendor fixtures using the historical semantic code.
     */
    public function setVendorTypeAttribute(?string $semantic): void
    {
        if ($semantic === null || $semantic === '') {
            return;
        }

        $this->attributes['vendor_type_id'] = VendorType::query()
            ->where('code', mb_strtoupper($semantic))
            ->valueOrFail('id');
    }

    public function defaultFeedItems(): HasMany
    {
        return $this->hasMany(FeedItem::class, 'default_vendor_id');
    }

    public function commodityBatches(): HasMany
    {
        return $this->hasMany(CommodityBatch::class);
    }

    public function feedingTransactions(): HasMany
    {
        return $this->hasMany(FeedingTransaction::class);
    }

    public function itemPurchaseTransactions(): HasMany
    {
        return $this->hasMany(ItemPurchaseTransaction::class);
    }
}
