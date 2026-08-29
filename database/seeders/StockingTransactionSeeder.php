<?php

namespace Database\Seeders;

use App\Models\CommodityBatch;
use App\Models\Location;
use App\Models\StockingTransaction;
use App\Models\User;
use Illuminate\Database\Seeder;

class StockingTransactionSeeder extends Seeder
{
    public function run(): void
    {
        $abelAdmin = User::where('email', 'abel@tambak.local')->firstOrFail();

        $transactions = [
            [
                'transaction_number' => 'STK-20260801-001',
                'transaction_date' => '2026-08-01 08:00:00',
                'location_code' => 'PTK-A1',
                'batch_code' => 'BT-001',
                'quantity' => 1000,
                'total_cost' => 500000,
                'unit_cost' => 500,
                'notes' => 'Pembibitan awal 1.000 ekor Udang Vaname ke Petak A1.',
            ],
            [
                'transaction_number' => 'STK-20260805-001',
                'transaction_date' => '2026-08-05 08:00:00',
                'location_code' => 'PTK-A2',
                'batch_code' => 'BT-002',
                'quantity' => 600,
                'total_cost' => 330000,
                'unit_cost' => 550,
                'notes' => 'Pembibitan awal 600 ekor Udang Vaname ke Petak A2.',
            ],
            [
                'transaction_number' => 'STK-20260807-001',
                'transaction_date' => '2026-08-07 08:00:00',
                'location_code' => 'PTK-B1',
                'batch_code' => 'BT-003',
                'quantity' => 800,
                'total_cost' => 240000,
                'unit_cost' => 300,
                'notes' => 'Pembibitan awal 800 ekor Bandeng ke Petak B1.',
            ],
        ];

        foreach ($transactions as $transaction) {
            $location = Location::where('code', $transaction['location_code'])->firstOrFail();
            $batch = CommodityBatch::where('batch_code', $transaction['batch_code'])->firstOrFail();

            $record = StockingTransaction::firstOrNew([
                'transaction_number' => $transaction['transaction_number'],
            ]);
            $record->fill([
                'transaction_date' => $transaction['transaction_date'],
                'location_id' => $location->id,
                'batch_id' => $batch->id,
                'quantity' => $transaction['quantity'],
                'total_cost' => $transaction['total_cost'],
                'unit_cost' => $transaction['unit_cost'],
                'notes' => $transaction['notes'],
            ]);
            $record->created_by ??= $abelAdmin->id;
            $record->save();
        }
    }
}
