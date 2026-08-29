<?php

namespace Database\Seeders;

use App\Models\CommodityBatch;
use App\Models\Location;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Database\Seeder;

class StockMovementSeeder extends Seeder
{
    public function run(): void
    {
        $abelAdmin = User::where('email', 'abel@tambak.local')->firstOrFail();
        $batch = CommodityBatch::where('batch_code', 'BT-001')->firstOrFail();
        $petakA1 = Location::where('code', 'PTK-A1')->firstOrFail();
        $petakB1 = Location::where('code', 'PTK-B1')->firstOrFail();

        $movement = StockMovement::firstOrNew([
            'transaction_number' => 'MOV-20260810-001',
        ]);
        $movement->fill([
            'transaction_date' => '2026-08-10 09:00:00',
            'batch_id' => $batch->id,
            'from_location_id' => $petakA1->id,
            'to_location_id' => $petakB1->id,
            'quantity' => 500,
            'notes' => 'Pemindahan stok 500 ekor BT-001 dari Petak A1 ke Petak B1.',
        ]);
        $movement->created_by ??= $abelAdmin->id;
        $movement->save();
    }
}
