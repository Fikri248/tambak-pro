<?php

namespace App\Support;

final class UserFacing
{
    public const ADJUSTMENT_TYPES = [
        'MORTALITY' => 'Kematian',
        'LOSS' => 'Kehilangan',
        'CORRECTION_IN' => 'Penyesuaian Tambah',
        'CORRECTION_OUT' => 'Penyesuaian Kurang',
        'OTHER' => 'Lainnya',
    ];

    public const TRANSACTION_TYPES = [
        'STOCKING' => 'Pembibitan',
        'MOVEMENT' => 'Pemindahan Stok',
        'ADJUSTMENT' => 'Perubahan Jumlah',
        'FEEDING' => 'Pemberian Pakan',
    ];

    public const VENDOR_TYPES = [
        'SEED' => 'Vendor Bibit',
        'FEED' => 'Vendor Pakan',
        'SERVICE' => 'Vendor Jasa',
        'MULTIPLE' => 'Vendor Beragam',
        'OTHER' => 'Lainnya',
    ];

    public const FEED_ITEM_TYPES = [
        'FEED' => 'Pakan',
        'NUTRITION' => 'Nutrisi',
        'MEDICINE' => 'Obat',
        'OTHER' => 'Lainnya',
    ];

    public const LOCATION_TYPES = [
        'AREA' => 'Area',
        'TAMBAK' => 'Tambak',
        'PETAK' => 'Petak',
        'OTHER' => 'Lainnya',
    ];

    public const STATUSES = [
        'ACTIVE' => 'Aktif',
        'INACTIVE' => 'Tidak Aktif',
    ];

    private function __construct() {}
}
