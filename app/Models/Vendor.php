<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vendor extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'vendor_type',
        'phone',
        'email',
        'address',
        'description',
        'status',
    ];

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
}
