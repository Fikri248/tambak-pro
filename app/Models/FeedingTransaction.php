<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeedingTransaction extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = [
        'transaction_number',
        'transaction_date',
        'location_id',
        'batch_id',
        'feed_item_id',
        'vendor_id',
        'stock_quantity_snapshot',
        'feed_quantity',
        'unit_cost',
        'total_cost',
        'created_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'transaction_date' => 'datetime',
            'stock_quantity_snapshot' => 'decimal:3',
            'feed_quantity' => 'decimal:3',
            'unit_cost' => 'decimal:4',
            'total_cost' => 'decimal:2',
        ];
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(CommodityBatch::class, 'batch_id');
    }

    public function feedItem(): BelongsTo
    {
        return $this->belongsTo(FeedItem::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
