<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\FeedingTransaction;
use App\Models\StockAdjustment;
use App\Models\StockingTransaction;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Database\Seeder;

class AuditLogSeeder extends Seeder
{
    public function run(): void
    {
        $fikri = User::where('email', 'fikri@tambak.local')->firstOrFail();
        $stocking = StockingTransaction::where('transaction_number', 'STK-20260801-001')->firstOrFail();
        $mortality = StockAdjustment::where('transaction_number', 'ADJ-20260802-001')->firstOrFail();
        $feeding = FeedingTransaction::where('transaction_number', 'FED-20260803-001')->firstOrFail();
        $movement = StockMovement::where('transaction_number', 'MOV-20260810-001')->firstOrFail();

        $logs = [
            [
                'module' => 'STOCKING_TRANSACTION',
                'transaction_number' => $stocking->transaction_number,
                'record_id' => $stocking->id,
                'description' => 'Pembibitan 1.000 ekor BT-001 ke Petak A1.',
                'old_values' => ['Petak A1' => 0],
                'new_values' => ['Petak A1' => 1000],
            ],
            [
                'module' => 'STOCK_ADJUSTMENT',
                'transaction_number' => $mortality->transaction_number,
                'record_id' => $mortality->id,
                'description' => 'Mortalitas 100 ekor BT-001 di Petak A1.',
                'old_values' => ['Petak A1' => 1000],
                'new_values' => ['Petak A1' => 900],
            ],
            [
                'module' => 'FEEDING_TRANSACTION',
                'transaction_number' => $feeding->transaction_number,
                'record_id' => $feeding->id,
                'description' => 'Pemberian 5 kg Pakan Starter A untuk BT-001 di Petak A1.',
                'old_values' => null,
                'new_values' => [
                    'feed_item' => 'Pakan Starter A',
                    'quantity' => 5,
                    'unit' => 'kg',
                    'total_cost' => 100000,
                ],
            ],
            [
                'module' => 'STOCK_MOVEMENT',
                'transaction_number' => $movement->transaction_number,
                'record_id' => $movement->id,
                'description' => 'Pemindahan stok 500 ekor BT-001 dari Petak A1 ke Petak B1',
                'old_values' => ['Petak A1' => 900, 'Petak B1' => 0],
                'new_values' => ['Petak A1' => 400, 'Petak B1' => 500],
            ],
        ];

        foreach ($logs as $log) {
            AuditLog::updateOrCreate(
                [
                    'module' => $log['module'],
                    'transaction_number' => $log['transaction_number'],
                ],
                [
                    'user_id' => $fikri->id,
                    'action' => 'CREATE',
                    'record_id' => $log['record_id'],
                    'description' => $log['description'],
                    'old_values' => $log['old_values'],
                    'new_values' => $log['new_values'],
                ],
            );
        }
    }
}
