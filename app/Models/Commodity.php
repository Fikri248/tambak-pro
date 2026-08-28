<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Commodity extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'category',
        'unit',
        'description',
        'status',
    ];

    public function batches(): HasMany
    {
        return $this->hasMany(CommodityBatch::class);
    }

    public function pondStocks(): HasManyThrough
    {
        return $this->hasManyThrough(
            PondStock::class,
            CommodityBatch::class,
            'commodity_id',
            'batch_id',
        );
    }
}
