<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            UserSeeder::class,
            LocationSeeder::class,
            VendorSeeder::class,
            CommoditySeeder::class,
            FeedItemSeeder::class,
            CommodityBatchSeeder::class,
            StockingTransactionSeeder::class,
            StockAdjustmentSeeder::class,
            StockMovementSeeder::class,
            FeedingTransactionSeeder::class,
            PondStockSeeder::class,
            AuditLogSeeder::class,
        ]);

        if (app()->environment(['local', 'testing'])) {
            $this->call(LargeDemoSeeder::class);
        }
    }
}
