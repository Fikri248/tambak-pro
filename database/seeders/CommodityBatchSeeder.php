<?php

namespace Database\Seeders;

use App\Models\Commodity;
use App\Models\CommodityBatch;
use App\Models\Vendor;
use Illuminate\Database\Seeder;

class CommodityBatchSeeder extends Seeder
{
    public function run(): void
    {
        $vaname = Commodity::where('code', 'KMD-001')->firstOrFail();
        $bandeng = Commodity::where('code', 'KMD-003')->firstOrFail();
        $bibitLautJaya = Vendor::where('name', 'CV Bibit Laut Jaya')->firstOrFail();
        $minaMakmur = Vendor::where('name', 'CV Mina Makmur')->firstOrFail();

        $batches = [
            [
                'batch_code' => 'BT-001',
                'commodity_id' => $vaname->id,
                'vendor_id' => $bibitLautJaya->id,
                'purchase_date' => '2026-08-01',
                'initial_quantity' => 1000,
                'total_cost' => 500000,
                'unit_cost' => 500,
            ],
            [
                'batch_code' => 'BT-002',
                'commodity_id' => $vaname->id,
                'vendor_id' => $minaMakmur->id,
                'purchase_date' => '2026-08-05',
                'initial_quantity' => 600,
                'total_cost' => 330000,
                'unit_cost' => 550,
            ],
            [
                'batch_code' => 'BT-003',
                'commodity_id' => $bandeng->id,
                'vendor_id' => $bibitLautJaya->id,
                'purchase_date' => '2026-08-07',
                'initial_quantity' => 800,
                'total_cost' => 240000,
                'unit_cost' => 300,
            ],
        ];

        foreach ($batches as $batch) {
            CommodityBatch::updateOrCreate(
                ['batch_code' => $batch['batch_code']],
                $batch + ['status' => 'ACTIVE', 'notes' => null],
            );
        }
    }
}
