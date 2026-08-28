<?php

namespace Database\Seeders;

use App\Models\FeedItem;
use App\Models\Vendor;
use Illuminate\Database\Seeder;

class FeedItemSeeder extends Seeder
{
    public function run(): void
    {
        $feedVendor = Vendor::where('name', 'PT Pakan Aqua Sejahtera')->firstOrFail();

        $items = [
            [
                'code' => 'PKN-001',
                'name' => 'Pakan Starter A',
                'item_type' => 'FEED',
                'default_vendor_id' => $feedVendor->id,
                'unit' => 'kg',
                'default_price' => 20000,
            ],
            [
                'code' => 'PKN-002',
                'name' => 'Pakan Grower B',
                'item_type' => 'FEED',
                'default_vendor_id' => $feedVendor->id,
                'unit' => 'kg',
                'default_price' => 25000,
            ],
            [
                'code' => 'NTR-001',
                'name' => 'Vitamin Aqua',
                'item_type' => 'NUTRITION',
                'default_vendor_id' => null,
                'unit' => 'liter',
                'default_price' => 50000,
            ],
            [
                'code' => 'OBT-001',
                'name' => 'Obat Tambak A',
                'item_type' => 'MEDICINE',
                'default_vendor_id' => null,
                'unit' => 'botol',
                'default_price' => 75000,
            ],
        ];

        foreach ($items as $item) {
            FeedItem::updateOrCreate(
                ['code' => $item['code']],
                $item + ['description' => null, 'status' => 'ACTIVE'],
            );
        }
    }
}
