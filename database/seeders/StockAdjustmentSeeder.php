<?php

namespace Database\Seeders;

use App\Models\CommodityBatch;
use App\Models\Location;
use App\Models\StockAdjustment;
use App\Models\User;
use Illuminate\Database\Seeder;

class StockAdjustmentSeeder extends Seeder
{
    public function run(): void
    {
        $abelAdmin = User::where('email', 'abel@tambak.local')->firstOrFail();
        $petakA1 = Location::where('code', 'PTK-A1')->firstOrFail();
        $batch = CommodityBatch::where('batch_code', 'BT-001')->firstOrFail();

        $adjustment = StockAdjustment::firstOrNew([
            'transaction_number' => 'ADJ-20260802-001',
        ]);
        $adjustment->fill([
            'transaction_date' => '2026-08-02 07:30:00',
            'location_id' => $petakA1->id,
            'batch_id' => $batch->id,
            'adjustment_type' => 'MORTALITY',
            'quantity_change' => -100,
            'quantity_before' => 1000,
            'quantity_after' => 900,
            'reason' => 'Mortalitas 100 ekor setelah masa adaptasi awal.',
        ]);
        $adjustment->created_by ??= $abelAdmin->id;
        $adjustment->save();
    }
}
