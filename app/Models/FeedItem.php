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

    public function feedingTransactions(): HasMany
    {
        return $this->hasMany(FeedingTransaction::class);
    }
}
