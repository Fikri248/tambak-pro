<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemPurchaseTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_number', 'transaction_date', 'feed_item_id', 'vendor_id',
        'quantity', 'unit_cost', 'total_cost', 'created_by', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'transaction_date' => 'datetime',
            'quantity' => 'decimal:3',
            'unit_cost' => 'decimal:4',
            'total_cost' => 'decimal:2',
        ];
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
