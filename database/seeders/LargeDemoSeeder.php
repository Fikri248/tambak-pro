<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\Commodity;
use App\Models\CommodityBatch;
use App\Models\FeedingTransaction;
use App\Models\FeedItem;
use App\Models\Location;
use App\Models\PondStock;
use App\Models\Role;
use App\Models\StockAdjustment;
use App\Models\StockingTransaction;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Vendor;
use Carbon\CarbonImmutable;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use LogicException;

class LargeDemoSeeder extends Seeder
{
    use WithoutModelEvents;

    private const RECORD_COUNT = 500;

    private const CHUNK_SIZE = 100;

    private const TRANSACTION_NUMBER_BASE = 900_000_000;

    private const MARKER = '[LOCAL DEMO]';

    private const START_DATE = '2025-04-08 06:00:00';

    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new LogicException('LargeDemoSeeder hanya boleh dijalankan di lingkungan local atau testing.');
        }

        DB::transaction(function (): void {
            $this->callSilent(RoleSeeder::class);
            $this->ensureCanonicalDemoUsers();
            $actorId = (int) User::query()->where('email', 'fikri@tambak.local')->valueOrFail('id');
            $locations = $this->seedLocations();
            $vendors = $this->seedVendors();
            $commodities = $this->seedCommodities();
            $feedItems = $this->seedFeedItems($vendors);
            $batches = $this->seedBatches($commodities, $vendors);

            $this->seedOperations(
                actorId: $actorId,
                locations: $locations,
                commodities: $commodities,
                feedItems: $feedItems,
                batches: $batches,
            );
        }, 3);
    }

    private function ensureCanonicalDemoUsers(): void
    {
        $roles = Role::query()
            ->whereIn('name', ['Admin', 'Manager'])
            ->pluck('id', 'name');
        $password = Hash::make('password');

        foreach ([
            ['email' => 'fikri@tambak.local', 'name' => 'Fikri', 'role' => 'Admin'],
            ['email' => 'abel@tambak.local', 'name' => 'Abel', 'role' => 'Manager'],
        ] as $identity) {
            User::query()->firstOrCreate(
                ['email' => $identity['email']],
                [
                    'role_id' => (int) $roles->get($identity['role']),
                    'name' => $identity['name'],
                    'password' => $password,
                    'status' => 'ACTIVE',
                ],
            );
        }
    }

    /**
     * @return array{petak_ids: list<int>, petak_names: array<int, string>}
     */
    private function seedLocations(): array
    {
        $areaRows = [];

        for ($area = 1; $area <= 5; $area++) {
            $areaRows[] = [
                'parent_id' => null,
                'code' => sprintf('LDM-AREA-%02d', $area),
                'name' => sprintf('Area Demo Pesisir %02d', $area),
                'location_type' => 'AREA',
                'address' => sprintf('Wilayah pesisir demo %02d, Jawa Timur', $area),
                'description' => self::MARKER.' Area untuk pengujian data berskala besar.',
                'status' => 'ACTIVE',
            ];
        }

        $this->assertOwnedNamespaceAvailable(Location::class, 'code', $areaRows, 'description');
        $this->upsertInChunks(
            Location::class,
            $areaRows,
            ['code'],
            ['parent_id', 'name', 'location_type', 'address', 'description', 'status'],
        );

        $areaIds = Location::query()
            ->whereIn('code', array_column($areaRows, 'code'))
            ->pluck('id', 'code');
        $tambakRows = [];

        for ($tambak = 1; $tambak <= 25; $tambak++) {
            $area = intdiv($tambak - 1, 5) + 1;
            $tambakRows[] = [
                'parent_id' => (int) $areaIds->get(sprintf('LDM-AREA-%02d', $area)),
                'code' => sprintf('LDM-TMB-%03d', $tambak),
                'name' => sprintf('Tambak Demo %03d', $tambak),
                'location_type' => 'TAMBAK',
                'address' => null,
                'description' => self::MARKER.' Unit tambak untuk pengujian hierarki dan filter.',
                'status' => 'ACTIVE',
            ];
        }

        $this->assertOwnedNamespaceAvailable(Location::class, 'code', $tambakRows, 'description');
        $this->upsertInChunks(
            Location::class,
            $tambakRows,
            ['code'],
            ['parent_id', 'name', 'location_type', 'address', 'description', 'status'],
        );

        $tambakIds = Location::query()
            ->whereIn('code', array_column($tambakRows, 'code'))
            ->pluck('id', 'code');
        $petakRows = [];

        for ($petak = 1; $petak <= self::RECORD_COUNT; $petak++) {
            $tambak = intdiv($petak - 1, 20) + 1;
            $petakRows[] = [
                'parent_id' => (int) $tambakIds->get(sprintf('LDM-TMB-%03d', $tambak)),
                'code' => sprintf('LDM-PTK-%04d', $petak),
                'name' => sprintf('Petak Demo %04d', $petak),
                'location_type' => 'PETAK',
                'address' => null,
                'description' => $petak % 50 === 0
                    ? self::MARKER.' Petak dengan deskripsi panjang untuk menguji tampilan tabel, pilihan, dan modal pada berbagai ukuran layar.'
                    : self::MARKER.' Petak pengujian data besar.',
                'status' => 'ACTIVE',
            ];
        }

        $this->assertOwnedNamespaceAvailable(Location::class, 'code', $petakRows, 'description');
        $this->upsertInChunks(
            Location::class,
            $petakRows,
            ['code'],
            ['parent_id', 'name', 'location_type', 'address', 'description', 'status'],
        );

        $petakModels = Location::query()
            ->whereIn('code', array_column($petakRows, 'code'))
            ->orderBy('code')
            ->get(['id', 'name']);

        if ($petakModels->count() !== self::RECORD_COUNT) {
            throw new LogicException('LargeDemoSeeder gagal menyiapkan 500 Petak demo.');
        }

        return [
            'petak_ids' => $petakModels->pluck('id')->map(fn ($id): int => (int) $id)->all(),
            'petak_names' => $petakModels->mapWithKeys(
                fn (Location $location): array => [(int) $location->id => $location->name],
            )->all(),
        ];
    }

    /**
     * @return array{ids: array<string, int>, seed_codes: list<string>, feed_codes: list<string>}
     */
    private function seedVendors(): array
    {
        $types = ['SEED', 'FEED', 'SERVICE', 'MULTIPLE', 'OTHER'];
        $rows = [];
        $seedCodes = [];
        $feedCodes = [];

        for ($index = 1; $index <= self::RECORD_COUNT; $index++) {
            $type = $types[($index - 1) % count($types)];
            $code = sprintf('LDM-VND-%04d', $index);
            $status = $index % 20 === 0 ? 'INACTIVE' : 'ACTIVE';
            $rows[] = [
                'code' => $code,
                'name' => $index % 50 === 0
                    ? sprintf('Vendor Demo Budidaya Perairan Terpadu Cabang Pantai Utara Nomor %04d', $index)
                    : sprintf('Vendor Demo %04d', $index),
                'vendor_type' => $type,
                'phone' => sprintf('+62-811-90%04d', $index),
                'email' => sprintf('vendor.%04d@demo.tambak.local', $index),
                'address' => $index % 25 === 0
                    ? sprintf('Jalan Pesisir Budidaya Nomor %d, Kecamatan Demo, Kabupaten Situbondo, Jawa Timur', $index)
                    : sprintf('Kawasan Budidaya Demo Blok %04d', $index),
                'description' => self::MARKER.' Vendor sintetis untuk pengujian pencarian dan filter.',
                'status' => $status,
            ];

            if ($status === 'ACTIVE' && in_array($type, ['SEED', 'MULTIPLE'], true)) {
                $seedCodes[] = $code;
            }

            if ($status === 'ACTIVE' && in_array($type, ['FEED', 'MULTIPLE'], true)) {
                $feedCodes[] = $code;
            }
        }

        $this->assertOwnedNamespaceAvailable(Vendor::class, 'code', $rows, 'description');
        $this->upsertInChunks(
            Vendor::class,
            $rows,
            ['code'],
            ['name', 'vendor_type', 'phone', 'email', 'address', 'description', 'status'],
        );

        $ids = Vendor::query()
            ->whereIn('code', array_column($rows, 'code'))
            ->pluck('id', 'code')
            ->map(fn ($id): int => (int) $id)
            ->all();

        return [
            'ids' => $ids,
            'seed_codes' => $seedCodes,
            'feed_codes' => $feedCodes,
        ];
    }

    /**
     * @return array{ids: array<string, int>, names: array<string, string>, units: array<string, string>}
     */
    private function seedCommodities(): array
    {
        $catalogue = [
            ['Udang Vaname', 'Udang', 'ekor'],
            ['Udang Windu', 'Udang', 'ekor'],
            ['Bandeng', 'Ikan', 'ekor'],
            ['Nila Salin', 'Ikan', 'ekor'],
            ['Kepiting Bakau', 'Krustasea', 'ekor'],
            ['Rumput Laut', 'Rumput Laut', 'kg'],
            ['Kerapu', 'Ikan', 'ekor'],
            ['Kerang Hijau', 'Moluska', 'ekor'],
        ];
        $rows = [];
        $names = [];
        $units = [];

        for ($index = 1; $index <= self::RECORD_COUNT; $index++) {
            [$baseName, $category, $unit] = $catalogue[($index - 1) % count($catalogue)];
            $code = sprintf('LDM-KMD-%04d', $index);
            $name = sprintf('%s Demo Varietas %04d', $baseName, $index);
            $rows[] = [
                'code' => $code,
                'name' => $name,
                'category' => $category,
                'unit' => $unit,
                'description' => self::MARKER.' Komoditas sintetis untuk pengujian data besar.',
                'status' => 'ACTIVE',
            ];
            $names[$code] = $name;
            $units[$code] = $unit;
        }

        $this->assertOwnedNamespaceAvailable(Commodity::class, 'code', $rows, 'description');
        $this->upsertInChunks(
            Commodity::class,
            $rows,
            ['code'],
            ['name', 'category', 'unit', 'description', 'status'],
        );

        $ids = Commodity::query()
            ->whereIn('code', array_column($rows, 'code'))
            ->pluck('id', 'code')
            ->map(fn ($id): int => (int) $id)
            ->all();

        return compact('ids', 'names', 'units');
    }

    /**
     * @param  array{ids: array<string, int>, seed_codes: list<string>, feed_codes: list<string>}  $vendors
     * @return array{ids: array<string, int>, names: array<string, string>, units: array<string, string>, prices: array<string, string>, vendorIds: array<string, int>}
     */
    private function seedFeedItems(array $vendors): array
    {
        $types = ['FEED', 'NUTRITION', 'MEDICINE', 'OTHER'];
        $unitsByType = [
            'FEED' => 'kg',
            'NUTRITION' => 'liter',
            'MEDICINE' => 'botol',
            'OTHER' => 'sachet',
        ];
        $rows = [];
        $names = [];
        $units = [];
        $prices = [];
        $vendorIds = [];

        for ($index = 1; $index <= self::RECORD_COUNT; $index++) {
            $type = $types[($index - 1) % count($types)];
            $code = sprintf('LDM-PKN-%04d', $index);
            $vendorCode = $vendors['feed_codes'][($index - 1) % count($vendors['feed_codes'])];
            $price = number_format(12_500 + ($index * 25), 2, '.', '');
            $name = match ($type) {
                'FEED' => sprintf('Pakan Demo Formula %04d', $index),
                'NUTRITION' => sprintf('Nutrisi Demo Formula %04d', $index),
                'MEDICINE' => sprintf('Obat Tambak Demo Formula %04d', $index),
                default => sprintf('Kebutuhan Tambak Demo %04d', $index),
            };
            $rows[] = [
                'code' => $code,
                'name' => $name,
                'item_type' => $type,
                'default_vendor_id' => $vendors['ids'][$vendorCode],
                'unit' => $unitsByType[$type],
                'default_price' => $price,
                'description' => self::MARKER.' Item operasional sintetis untuk pengujian data besar.',
                'status' => 'ACTIVE',
            ];
            $names[$code] = $name;
            $units[$code] = $unitsByType[$type];
            $prices[$code] = $price;
            $vendorIds[$code] = $vendors['ids'][$vendorCode];
        }

        $this->assertOwnedNamespaceAvailable(FeedItem::class, 'code', $rows, 'description');
        $this->upsertInChunks(
            FeedItem::class,
            $rows,
            ['code'],
            ['name', 'item_type', 'default_vendor_id', 'unit', 'default_price', 'description', 'status'],
        );

        $ids = FeedItem::query()
            ->whereIn('code', array_column($rows, 'code'))
            ->pluck('id', 'code')
            ->map(fn ($id): int => (int) $id)
            ->all();

        return compact('ids', 'names', 'units', 'prices', 'vendorIds');
    }

    /**
     * @param  array{ids: array<string, int>, names: array<string, string>, units: array<string, string>}  $commodities
     * @param  array{ids: array<string, int>, seed_codes: list<string>, feed_codes: list<string>}  $vendors
     * @return array{ids: array<string, int>, quantities: array<string, string>, unitCosts: array<string, string>, totalCosts: array<string, string>}
     */
    private function seedBatches(array $commodities, array $vendors): array
    {
        $start = CarbonImmutable::parse(self::START_DATE);
        $rows = [];
        $quantities = [];
        $unitCosts = [];
        $totalCosts = [];

        for ($index = 1; $index <= self::RECORD_COUNT; $index++) {
            $batchCode = sprintf('LDM-BT-%04d', $index);
            $commodityCode = sprintf('LDM-KMD-%04d', $index);
            $vendorCode = $vendors['seed_codes'][($index - 1) % count($vendors['seed_codes'])];
            $quantity = number_format(1_000 + $index, 3, '.', '');
            $unitCost = number_format(400 + ($index % 201), 4, '.', '');
            $totalCost = number_format((1_000 + $index) * (400 + ($index % 201)), 2, '.', '');
            $rows[] = [
                'batch_code' => $batchCode,
                'commodity_id' => $commodities['ids'][$commodityCode],
                'vendor_id' => $vendors['ids'][$vendorCode],
                'purchase_date' => $start->addDays($index - 1)->toDateString(),
                'initial_quantity' => $quantity,
                'total_cost' => $totalCost,
                'unit_cost' => $unitCost,
                'status' => 'ACTIVE',
                'notes' => self::MARKER.' Batch pembibitan sintetis '.sprintf('%04d', $index).'.',
            ];
            $quantities[$batchCode] = $quantity;
            $unitCosts[$batchCode] = $unitCost;
            $totalCosts[$batchCode] = $totalCost;
        }

        $this->assertOwnedNamespaceAvailable(CommodityBatch::class, 'batch_code', $rows, 'notes');
        $this->upsertInChunks(
            CommodityBatch::class,
            $rows,
            ['batch_code'],
            ['commodity_id', 'vendor_id', 'purchase_date', 'initial_quantity', 'total_cost', 'unit_cost', 'status', 'notes'],
        );

        $ids = CommodityBatch::query()
            ->whereIn('batch_code', array_column($rows, 'batch_code'))
            ->pluck('id', 'batch_code')
            ->map(fn ($id): int => (int) $id)
            ->all();

        return compact('ids', 'quantities', 'unitCosts', 'totalCosts');
    }

    /**
     * @param  array{petak_ids: list<int>, petak_names: array<int, string>}  $locations
     * @param  array{ids: array<string, int>, names: array<string, string>, units: array<string, string>}  $commodities
     * @param  array{ids: array<string, int>, names: array<string, string>, units: array<string, string>, prices: array<string, string>, vendorIds: array<string, int>}  $feedItems
     * @param  array{ids: array<string, int>, quantities: array<string, string>, unitCosts: array<string, string>, totalCosts: array<string, string>}  $batches
     */
    private function seedOperations(
        int $actorId,
        array $locations,
        array $commodities,
        array $feedItems,
        array $batches,
    ): void {
        $start = CarbonImmutable::parse(self::START_DATE);
        $adjustmentTypes = ['MORTALITY', 'LOSS', 'CORRECTION_IN', 'CORRECTION_OUT', 'OTHER'];
        $stockingRows = [];
        $movementRows = [];
        $adjustmentRows = [];
        $feedingRows = [];
        $pondRows = [];
        $auditRows = [];

        for ($index = 1; $index <= self::RECORD_COUNT; $index++) {
            $batchCode = sprintf('LDM-BT-%04d', $index);
            $commodityCode = sprintf('LDM-KMD-%04d', $index);
            $feedCode = sprintf('LDM-PKN-%04d', $index);
            $batchId = $batches['ids'][$batchCode];
            $sourceId = $locations['petak_ids'][$index - 1];
            $destinationId = $locations['petak_ids'][$index % self::RECORD_COUNT];
            $day = $start->addDays($index - 1);
            $initialQuantity = $batches['quantities'][$batchCode];
            $movementQuantity = number_format(100 + ($index % 101), 3, '.', '');
            $sourceAfter = bcsub($initialQuantity, $movementQuantity, 3);
            $adjustmentType = $adjustmentTypes[($index - 1) % count($adjustmentTypes)];
            $adjustmentMagnitude = number_format(1 + ($index % 7), 3, '.', '');
            $isIncrease = $adjustmentType === 'CORRECTION_IN'
                || ($adjustmentType === 'OTHER' && intdiv($index - 1, count($adjustmentTypes)) % 2 === 0);
            $quantityChange = $isIncrease ? $adjustmentMagnitude : '-'.$adjustmentMagnitude;
            $destinationAfter = bcadd($movementQuantity, $quantityChange, 3);
            $feedQuantity = number_format(1 + ($index % 20) + (($index % 4) * 0.125), 3, '.', '');
            $feedUnitCost = number_format((float) $feedItems['prices'][$feedCode], 4, '.', '');
            $feedingTotalCost = bcadd(bcmul($feedQuantity, $feedUnitCost, 3), '0.005', 2);
            $stockingNumber = $this->transactionNumber('PBT', $index);
            $movementNumber = $this->transactionNumber('MUT', $index);
            $adjustmentNumber = $this->transactionNumber('ADJ', $index);
            $feedingNumber = $this->transactionNumber('FDG', $index);
            $sourceName = $locations['petak_names'][$sourceId];
            $destinationName = $locations['petak_names'][$destinationId];
            $commodityName = $commodities['names'][$commodityCode];
            $commodityUnit = $commodities['units'][$commodityCode];

            if ($sourceId === $destinationId || bccomp($sourceAfter, '0', 3) === -1 || bccomp($destinationAfter, '0', 3) === -1) {
                throw new LogicException('Simulasi stok LargeDemoSeeder menghasilkan operasi yang tidak valid.');
            }

            $stockingRows[] = [
                'transaction_number' => $stockingNumber,
                'transaction_date' => $day->setTime(6, 0),
                'location_id' => $sourceId,
                'batch_id' => $batchId,
                'quantity' => $initialQuantity,
                'total_cost' => $batches['totalCosts'][$batchCode],
                'unit_cost' => $batches['unitCosts'][$batchCode],
                'created_by' => $actorId,
                'notes' => self::MARKER.' Pembibitan sintetis '.sprintf('%04d', $index).'.',
                'created_at' => $day->setTime(6, 0),
            ];
            $movementRows[] = [
                'transaction_number' => $movementNumber,
                'transaction_date' => $day->setTime(8, 0),
                'batch_id' => $batchId,
                'from_location_id' => $sourceId,
                'to_location_id' => $destinationId,
                'quantity' => $movementQuantity,
                'created_by' => $actorId,
                'notes' => self::MARKER.' Pemindahan sintetis '.sprintf('%04d', $index).'.',
                'created_at' => $day->setTime(8, 0),
            ];
            $adjustmentRows[] = [
                'transaction_number' => $adjustmentNumber,
                'transaction_date' => $day->setTime(10, 0),
                'location_id' => $destinationId,
                'batch_id' => $batchId,
                'adjustment_type' => $adjustmentType,
                'quantity_change' => $quantityChange,
                'quantity_before' => $movementQuantity,
                'quantity_after' => $destinationAfter,
                'reason' => self::MARKER.' Perubahan jumlah sintetis '.sprintf('%04d', $index).'.',
                'created_by' => $actorId,
                'created_at' => $day->setTime(10, 0),
            ];
            $feedingRows[] = [
                'transaction_number' => $feedingNumber,
                'transaction_date' => $day->setTime(12, 0),
                'location_id' => $destinationId,
                'batch_id' => $batchId,
                'feed_item_id' => $feedItems['ids'][$feedCode],
                'vendor_id' => $feedItems['vendorIds'][$feedCode],
                'stock_quantity_snapshot' => $destinationAfter,
                'feed_quantity' => $feedQuantity,
                'unit_cost' => $feedUnitCost,
                'total_cost' => $feedingTotalCost,
                'created_by' => $actorId,
                'notes' => self::MARKER.' Pemberian pakan sintetis '.sprintf('%04d', $index).'.',
                'created_at' => $day->setTime(12, 0),
            ];
            $pondRows[] = [
                'location_id' => $sourceId,
                'batch_id' => $batchId,
                'quantity' => $sourceAfter,
            ];
            $pondRows[] = [
                'location_id' => $destinationId,
                'batch_id' => $batchId,
                'quantity' => $destinationAfter,
            ];

            $auditRows[] = $this->auditRow(
                module: 'STOCKING_TRANSACTION',
                transactionNumber: $stockingNumber,
                actorId: $actorId,
                description: "Pembibitan {$initialQuantity} {$commodityUnit} {$commodityName} ke {$sourceName}",
                oldValues: null,
                newValues: [
                    'batch_code' => $batchCode,
                    'commodity' => $commodityName,
                    'location_id' => $sourceId,
                    'location' => $sourceName,
                    'quantity' => $initialQuantity,
                    'total_cost' => $batches['totalCosts'][$batchCode],
                    'unit_cost' => $batches['unitCosts'][$batchCode],
                ],
                createdAt: $day->setTime(6, 0),
            );
            $auditRows[] = $this->auditRow(
                module: 'STOCK_MOVEMENT',
                transactionNumber: $movementNumber,
                actorId: $actorId,
                description: "Pemindahan stok {$movementQuantity} {$commodityUnit} Batch {$batchCode} dari {$sourceName} ke {$destinationName}",
                oldValues: [
                    'source_location_id' => $sourceId,
                    'source_quantity' => $initialQuantity,
                    'destination_location_id' => $destinationId,
                    'destination_quantity' => '0.000',
                ],
                newValues: [
                    'source_location_id' => $sourceId,
                    'source_quantity' => $sourceAfter,
                    'destination_location_id' => $destinationId,
                    'destination_quantity' => $movementQuantity,
                ],
                createdAt: $day->setTime(8, 0),
            );
            $auditRows[] = $this->auditRow(
                module: 'STOCK_ADJUSTMENT',
                transactionNumber: $adjustmentNumber,
                actorId: $actorId,
                description: "Perubahan jumlah {$batchCode} sebesar {$quantityChange} di {$destinationName}",
                oldValues: [
                    'location_id' => $destinationId,
                    'batch_code' => $batchCode,
                    'quantity' => $movementQuantity,
                ],
                newValues: [
                    'location_id' => $destinationId,
                    'batch_code' => $batchCode,
                    'quantity_change' => $quantityChange,
                    'quantity' => $destinationAfter,
                ],
                createdAt: $day->setTime(10, 0),
            );
            $auditRows[] = $this->auditRow(
                module: 'FEEDING_TRANSACTION',
                transactionNumber: $feedingNumber,
                actorId: $actorId,
                description: "Pemberian {$feedQuantity} {$feedItems['units'][$feedCode]} {$feedItems['names'][$feedCode]} di {$destinationName} untuk {$batchCode}",
                oldValues: null,
                newValues: [
                    'location_id' => $destinationId,
                    'batch_code' => $batchCode,
                    'feed_item' => $feedItems['names'][$feedCode],
                    'feed_quantity' => $feedQuantity,
                    'unit_cost' => $feedUnitCost,
                    'total_cost' => $feedingTotalCost,
                    'stock_quantity_snapshot' => $destinationAfter,
                ],
                createdAt: $day->setTime(12, 0),
            );
        }

        $this->assertDemoStockFixtureHasNoExternalActivity(
            locationIds: $locations['petak_ids'],
            batchIds: array_values($batches['ids']),
            stockingRows: $stockingRows,
            movementRows: $movementRows,
            adjustmentRows: $adjustmentRows,
        );

        $this->assertTransactionNamespaceAvailable(StockingTransaction::class, $stockingRows, 'notes');
        $this->assertTransactionNamespaceAvailable(StockMovement::class, $movementRows, 'notes');
        $this->assertTransactionNamespaceAvailable(StockAdjustment::class, $adjustmentRows, 'reason');
        $this->assertTransactionNamespaceAvailable(FeedingTransaction::class, $feedingRows, 'notes');

        $this->upsertInChunks(
            StockingTransaction::class,
            $stockingRows,
            ['transaction_number'],
            ['transaction_date', 'location_id', 'batch_id', 'quantity', 'total_cost', 'unit_cost', 'created_by', 'notes', 'created_at'],
        );
        $this->upsertInChunks(
            StockMovement::class,
            $movementRows,
            ['transaction_number'],
            ['transaction_date', 'batch_id', 'from_location_id', 'to_location_id', 'quantity', 'created_by', 'notes', 'created_at'],
        );
        $this->upsertInChunks(
            StockAdjustment::class,
            $adjustmentRows,
            ['transaction_number'],
            ['transaction_date', 'location_id', 'batch_id', 'adjustment_type', 'quantity_change', 'quantity_before', 'quantity_after', 'reason', 'created_by', 'created_at'],
        );
        $this->upsertInChunks(
            FeedingTransaction::class,
            $feedingRows,
            ['transaction_number'],
            ['transaction_date', 'location_id', 'batch_id', 'feed_item_id', 'vendor_id', 'stock_quantity_snapshot', 'feed_quantity', 'unit_cost', 'total_cost', 'created_by', 'notes', 'created_at'],
        );
        $this->upsertInChunks(
            PondStock::class,
            $pondRows,
            ['location_id', 'batch_id'],
            ['quantity'],
        );

        $recordIds = [
            'STOCKING_TRANSACTION' => StockingTransaction::query()
                ->whereIn('transaction_number', array_column($stockingRows, 'transaction_number'))
                ->pluck('id', 'transaction_number'),
            'STOCK_MOVEMENT' => StockMovement::query()
                ->whereIn('transaction_number', array_column($movementRows, 'transaction_number'))
                ->pluck('id', 'transaction_number'),
            'STOCK_ADJUSTMENT' => StockAdjustment::query()
                ->whereIn('transaction_number', array_column($adjustmentRows, 'transaction_number'))
                ->pluck('id', 'transaction_number'),
            'FEEDING_TRANSACTION' => FeedingTransaction::query()
                ->whereIn('transaction_number', array_column($feedingRows, 'transaction_number'))
                ->pluck('id', 'transaction_number'),
        ];

        foreach ($auditRows as &$auditRow) {
            $recordId = $recordIds[$auditRow['module']]->get($auditRow['transaction_number']);

            if (! $recordId) {
                throw new LogicException('AuditLog LargeDemoSeeder tidak menemukan transaksi sumber.');
            }

            $auditRow['record_id'] = (int) $recordId;
        }
        unset($auditRow);

        $this->syncAuditLogs($auditRows);
    }

    /**
     * A rerun may reset only the stock ledger that belongs entirely to this
     * deterministic fixture. Refuse to overwrite it after an ordinary
     * transaction has used a demo Petak or Batch, otherwise retained history
     * and the regenerated pond-stock balance could diverge.
     *
     * @param  list<int>  $locationIds
     * @param  list<int>  $batchIds
     * @param  list<array<string, mixed>>  $stockingRows
     * @param  list<array<string, mixed>>  $movementRows
     * @param  list<array<string, mixed>>  $adjustmentRows
     */
    private function assertDemoStockFixtureHasNoExternalActivity(
        array $locationIds,
        array $batchIds,
        array $stockingRows,
        array $movementRows,
        array $adjustmentRows,
    ): void {
        $demoLocations = array_fill_keys($locationIds, true);
        $demoBatches = array_fill_keys($batchIds, true);
        $expectedStocking = array_fill_keys(array_column($stockingRows, 'transaction_number'), true);
        $expectedMovements = array_fill_keys(array_column($movementRows, 'transaction_number'), true);
        $expectedAdjustments = array_fill_keys(array_column($adjustmentRows, 'transaction_number'), true);

        $externalStocking = StockingTransaction::query()
            ->get(['transaction_number', 'location_id', 'batch_id'])
            ->contains(fn (StockingTransaction $transaction): bool => ! isset($expectedStocking[$transaction->transaction_number])
                && (isset($demoLocations[(int) $transaction->location_id])
                    || isset($demoBatches[(int) $transaction->batch_id]))
            );
        $externalMovement = StockMovement::query()
            ->get(['transaction_number', 'from_location_id', 'to_location_id', 'batch_id'])
            ->contains(fn (StockMovement $transaction): bool => ! isset($expectedMovements[$transaction->transaction_number])
                && (isset($demoLocations[(int) $transaction->from_location_id])
                    || isset($demoLocations[(int) $transaction->to_location_id])
                    || isset($demoBatches[(int) $transaction->batch_id]))
            );
        $externalAdjustment = StockAdjustment::query()
            ->get(['transaction_number', 'location_id', 'batch_id'])
            ->contains(fn (StockAdjustment $transaction): bool => ! isset($expectedAdjustments[$transaction->transaction_number])
                && (isset($demoLocations[(int) $transaction->location_id])
                    || isset($demoBatches[(int) $transaction->batch_id]))
            );

        if ($externalStocking || $externalMovement || $externalAdjustment) {
            throw new LogicException(
                'LargeDemoSeeder tidak dijalankan ulang karena Petak atau Batch demo sudah digunakan oleh transaksi di luar fixture.',
            );
        }
    }

    /**
     * @param  class-string<Model>  $model
     * @param  list<array<string, mixed>>  $rows
     * @param  list<string>  $uniqueBy
     * @param  list<string>  $update
     */
    private function upsertInChunks(string $model, array $rows, array $uniqueBy, array $update): void
    {
        foreach (array_chunk($rows, self::CHUNK_SIZE) as $chunk) {
            $model::query()->upsert($chunk, $uniqueBy, $update);
        }
    }

    /**
     * @param  class-string<Model>  $model
     * @param  list<array<string, mixed>>  $rows
     */
    private function assertTransactionNamespaceAvailable(string $model, array $rows, string $markerColumn): void
    {
        $this->assertOwnedNamespaceAvailable($model, 'transaction_number', $rows, $markerColumn);
    }

    /**
     * @param  class-string<Model>  $model
     * @param  list<array<string, mixed>>  $rows
     */
    private function assertOwnedNamespaceAvailable(
        string $model,
        string $identityColumn,
        array $rows,
        string $markerColumn,
    ): void {
        $identities = array_column($rows, $identityColumn);

        foreach (array_chunk($identities, self::CHUNK_SIZE) as $chunk) {
            $conflict = $model::query()
                ->whereIn($identityColumn, $chunk)
                ->where(function ($query) use ($markerColumn): void {
                    $query->whereNull($markerColumn)
                        ->orWhere($markerColumn, 'not like', self::MARKER.'%');
                })
                ->exists();

            if ($conflict) {
                throw new LogicException('Namespace LargeDemoSeeder telah digunakan oleh data non-demo.');
            }
        }
    }

    private function transactionNumber(string $prefix, int $index): string
    {
        return sprintf('%s-%09d', $prefix, self::TRANSACTION_NUMBER_BASE + $index);
    }

    /**
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>  $newValues
     * @return array<string, mixed>
     */
    private function auditRow(
        string $module,
        string $transactionNumber,
        int $actorId,
        string $description,
        ?array $oldValues,
        array $newValues,
        CarbonImmutable $createdAt,
    ): array {
        return [
            'user_id' => $actorId,
            'action' => 'CREATE',
            'module' => $module,
            'record_id' => null,
            'transaction_number' => $transactionNumber,
            'description' => $description,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'created_at' => $createdAt,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function syncAuditLogs(array $rows): void
    {
        $existing = collect();

        foreach (array_chunk(array_column($rows, 'transaction_number'), self::CHUNK_SIZE) as $numbers) {
            $existing = $existing->concat(
                AuditLog::query()
                    ->where('action', 'CREATE')
                    ->whereIn('transaction_number', $numbers)
                    ->get(['id', 'module', 'transaction_number']),
            );
        }

        $existingIds = $existing->mapWithKeys(
            fn (AuditLog $log): array => [$log->module.'|'.$log->transaction_number => (int) $log->id],
        );
        $inserts = [];
        $updates = [];

        foreach ($rows as $row) {
            $row['old_values'] = $row['old_values'] === null
                ? null
                : json_encode($row['old_values'], JSON_THROW_ON_ERROR);
            $row['new_values'] = json_encode($row['new_values'], JSON_THROW_ON_ERROR);
            $id = $existingIds->get($row['module'].'|'.$row['transaction_number']);

            if ($id) {
                $updates[] = ['id' => $id, ...$row];
            } else {
                $inserts[] = $row;
            }
        }

        foreach (array_chunk($inserts, self::CHUNK_SIZE) as $chunk) {
            DB::table('audit_logs')->insert($chunk);
        }

        foreach (array_chunk($updates, self::CHUNK_SIZE) as $chunk) {
            DB::table('audit_logs')->upsert(
                $chunk,
                ['id'],
                ['user_id', 'action', 'module', 'record_id', 'transaction_number', 'description', 'old_values', 'new_values', 'created_at'],
            );
        }
    }
}
