<?php

namespace Database\Seeders;

use App\Models\Commodity;
use Illuminate\Database\Seeder;

class CommoditySeeder extends Seeder
{
    public function run(): void
    {
        $commodities = [
            ['code' => 'KMD-001', 'name' => 'Udang Vaname', 'category' => 'Udang'],
            ['code' => 'KMD-002', 'name' => 'Udang Windu', 'category' => 'Udang'],
            ['code' => 'KMD-003', 'name' => 'Bandeng', 'category' => 'Ikan'],
            ['code' => 'KMD-004', 'name' => 'Ikan Nila', 'category' => 'Ikan'],
        ];

        foreach ($commodities as $commodity) {
            Commodity::updateOrCreate(
                ['code' => $commodity['code']],
                $commodity + ['unit' => 'ekor', 'description' => null, 'status' => 'ACTIVE'],
            );
        }
    }
}
