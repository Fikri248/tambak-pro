<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockAdjustment extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = [
        'transaction_number',
        'transaction_date',
        'location_id',
        'batch_id',
        'adjustment_type',
        'quantity_change',
        'quantity_before',
        'quantity_after',
        'reason',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'transaction_date' => 'datetime',
            'quantity_change' => 'decimal:3',
            'quantity_before' => 'decimal:3',
            'quantity_after' => 'decimal:3',
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

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
