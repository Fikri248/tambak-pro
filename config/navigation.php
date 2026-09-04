<?php

return [
    'sidebar' => [
        [
            'items' => [
                ['label' => 'Dashboard', 'icon' => 'dashboard', 'route' => 'dashboard', 'ability' => 'dashboard.view'],
            ],
        ],
        [
            'label' => 'MASTER DATA',
            'items' => [
                ['label' => 'Tambak', 'icon' => 'map', 'route' => 'tambak.index', 'active' => 'tambak.*', 'ability' => 'locations.view'],
                ['label' => 'Komoditas', 'icon' => 'package', 'route' => 'commodities.index', 'active' => 'commodities.*', 'ability' => 'commodities.view'],
                ['label' => 'Vendor', 'icon' => 'truck', 'route' => 'vendors.index', 'active' => 'vendors.*', 'ability' => 'vendors.view'],
                ['label' => 'Barang/Item', 'icon' => 'feed', 'route' => 'feed-items.index', 'active' => 'feed-items.*', 'ability' => 'feed-items.view'],
                ['label' => 'Chart of Accounts', 'icon' => 'coins', 'route' => 'chart-of-accounts.index', 'active' => 'chart-of-accounts.*', 'ability' => 'chart-of-accounts.view'],
            ],
        ],
        [
            'label' => 'TRANSAKSI',
            'ability' => 'operations.view',
            'items' => [
                ['label' => 'Pembibitan', 'icon' => 'seedling', 'route' => 'stocking.index', 'active' => 'stocking.*', 'ability' => 'stocking.view'],
                ['label' => 'Pemindahan Stok', 'icon' => 'transfer', 'route' => 'movements.index', 'active' => 'movements.*', 'ability' => 'movements.view'],
                ['label' => 'Perubahan Jumlah', 'icon' => 'adjustment', 'route' => 'adjustments.index', 'active' => 'adjustments.*', 'ability' => 'adjustments.view'],
                ['label' => 'Pembelian Barang/Item', 'icon' => 'coins', 'route' => 'item-purchases.index', 'active' => 'item-purchases.*', 'ability' => 'item-purchases.view'],
                ['label' => 'Penggunaan Barang/Item', 'icon' => 'feed', 'route' => 'feeding.index', 'active' => 'feeding.*', 'ability' => 'feeding.view'],
            ],
        ],
        [
            'label' => 'RIWAYAT',
            'ability' => 'transactions.view',
            'items' => [
                ['label' => 'Riwayat Transaksi', 'icon' => 'history', 'route' => 'history.index', 'active' => 'history.*', 'ability' => 'history.view'],
            ],
        ],
        [
            'label' => 'LAPORAN',
            'items' => [
                ['label' => 'Laporan Operasional', 'icon' => 'report', 'route' => 'reports.index', 'active' => 'reports.*', 'ability' => 'reports.view'],
            ],
        ],
    ],
];
