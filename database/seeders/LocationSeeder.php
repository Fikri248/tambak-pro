<?php

namespace Database\Seeders;

use App\Models\Location;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        $area = Location::updateOrCreate(
            ['code' => 'AREA-STB'],
            [
                'parent_id' => null,
                'name' => 'Area Situbondo',
                'location_type' => 'AREA',
                'address' => 'Kabupaten Situbondo, Jawa Timur',
                'description' => 'Area operasional tambak Situbondo.',
                'status' => 'ACTIVE',
            ],
        );

        $tambakA = Location::updateOrCreate(
            ['code' => 'TMB-A'],
            [
                'parent_id' => $area->id,
                'name' => 'Tambak A',
                'location_type' => 'TAMBAK',
                'address' => null,
                'description' => 'Unit Tambak A di Area Situbondo.',
                'status' => 'ACTIVE',
            ],
        );

        $tambakB = Location::updateOrCreate(
            ['code' => 'TMB-B'],
            [
                'parent_id' => $area->id,
                'name' => 'Tambak B',
                'location_type' => 'TAMBAK',
                'address' => null,
                'description' => 'Unit Tambak B di Area Situbondo.',
                'status' => 'ACTIVE',
            ],
        );

        $ponds = [
            ['code' => 'PTK-A1', 'parent_id' => $tambakA->id, 'name' => 'Petak A1'],
            ['code' => 'PTK-A2', 'parent_id' => $tambakA->id, 'name' => 'Petak A2'],
            ['code' => 'PTK-B1', 'parent_id' => $tambakB->id, 'name' => 'Petak B1'],
        ];

        foreach ($ponds as $pond) {
            Location::updateOrCreate(
                ['code' => $pond['code']],
                [
                    'parent_id' => $pond['parent_id'],
                    'name' => $pond['name'],
                    'location_type' => 'PETAK',
                    'address' => null,
                    'description' => null,
                    'status' => 'ACTIVE',
                ],
            );
        }
    }
}
