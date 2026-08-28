<?php

namespace Database\Seeders;

use App\Models\Vendor;
use Illuminate\Database\Seeder;

class VendorSeeder extends Seeder
{
    public function run(): void
    {
        $vendors = [
            [
                'code' => 'VND-001',
                'name' => 'CV Bibit Laut Jaya',
                'vendor_type' => 'SEED',
                'address' => 'Situbondo, Jawa Timur',
                'description' => 'Pemasok bibit komoditas tambak.',
            ],
            [
                'code' => 'VND-002',
                'name' => 'PT Pakan Aqua Sejahtera',
                'vendor_type' => 'FEED',
                'address' => 'Surabaya, Jawa Timur',
                'description' => 'Pemasok pakan dan kebutuhan nutrisi tambak.',
            ],
            [
                'code' => 'VND-003',
                'name' => 'CV Mina Makmur',
                'vendor_type' => 'MULTIPLE',
                'address' => 'Banyuwangi, Jawa Timur',
                'description' => 'Pemasok berbagai kebutuhan budidaya.',
            ],
            [
                'code' => 'VND-004',
                'name' => 'Jasa Tambak Sentosa',
                'vendor_type' => 'SERVICE',
                'address' => 'Situbondo, Jawa Timur',
                'description' => 'Penyedia jasa pemeliharaan tambak.',
            ],
        ];

        foreach ($vendors as $vendor) {
            Vendor::updateOrCreate(
                ['code' => $vendor['code']],
                $vendor + ['phone' => null, 'email' => null, 'status' => 'ACTIVE'],
            );
        }
    }
}
