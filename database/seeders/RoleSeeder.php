<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => 'Admin', 'description' => 'Administrator master data, transaksi operasional, dan pelaporan tambak.'],
            ['name' => 'Manager', 'description' => 'Manajer transaksi operasional dan pelaporan tambak.'],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(['name' => $role['name']], $role);
        }
    }
}
