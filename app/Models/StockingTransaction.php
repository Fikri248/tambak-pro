<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockingTransaction extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = [
        'transaction_number',
        'transaction_date',
        'location_id',
        'batch_id',
        'quantity',
        'total_cost',
        'unit_cost',
        'created_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'transaction_date' => 'datetime',
            'quantity' => 'decimal:3',
            'total_cost' => 'decimal:2',
            'unit_cost' => 'decimal:4',
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
