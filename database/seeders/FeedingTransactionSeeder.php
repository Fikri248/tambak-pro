<?php

namespace Database\Seeders;

use App\Models\CommodityBatch;
use App\Models\FeedingTransaction;
use App\Models\FeedItem;
use App\Models\Location;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Seeder;

class FeedingTransactionSeeder extends Seeder
{
    public function run(): void
    {
        $fikri = User::where('email', 'fikri@tambak.local')->firstOrFail();
        $vendor = Vendor::where('name', 'PT Pakan Aqua Sejahtera')->firstOrFail();

        $transactions = [
            [
                'transaction_number' => 'FED-20260803-001',
                'transaction_date' => '2026-08-03 07:00:00',
                'location_code' => 'PTK-A1',
                'batch_code' => 'BT-001',
                'feed_code' => 'PKN-001',
                'stock_snapshot' => 900,
                'feed_quantity' => 5,
                'unit_cost' => 20000,
                'total_cost' => 100000,
                'notes' => 'Pemberian pakan starter untuk BT-001 sebelum pemindahan stok.',
            ],
            [
                'transaction_number' => 'FED-20260806-001',
                'transaction_date' => '2026-08-06 07:00:00',
                'location_code' => 'PTK-A2',
                'batch_code' => 'BT-002',
                'feed_code' => 'PKN-001',
                'stock_snapshot' => 600,
                'feed_quantity' => 4,
                'unit_cost' => 20000,
                'total_cost' => 80000,
                'notes' => 'Pemberian pakan starter untuk BT-002.',
            ],
            [
                'transaction_number' => 'FED-20260808-001',
                'transaction_date' => '2026-08-08 07:00:00',
                'location_code' => 'PTK-B1',
                'batch_code' => 'BT-003',
                'feed_code' => 'PKN-001',
                'stock_snapshot' => 800,
                'feed_quantity' => 6,
                'unit_cost' => 20000,
                'total_cost' => 120000,
                'notes' => 'Pemberian pakan starter untuk BT-003.',
            ],
            [
                'transaction_number' => 'FED-20260811-001',
                'transaction_date' => '2026-08-11 07:00:00',
                'location_code' => 'PTK-B1',
                'batch_code' => 'BT-001',
                'feed_code' => 'PKN-002',
                'stock_snapshot' => 500,
                'feed_quantity' => 4,
                'unit_cost' => 25000,
                'total_cost' => 100000,
                'notes' => 'Pemberian pakan grower setelah BT-001 dipindahkan ke Petak B1.',
            ],
        ];

        foreach ($transactions as $transaction) {
            $location = Location::where('code', $transaction['location_code'])->firstOrFail();
            $batch = CommodityBatch::where('batch_code', $transaction['batch_code'])->firstOrFail();
            $feedItem = FeedItem::where('code', $transaction['feed_code'])->firstOrFail();

            FeedingTransaction::updateOrCreate(
                ['transaction_number' => $transaction['transaction_number']],
                [
                    'transaction_date' => $transaction['transaction_date'],
                    'location_id' => $location->id,
                    'batch_id' => $batch->id,
                    'feed_item_id' => $feedItem->id,
                    'vendor_id' => $vendor->id,
                    'stock_quantity_snapshot' => $transaction['stock_snapshot'],
                    'feed_quantity' => $transaction['feed_quantity'],
                    'unit_cost' => $transaction['unit_cost'],
                    'total_cost' => $transaction['total_cost'],
                    'created_by' => $fikri->id,
                    'notes' => $transaction['notes'],
                ],
            );
        }
    }
}
