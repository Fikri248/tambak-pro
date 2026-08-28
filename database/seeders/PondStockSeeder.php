<?php

namespace Database\Seeders;

use App\Models\CommodityBatch;
use App\Models\Location;
use App\Models\PondStock;
use Illuminate\Database\Seeder;

class PondStockSeeder extends Seeder
{
    public function run(): void
    {
        $stocks = [
            ['location_code' => 'PTK-A1', 'batch_code' => 'BT-001', 'quantity' => 400],
            ['location_code' => 'PTK-A2', 'batch_code' => 'BT-002', 'quantity' => 600],
            ['location_code' => 'PTK-B1', 'batch_code' => 'BT-001', 'quantity' => 500],
            ['location_code' => 'PTK-B1', 'batch_code' => 'BT-003', 'quantity' => 800],
        ];

        foreach ($stocks as $stock) {
            $location = Location::where('code', $stock['location_code'])->firstOrFail();
            $batch = CommodityBatch::where('batch_code', $stock['batch_code'])->firstOrFail();

            PondStock::updateOrCreate(
                ['location_id' => $location->id, 'batch_id' => $batch->id],
                ['quantity' => $stock['quantity']],
            );
        }
    }
}
