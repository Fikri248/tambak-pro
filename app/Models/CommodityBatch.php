<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommodityBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'batch_code',
        'commodity_id',
        'vendor_id',
        'purchase_date',
        'initial_quantity',
        'total_cost',
        'unit_cost',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'purchase_date' => 'date',
            'initial_quantity' => 'decimal:3',
            'total_cost' => 'decimal:2',
            'unit_cost' => 'decimal:4',
        ];
    }

    public function commodity(): BelongsTo
    {
        return $this->belongsTo(Commodity::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function pondStocks(): HasMany
    {
        return $this->hasMany(PondStock::class, 'batch_id');
    }

    public function stockingTransactions(): HasMany
    {
        return $this->hasMany(StockingTransaction::class, 'batch_id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'batch_id');
    }

    public function adjustments(): HasMany
    {
        return $this->hasMany(StockAdjustment::class, 'batch_id');
    }

    public function feedingTransactions(): HasMany
    {
        return $this->hasMany(FeedingTransaction::class, 'batch_id');
    }
}
