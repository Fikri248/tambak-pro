<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = [
        'transaction_number',
        'transaction_date',
        'batch_id',
        'from_location_id',
        'to_location_id',
        'quantity',
        'created_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'transaction_date' => 'datetime',
            'quantity' => 'decimal:3',
        ];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(CommodityBatch::class, 'batch_id');
    }

    public function fromLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'from_location_id');
    }

    public function toLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'to_location_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
