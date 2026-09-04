<?php

namespace App\Services\Reports;

use App\Exports\Reports\ReportExportDefinition;
use App\Models\VendorType;
use App\Support\DecimalDisplay;
use App\Support\PageSize;
use App\Support\UserFacing;
use Carbon\Carbon;
use Closure;
use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class OperationalReportService
{
    private const REPORT_FILENAME_PREFIXES = [
        'stock' => 'laporan-stok',
        'stocking' => 'laporan-pembibitan',
        'movements' => 'laporan-pemindahan-bibit',
        'adjustments' => 'laporan-perubahan-jumlah',
        'purchases' => 'laporan-pembelian-barang-item',
        'feeding' => 'laporan-pakan',
        'items' => 'laporan-barang-item',
        'vendors' => 'laporan-vendor',
        'commodities' => 'laporan-komoditas',
        'locations' => 'laporan-tambak-petak',
    ];

    /** @param array<string, mixed> $validated */
    public function filters(array $validated): array
    {
        $defaults = [
            'search' => '', 'area_id' => null, 'tambak_id' => null, 'location_id' => null,
            'commodity_id' => null, 'batch_id' => null, 'vendor_id' => null,
            'feed_item_id' => null, 'user_id' => null, 'type' => null,
            'status' => null, 'category' => null, 'date_from' => null, 'date_to' => null,
        ];
        $filters = array_replace($defaults, $validated);

        foreach (['area_id', 'tambak_id', 'location_id', 'commodity_id', 'batch_id', 'vendor_id', 'feed_item_id', 'user_id'] as $key) {
            $filters[$key] = $filters[$key] !== null ? (int) $filters[$key] : null;
        }

        $filters['search'] = (string) ($filters['search'] ?? '');
        unset($filters['format'], $filters['page']);

        return $filters;
    }

    /** @return array<string, mixed> */
    public function hub(): array
    {
        $positiveStock = DB::table('pond_stocks as ps')->where('ps.quantity', '>', 0);
        $metrics = [
            'current_stock' => (float) (clone $positiveStock)->sum('ps.quantity'),
            'stock_value' => (float) DB::table('pond_stocks as ps')
                ->join('commodity_batches as batch', 'batch.id', '=', 'ps.batch_id')
                ->where('ps.quantity', '>', 0)
                ->sum(DB::raw('ps.quantity * batch.unit_cost')),
            'purchase_cost' => (float) DB::table('item_purchase_transactions')->sum('total_cost'),
            'mortality' => abs((float) DB::table('stock_adjustments')->where('adjustment_type', 'MORTALITY')->sum('quantity_change')),
        ];

        return [
            'metrics' => $metrics,
            'summaryCards' => [
                $this->summary('Total Stok Saat Ini', $this->quantity($metrics['current_stock']), 'ekor', 'seedling'),
                $this->summary('Nilai Stok Saat Ini', $this->money($metrics['stock_value']), null, 'coins'),
                $this->summary('Nilai Pembelian Barang/Item', $this->decimalMoney($metrics['purchase_cost']), null, 'coins'),
                $this->summary('Kematian Tercatat', $this->quantity($metrics['mortality']), 'ekor', 'adjustment'),
            ],
            'reportCards' => [
                $this->reportCard('Stok Saat Ini', 'Posisi stok aktif per petak dan Batch.', route('reports.stock'), $this->quantity($metrics['current_stock']).' ekor', 'package'),
                $this->reportCard('Pembibitan', 'Riwayat bibit masuk dan nilai pembibitan.', route('reports.stocking'), DB::table('stocking_transactions')->count().' transaksi', 'seedling'),
                $this->reportCard('Pemindahan Stok', 'Pantau perpindahan stok antarpetak.', route('reports.movements'), DB::table('stock_movements')->count().' transaksi', 'transfer'),
                $this->reportCard('Perubahan Jumlah', 'Kematian, kehilangan, dan penyesuaian stok.', route('reports.adjustments'), DB::table('stock_adjustments')->count().' transaksi', 'adjustment'),
                $this->reportCard('Pembelian Barang/Item', 'Riwayat pengadaan Barang/Item dan biaya tercatat.', route('reports.purchases'), DB::table('item_purchase_transactions')->count().' transaksi', 'coins'),
                $this->reportCard('Barang/Item', 'Daftar master Barang/Item, jenis, Vendor default, dan status.', route('reports.items'), DB::table('feed_items')->count().' item', 'feed'),
                $this->reportCard('Vendor', 'Ringkasan keterlibatan Vendor operasional.', route('reports.vendors'), DB::table('vendors')->count().' Vendor', 'truck'),
                $this->reportCard('Komoditas', 'Posisi stok dan aktivitas per komoditas.', route('reports.commodities'), DB::table('commodities')->count().' komoditas', 'package'),
                $this->reportCard('Tambak & Petak', 'Ringkasan operasional berdasarkan lokasi.', route('reports.locations'), DB::table('locations')->where('location_type', 'PETAK')->count().' petak', 'map'),
            ],
        ];
    }

    /** @param array<string, mixed> $filters */
    public function export(string $report, array $filters): ReportExportDefinition
    {
        return match ($report) {
            'stock' => $this->stockExport($filters),
            'stocking' => $this->stockingExport($filters),
            'movements' => $this->movementExport($filters),
            'adjustments' => $this->adjustmentExport($filters),
            'purchases' => $this->purchaseExport($filters),
            'feeding' => $this->feedingExport($filters),
            'items' => $this->itemExport($filters),
            'vendors' => $this->vendorExport($filters),
            'commodities' => $this->commodityExport($filters),
            'locations' => $this->locationExport($filters),
            default => throw new \InvalidArgumentException('Jenis laporan tidak didukung.'),
        };
    }

    /** @param array<string, mixed> $filters */
    public function report(string $report, array $filters, bool $paginate = true, int $perPage = PageSize::DEFAULT): array
    {
        return match ($report) {
            'stock' => $this->stock($filters, $paginate, $perPage),
            'stocking' => $this->stocking($filters, $paginate, $perPage),
            'movements' => $this->movements($filters, $paginate, $perPage),
            'adjustments' => $this->adjustments($filters, $paginate, $perPage),
            'purchases' => $this->purchases($filters, $paginate, $perPage),
            'feeding' => $this->feeding($filters, $paginate, $perPage),
            'items' => $this->items($filters, $paginate, $perPage),
            'vendors' => $this->vendors($filters, $paginate, $perPage),
            'commodities' => $this->commodities($filters, $paginate, $perPage),
            'locations' => $this->locations($filters, $paginate, $perPage),
            default => throw new \InvalidArgumentException('Jenis laporan tidak didukung.'),
        };
    }

    /** @param array<string, mixed> $filters */
    public function document(string $report, array $filters): array
    {
        $data = $this->report($report, $filters, false);

        return $data + [
            'filters' => $filters,
            'filterSummary' => $this->humanReadableFilters($filters, $data['filterFields']),
            'generatedAt' => Carbon::now(config('app.timezone'))->locale('id')->translatedFormat('d F Y, H:i'),
            'filename' => $this->exportFilename(self::REPORT_FILENAME_PREFIXES[$report], $filters),
        ];
    }

    /** @param array<string, mixed> $filters */
    private function stockExport(array $filters): ReportExportDefinition
    {
        $query = $this->stockQuery($filters)
            ->select(['tambak.name as tambak_name', 'petak.name as petak_name', 'batch.batch_code', 'commodity.name as commodity_name', 'vendor.name as vendor_name', 'batch.purchase_date', 'ps.quantity', 'commodity.unit', 'batch.unit_cost'])
            ->selectRaw('ps.quantity * batch.unit_cost as stock_value')
            ->orderBy('tambak.name')->orderBy('petak.name')->orderBy('batch.batch_code')->orderBy('ps.id');

        return $this->definition('Laporan Stok Saat Ini', 'Stok Saat Ini', 'laporan-stok', [
            'Tambak', 'Petak', 'Batch', 'Komoditas', 'Vendor Bibit', 'Tanggal Pembelian',
            'Jumlah Saat Ini', 'Satuan', 'Harga per Satuan', 'Nilai Stok',
        ], $query, fn (object $row): array => [
            $row->tambak_name ?: 'Tanpa Tambak', $row->petak_name, $row->batch_code,
            $row->commodity_name, $row->vendor_name ?: '-', Carbon::parse($row->purchase_date)->format('Y-m-d'),
            (float) $row->quantity, $row->unit, (float) $row->unit_cost, (float) $row->stock_value,
        ], ['G' => '#,##0.000', 'I' => '#,##0.00', 'J' => '#,##0.00'], $filters);
    }

    /** @param array<string, mixed> $filters */
    private function stockingExport(array $filters): ReportExportDefinition
    {
        $query = $this->stockingQuery($filters)
            ->select(['st.transaction_number', 'st.transaction_date', 'tambak.name as tambak_name', 'petak.name as petak_name', 'batch.batch_code', 'commodity.name as commodity_name', 'vendor.name as vendor_name', 'st.quantity', 'commodity.unit', 'st.unit_cost', 'st.total_cost', 'creator.name as user_name', 'st.notes'])
            ->orderByDesc('st.transaction_date')->orderByDesc('st.created_at')->orderByDesc('st.id');

        return $this->definition('Laporan Pembibitan', 'Pembibitan', 'laporan-pembibitan', [
            'No. Transaksi', 'Tanggal', 'Tambak', 'Petak', 'Batch', 'Komoditas', 'Vendor',
            'Jumlah', 'Satuan', 'Harga per Satuan', 'Total Biaya', 'Dicatat Oleh', 'Catatan',
        ], $query, fn (object $row): array => [
            $row->transaction_number, Carbon::parse($row->transaction_date)->format('Y-m-d H:i:s'),
            $row->tambak_name ?: 'Tanpa Tambak', $row->petak_name, $row->batch_code, $row->commodity_name,
            $row->vendor_name ?: '-', (float) $row->quantity, $row->unit, (float) $row->unit_cost,
            (float) $row->total_cost, $row->user_name ?: 'Sistem', $row->notes ?: '',
        ], ['H' => '#,##0.000', 'J' => '#,##0.00', 'K' => '#,##0.00'], $filters);
    }

    /** @param array<string, mixed> $filters */
    private function movementExport(array $filters): ReportExportDefinition
    {
        $query = $this->movementQuery($filters)
            ->select(['sm.transaction_number', 'sm.transaction_date', 'batch.batch_code', 'commodity.name as commodity_name', 'source_location.name as source_name', 'destination_location.name as destination_name', 'sm.quantity', 'commodity.unit', 'creator.name as user_name', 'sm.notes'])
            ->orderByDesc('sm.transaction_date')->orderByDesc('sm.created_at')->orderByDesc('sm.id');

        return $this->definition('Laporan Pemindahan Stok', 'Pemindahan Stok', 'laporan-pemindahan-stok', [
            'No. Transaksi', 'Tanggal', 'Batch', 'Komoditas', 'Petak Asal', 'Petak Tujuan',
            'Jumlah Dipindahkan', 'Satuan', 'Dicatat Oleh', 'Catatan',
        ], $query, fn (object $row): array => [
            $row->transaction_number, Carbon::parse($row->transaction_date)->format('Y-m-d H:i:s'),
            $row->batch_code, $row->commodity_name, $row->source_name, $row->destination_name,
            (float) $row->quantity, $row->unit, $row->user_name ?: 'Sistem', $row->notes ?: '',
        ], ['G' => '#,##0.000'], $filters);
    }

    /** @param array<string, mixed> $filters */
    private function adjustmentExport(array $filters): ReportExportDefinition
    {
        $query = $this->adjustmentQuery($filters)
            ->select(['sa.transaction_number', 'sa.transaction_date', 'sa.adjustment_type', 'petak.name as petak_name', 'batch.batch_code', 'commodity.name as commodity_name', 'sa.quantity_before', 'sa.quantity_change', 'sa.quantity_after', 'commodity.unit', 'sa.reason', 'creator.name as user_name'])
            ->orderByDesc('sa.transaction_date')->orderByDesc('sa.created_at')->orderByDesc('sa.id');

        return $this->definition('Laporan Perubahan Jumlah', 'Perubahan Jumlah', 'laporan-perubahan-jumlah', [
            'No. Transaksi', 'Tanggal', 'Jenis', 'Petak', 'Batch', 'Komoditas', 'Jumlah Sebelum',
            'Perubahan', 'Jumlah Sesudah', 'Satuan', 'Alasan', 'Dicatat Oleh',
        ], $query, fn (object $row): array => [
            $row->transaction_number, Carbon::parse($row->transaction_date)->format('Y-m-d H:i:s'),
            UserFacing::ADJUSTMENT_TYPES[$row->adjustment_type] ?? 'Lainnya', $row->petak_name,
            $row->batch_code, $row->commodity_name, (float) $row->quantity_before,
            (float) $row->quantity_change, (float) $row->quantity_after, $row->unit,
            $row->reason ?: '', $row->user_name ?: 'Sistem',
        ], ['G' => '#,##0.000', 'H' => '+#,##0.000;-#,##0.000;0', 'I' => '#,##0.000'], $filters);
    }

    /** @param array<string, mixed> $filters */
    private function purchaseExport(array $filters): ReportExportDefinition
    {
        $query = $this->purchaseQuery($filters)
            ->select([
                'purchase.transaction_number', 'purchase.transaction_date', 'item.name as item_name',
                'item_type.name as item_type_name', 'vendor.name as vendor_name', 'purchase.quantity',
                'item.unit', 'purchase.unit_cost', 'purchase.total_cost', 'creator.name as user_name',
            ])
            ->orderByDesc('purchase.transaction_date')->orderByDesc('purchase.created_at')->orderByDesc('purchase.id');

        return $this->definition('Laporan Pembelian Barang/Item', 'Pembelian Barang Item', 'laporan-pembelian-barang-item', [
            'No. Transaksi', 'Tanggal', 'Barang/Item', 'Jenis Barang/Item', 'Vendor',
            'Jumlah', 'Satuan', 'Harga Satuan', 'Total Biaya', 'Dicatat Oleh',
        ], $query, fn (object $row): array => [
            $row->transaction_number, Carbon::parse($row->transaction_date)->format('Y-m-d H:i:s'),
            $row->item_name, $row->item_type_name, $row->vendor_name, (float) $row->quantity,
            $row->unit, (float) $row->unit_cost, (float) $row->total_cost, $row->user_name ?: 'Sistem',
        ], ['F' => '#,##0.###', 'H' => '#,##0.####', 'I' => '#,##0.##'], $filters);
    }

    /** @param array<string, mixed> $filters */
    private function feedingExport(array $filters): ReportExportDefinition
    {
        $query = $this->feedingQuery($filters)
            ->select(['ft.transaction_number', 'ft.transaction_date', 'tambak.name as tambak_name', 'petak.name as petak_name', 'batch.batch_code', 'commodity.name as commodity_name', 'commodity.unit as stock_unit', 'item.name as item_name', 'item_type.name as item_type_name', 'item.unit as item_unit', 'vendor.name as vendor_name', 'ft.stock_quantity_snapshot', 'ft.feed_quantity', 'ft.unit_cost', 'ft.total_cost', 'creator.name as user_name', 'ft.notes'])
            ->orderByDesc('ft.transaction_date')->orderByDesc('ft.created_at')->orderByDesc('ft.id');

        return $this->definition('Laporan Penggunaan Barang/Item', 'Penggunaan Barang/Item', 'laporan-penggunaan-barang-item', [
            'No. Transaksi', 'Tanggal', 'Tambak', 'Petak', 'Cakupan', 'Komoditas', 'Barang/Item',
            'Jenis Barang/Item', 'Vendor', 'Stok Saat Pencatatan', 'Satuan Stok', 'Jumlah Penggunaan',
            'Satuan', 'Harga per Satuan', 'Total Biaya', 'Dicatat Oleh', 'Catatan',
        ], $query, fn (object $row): array => [
            $row->transaction_number, Carbon::parse($row->transaction_date)->format('Y-m-d H:i:s'),
            $row->tambak_name ?: 'Tanpa Tambak', $row->petak_name, $row->batch_code ?: 'Seluruh Petak',
            $row->commodity_name ?: '-', $row->item_name, $row->item_type_name,
            $row->vendor_name ?: '-', $row->stock_quantity_snapshot !== null ? (float) $row->stock_quantity_snapshot : null,
            $row->stock_unit ?: 'ekor', (float) $row->feed_quantity, $row->item_unit,
            (float) $row->unit_cost, (float) $row->total_cost, $row->user_name ?: 'Sistem', $row->notes ?: '',
        ], ['J' => '#,##0.000', 'L' => '#,##0.000', 'N' => '#,##0.00', 'O' => '#,##0.00'], $filters);
    }

    /** @param array<string, mixed> $filters */
    private function itemExport(array $filters): ReportExportDefinition
    {
        $query = $this->itemQuery($filters)
            ->select([
                'item.code', 'item.name', 'item_type.name as item_type_name', 'item.unit',
                'vendor.name as vendor_name', 'item.default_price', 'item.status',
            ])
            ->orderBy('item.name')->orderBy('item.code')->orderBy('item.id');

        return $this->definition('Laporan Barang/Item', 'Barang Item', 'laporan-barang-item', [
            'Kode', 'Nama Barang/Item', 'Jenis Barang/Item', 'Satuan', 'Vendor Default', 'Harga Default', 'Status',
        ], $query, fn (object $row): array => [
            $row->code, $row->name, $row->item_type_name, $row->unit, $row->vendor_name ?: '-',
            (float) $row->default_price, $row->status === 'ACTIVE' ? 'Aktif' : 'Tidak Aktif',
        ], ['F' => '#,##0.####'], $filters);
    }

    /** @param array<string, mixed> $filters */
    private function vendorExport(array $filters): ReportExportDefinition
    {
        $query = $this->vendorQuery($filters)
            ->select(['vendor.code', 'vendor.name', 'vendor_type.name as vendor_type_name', 'vendor.status'])
            ->selectRaw('COALESCE(batch_usage.batch_count, 0) as batch_count, COALESCE(stocking_usage.stocking_value, 0) as stocking_value, COALESCE(feeding_usage.feeding_count, 0) as feeding_count, COALESCE(feeding_usage.feeding_cost, 0) as feeding_cost')
            ->orderBy('vendor.name')->orderBy('vendor.code')->orderBy('vendor.id');

        return $this->definition('Laporan Vendor', 'Vendor', 'laporan-vendor', [
            'Kode Vendor', 'Vendor', 'Jenis', 'Status', 'Jumlah Batch', 'Nilai Pembibitan',
            'Jumlah Transaksi Penggunaan', 'Biaya Penggunaan Barang/Item',
        ], $query, fn (object $row): array => [
            $row->code, $row->name, $row->vendor_type_name,
            $row->status === 'ACTIVE' ? 'Aktif' : 'Tidak Aktif', (int) $row->batch_count,
            (float) $row->stocking_value, (int) $row->feeding_count, (float) $row->feeding_cost,
        ], ['E' => '0', 'F' => '#,##0.00', 'G' => '0', 'H' => '#,##0.00'], $filters);
    }

    /** @param array<string, mixed> $filters */
    private function commodityExport(array $filters): ReportExportDefinition
    {
        $query = $this->commodityQuery($filters)
            ->select(['commodity.code', 'commodity.name', 'commodity.category', 'commodity.unit', 'commodity.status'])
            ->selectRaw('COALESCE(batch_data.batch_count, 0) as batch_count, COALESCE(stock_data.current_stock, 0) as current_stock, COALESCE(stocking_data.stocked_quantity, 0) as stocked_quantity, COALESCE(adjustment_data.mortality, 0) as mortality, COALESCE(adjustment_data.loss, 0) as loss, COALESCE(stock_data.stock_value, 0) as stock_value')
            ->orderBy('commodity.name')->orderBy('commodity.code')->orderBy('commodity.id');

        return $this->definition('Laporan Komoditas', 'Komoditas', 'laporan-komoditas', [
            'Kode', 'Komoditas', 'Kategori', 'Satuan', 'Status', 'Jumlah Batch', 'Stok Saat Ini',
            'Bibit Masuk', 'Kematian', 'Kehilangan', 'Nilai Stok Saat Ini',
        ], $query, fn (object $row): array => [
            $row->code, $row->name, $row->category ?: '-', $row->unit,
            $row->status === 'ACTIVE' ? 'Aktif' : 'Tidak Aktif', (int) $row->batch_count,
            (float) $row->current_stock, (float) $row->stocked_quantity, (float) $row->mortality,
            (float) $row->loss, (float) $row->stock_value,
        ], ['F' => '0', 'G' => '#,##0.000', 'H' => '#,##0.000', 'I' => '#,##0.000', 'J' => '#,##0.000', 'K' => '#,##0.00'], $filters);
    }

    /** @param array<string, mixed> $filters */
    private function locationExport(array $filters): ReportExportDefinition
    {
        $query = $this->locationQuery($filters)
            ->select(['area.name as area_name', 'tambak.name as tambak_name', 'petak.name as petak_name', 'petak.status'])
            ->selectRaw('COALESCE(stock_data.batch_count, 0) as batch_count, COALESCE(stock_data.commodity_count, 0) as commodity_count, COALESCE(stock_data.current_stock, 0) as current_stock, COALESCE(stocking_data.stocked_quantity, 0) as stocked_quantity, COALESCE(adjustment_data.mortality, 0) as mortality, COALESCE(feeding_data.feeding_cost, 0) as feeding_cost, activity_data.last_activity')
            ->orderBy('tambak.name')->orderBy('petak.name')->orderBy('petak.id');

        return $this->definition('Laporan Tambak & Petak', 'Tambak & Petak', 'laporan-tambak-petak', [
            'Area', 'Tambak', 'Petak', 'Status', 'Batch Berstok', 'Jumlah Komoditas',
            'Stok Saat Ini', 'Pembibitan', 'Kematian', 'Biaya Penggunaan Barang/Item', 'Aktivitas Terakhir',
        ], $query, fn (object $row): array => [
            $row->area_name ?: '-', $row->tambak_name ?: 'Tanpa Tambak', $row->petak_name,
            $row->status === 'ACTIVE' ? 'Aktif' : 'Tidak Aktif', (int) $row->batch_count,
            (int) $row->commodity_count, (float) $row->current_stock, (float) $row->stocked_quantity,
            (float) $row->mortality, (float) $row->feeding_cost,
            $row->last_activity ? Carbon::parse($row->last_activity)->format('Y-m-d H:i:s') : '',
        ], ['E' => '0', 'F' => '0', 'G' => '#,##0.000', 'H' => '#,##0.000', 'I' => '#,##0.000', 'J' => '#,##0.00'], $filters);
    }

    /** @param array<string, mixed> $filters */
    public function stock(array $filters, bool $paginate = true, int $perPage = PageSize::DEFAULT): array
    {
        $query = $this->stockQuery($filters);

        $metrics = [
            'total_stock' => (float) (clone $query)->sum('ps.quantity'),
            'batches' => (clone $query)->distinct()->count('ps.batch_id'),
            'locations' => (clone $query)->distinct()->count('ps.location_id'),
            'commodities' => (clone $query)->distinct()->count('batch.commodity_id'),
        ];

        $rows = $this->rows(
            $query->select([
                'ps.id', 'ps.quantity', 'petak.id as petak_id', 'petak.name as petak_name',
                'tambak.name as tambak_name', 'batch.id as batch_id', 'batch.batch_code',
                'batch.purchase_date', 'batch.unit_cost', 'commodity.id as commodity_id',
                'commodity.name as commodity_name', 'commodity.unit', 'vendor.name as vendor_name',
            ])->orderBy('tambak.name')->orderBy('petak.name')->orderBy('batch.batch_code'),
            function (object $row): array {
                $quantity = (float) $row->quantity;
                $unitCost = (float) $row->unit_cost;

                return $this->row([
                    $row->tambak_name ?: 'Tanpa Tambak',
                    $this->cell($row->petak_name, route('tambak.show', $row->petak_id), false, 'center'),
                    $this->cell($row->batch_code, null, false, 'center', true),
                    $this->cell($row->commodity_name, route('commodities.show', $row->commodity_id)),
                    $row->vendor_name ?: '—',
                    $this->cell(Carbon::parse($row->purchase_date)->locale('id')->translatedFormat('d M Y'), null, false, 'center'),
                    $this->cell($this->quantity($quantity).' '.$row->unit, null, false, 'right'),
                    $this->cell($this->money($unitCost), null, false, 'right'),
                    $this->cell($this->money($quantity * $unitCost), null, false, 'right'),
                ]);
            },
            $paginate,
            $perPage,
        );

        return $this->page(
            'Laporan Stok Saat Ini',
            'Lihat posisi stok bibit terkini berdasarkan tambak, petak, komoditas, dan Batch.',
            route('reports.stock'),
            $metrics,
            [
                $this->summary('Total Stok Saat Ini', $this->quantity($metrics['total_stock']), 'ekor', 'seedling'),
                $this->summary('Batch Berstok', $metrics['batches'], null, 'package'),
                $this->summary('Petak Berisi Stok', $metrics['locations'], 'petak', 'map'),
                $this->summary('Komoditas di Stok', $metrics['commodities'], 'komoditas', 'building'),
            ],
            $this->stockFilterFields(),
            ['Tambak', 'Petak', 'Batch', 'Komoditas', 'Vendor Bibit', 'Tanggal Pembelian', 'Jumlah Saat Ini', 'Harga per Satuan', 'Nilai Stok'],
            $rows,
            'Posisi Stok per Batch'
        );
    }

    /** @param array<string, mixed> $filters */
    public function stocking(array $filters, bool $paginate = true, int $perPage = PageSize::DEFAULT): array
    {
        $query = $this->stockingQuery($filters);

        $metrics = [
            'total' => (clone $query)->count('st.id'),
            'quantity' => (float) (clone $query)->sum('st.quantity'),
            'cost' => (float) (clone $query)->sum('st.total_cost'),
            'batches' => (clone $query)->distinct()->count('st.batch_id'),
        ];
        $rows = $this->rows(
            $query->select(['st.*', 'petak.name as petak_name', 'batch.batch_code', 'commodity.name as commodity_name', 'commodity.unit', 'vendor.name as vendor_name', 'creator.name as user_name'])
                ->orderByDesc('st.transaction_date')->orderByDesc('st.created_at')->orderByDesc('st.id'),
            fn (object $row): array => $this->row([
                $this->cell($row->transaction_number, route('stocking.show', $row->id), false, 'left', true),
                $this->cell(Carbon::parse($row->transaction_date)->locale('id')->translatedFormat('d M Y, H:i'), null, false, 'center'),
                $this->cell($row->petak_name, null, false, 'center'),
                $this->cell($row->batch_code, null, false, 'center', true),
                $row->commodity_name,
                $row->vendor_name ?: '—',
                $this->cell($this->quantity((float) $row->quantity).' '.$row->unit, null, false, 'right'),
                $this->cell($this->money((float) $row->unit_cost), null, false, 'right'),
                $this->cell($this->money((float) $row->total_cost), null, false, 'right'),
                $row->user_name ?: 'Sistem',
            ]),
            $paginate,
            $perPage,
        );

        return $this->page('Laporan Pembibitan', 'Rekap bibit masuk, batch, dan nilai pembibitan yang tercatat.', route('reports.stocking'), $metrics, [
            $this->summary('Total Transaksi', $metrics['total'], null, 'history'),
            $this->summary('Total Bibit Masuk', $this->quantity($metrics['quantity']), 'ekor', 'seedling'),
            $this->summary('Total Nilai Pembibitan', $this->money($metrics['cost']), null, 'coins'),
            $this->summary('Batch Dibuat', $metrics['batches'], null, 'package'),
        ], $this->transactionFilterFields(['tambak', 'location', 'commodity', 'vendor', 'user']), ['No. Transaksi', 'Tanggal', 'Petak', 'Batch', 'Komoditas', 'Vendor', 'Jumlah', 'Harga per Satuan', 'Total Biaya', 'Dicatat Oleh'], $rows, 'Transaksi Pembibitan');
    }

    /** @param array<string, mixed> $filters */
    public function movements(array $filters, bool $paginate = true, int $perPage = PageSize::DEFAULT): array
    {
        $query = $this->movementQuery($filters);

        $involved = (clone $query)->select('sm.from_location_id as location_id')
            ->union((clone $query)->select('sm.to_location_id as location_id'));
        $metrics = [
            'total' => (clone $query)->count('sm.id'),
            'quantity' => (float) (clone $query)->sum('sm.quantity'),
            'batches' => (clone $query)->distinct()->count('sm.batch_id'),
            'locations' => DB::query()->fromSub($involved, 'involved')->count(),
        ];
        $rows = $this->rows(
            $query->select(['sm.*', 'source_location.name as source_name', 'destination_location.name as destination_name', 'batch.batch_code', 'commodity.name as commodity_name', 'commodity.unit', 'creator.name as user_name'])
                ->orderByDesc('sm.transaction_date')->orderByDesc('sm.created_at')->orderByDesc('sm.id'),
            fn (object $row): array => $this->row([
                $this->cell($row->transaction_number, route('movements.show', $row->id), false, 'left', true),
                $this->cell(Carbon::parse($row->transaction_date)->locale('id')->translatedFormat('d M Y, H:i'), null, false, 'center'),
                $this->cell($row->batch_code, null, false, 'center', true),
                $row->commodity_name,
                $this->cell($row->source_name, null, false, 'center'),
                $this->cell($row->destination_name, null, false, 'center'),
                $this->cell($this->quantity((float) $row->quantity).' '.$row->unit, null, false, 'right'),
                $row->user_name ?: 'Sistem',
            ]),
            $paginate,
            $perPage,
        );

        return $this->page('Laporan Pemindahan Stok', 'Pantau perpindahan stok antarpetak.', route('reports.movements'), $metrics, [
            $this->summary('Total Pemindahan', $metrics['total'], null, 'transfer'),
            $this->summary('Total Stok Dipindahkan', $this->quantity($metrics['quantity']), 'ekor', 'seedling'),
            $this->summary('Batch Dipindahkan', $metrics['batches'], null, 'package'),
            $this->summary('Petak Terlibat', $metrics['locations'], 'petak', 'map'),
        ], $this->transactionFilterFields(['location', 'commodity', 'batch', 'user']), ['No. Transaksi', 'Tanggal', 'Batch', 'Komoditas', 'Petak Asal', 'Petak Tujuan', 'Jumlah Dipindahkan', 'Dicatat Oleh'], $rows, 'Transaksi Pemindahan Stok');
    }

    /** @param array<string, mixed> $filters */
    public function adjustments(array $filters, bool $paginate = true, int $perPage = PageSize::DEFAULT): array
    {
        $query = $this->adjustmentQuery($filters);

        $metrics = [
            'total' => (clone $query)->count('sa.id'),
            'mortality' => abs((float) (clone $query)->where('sa.adjustment_type', 'MORTALITY')->sum('sa.quantity_change')),
            'loss' => abs((float) (clone $query)->where('sa.adjustment_type', 'LOSS')->sum('sa.quantity_change')),
            'net_correction' => (float) (clone $query)->whereIn('sa.adjustment_type', ['CORRECTION_IN', 'CORRECTION_OUT'])->sum('sa.quantity_change'),
        ];
        $rows = $this->rows(
            $query->select(['sa.*', 'petak.name as petak_name', 'batch.batch_code', 'commodity.name as commodity_name', 'commodity.unit', 'creator.name as user_name'])
                ->orderByDesc('sa.transaction_date')->orderByDesc('sa.created_at')->orderByDesc('sa.id'),
            function (object $row): array {
                $change = (float) $row->quantity_change;

                return $this->row([
                    $this->cell($row->transaction_number, route('adjustments.show', $row->id), false, 'left', true),
                    $this->cell(Carbon::parse($row->transaction_date)->locale('id')->translatedFormat('d M Y, H:i'), null, false, 'center'),
                    $this->cell(UserFacing::ADJUSTMENT_TYPES[$row->adjustment_type] ?? 'Lainnya', null, true, 'center'),
                    $this->cell($row->petak_name, null, false, 'center'),
                    $this->cell($row->batch_code, null, false, 'center', true),
                    $row->commodity_name,
                    $this->cell($this->quantity((float) $row->quantity_before), null, false, 'right'),
                    $this->cell(($change > 0 ? '+' : '').$this->quantity($change), null, false, 'right'),
                    $this->cell($this->quantity((float) $row->quantity_after), null, false, 'right'),
                    $row->reason ?: '—',
                    $row->user_name ?: 'Sistem',
                ]);
            },
            $paginate,
            $perPage,
        );

        return $this->page('Laporan Perubahan Jumlah', 'Ringkasan kematian, kehilangan, dan penyesuaian stok bibit.', route('reports.adjustments'), $metrics, [
            $this->summary('Total Perubahan', $metrics['total'], null, 'adjustment'),
            $this->summary('Total Kematian', $this->quantity($metrics['mortality']), 'ekor', 'warning'),
            $this->summary('Total Kehilangan', $this->quantity($metrics['loss']), 'ekor', 'package'),
            $this->summary('Net Koreksi', ($metrics['net_correction'] > 0 ? '+' : '').$this->quantity($metrics['net_correction']), 'ekor', 'check'),
        ], array_merge($this->transactionFilterFields(['location', 'commodity', 'batch', 'user']), [$this->selectField('type', 'Semua Jenis', $this->mapOptions(UserFacing::ADJUSTMENT_TYPES))]), ['No. Transaksi', 'Tanggal', 'Jenis', 'Petak', 'Batch', 'Komoditas', 'Sebelum', 'Perubahan', 'Sesudah', 'Alasan', 'Dicatat Oleh'], $rows, 'Transaksi Perubahan Jumlah');
    }

    /** @param array<string, mixed> $filters */
    public function purchases(array $filters, bool $paginate = true, int $perPage = PageSize::DEFAULT): array
    {
        $query = $this->purchaseQuery($filters);
        $metrics = [
            'total' => (clone $query)->count('purchase.id'),
            'cost' => (float) (clone $query)->sum('purchase.total_cost'),
            'items' => (clone $query)->distinct()->count('purchase.feed_item_id'),
            'vendors' => (clone $query)->distinct()->count('purchase.vendor_id'),
        ];
        $rows = $this->rows(
            $query->select([
                'purchase.*', 'item.name as item_name', 'item.unit', 'item_type.name as item_type_name',
                'vendor.name as vendor_name', 'creator.name as user_name',
            ])->orderByDesc('purchase.transaction_date')->orderByDesc('purchase.created_at')->orderByDesc('purchase.id'),
            fn (object $row): array => $this->row([
                $this->cell($row->transaction_number, route('item-purchases.show', $row->id), false, 'left', true),
                $this->cell(Carbon::parse($row->transaction_date)->locale('id')->translatedFormat('d M Y, H:i'), null, false, 'center'),
                $row->item_name,
                $this->cell($row->item_type_name, null, true, 'center'),
                $row->vendor_name,
                $this->cell(DecimalDisplay::localized((string) $row->quantity).' '.$row->unit, null, false, 'right'),
                $this->cell($this->decimalMoney($row->unit_cost), null, false, 'right'),
                $this->cell($this->decimalMoney($row->total_cost), null, false, 'right'),
                $row->user_name ?: 'Sistem',
            ]),
            $paginate,
            $perPage,
        );

        return $this->page(
            'Laporan Pembelian Barang/Item',
            'Rekap transaksi pengadaan Barang/Item tanpa mengubah saldo inventori.',
            route('reports.purchases'),
            $metrics,
            [
                $this->summary('Total Transaksi', $metrics['total'], null, 'history'),
                $this->summary('Total Biaya Pembelian', $this->decimalMoney($metrics['cost']), null, 'coins'),
                $this->summary('Barang/Item Dibeli', $metrics['items'], null, 'package'),
                $this->summary('Vendor Terlibat', $metrics['vendors'], null, 'truck'),
            ],
            $this->itemTransactionFilterFields(),
            ['No. Transaksi', 'Tanggal', 'Barang/Item', 'Jenis Barang/Item', 'Vendor', 'Jumlah', 'Harga Satuan', 'Total Biaya', 'Dicatat Oleh'],
            $rows,
            'Transaksi Pembelian Barang/Item',
        );
    }

    /** @param array<string, mixed> $filters */
    public function feeding(array $filters, bool $paginate = true, int $perPage = PageSize::DEFAULT): array
    {
        $query = $this->feedingQuery($filters);

        $metrics = [
            'total' => (clone $query)->count('ft.id'),
            'cost' => (float) (clone $query)->sum('ft.total_cost'),
            'items' => (clone $query)->distinct()->count('ft.feed_item_id'),
            'locations' => (clone $query)->distinct()->count('ft.location_id'),
        ];
        $usage = (clone $query)->select(['item.id', 'item.name', 'item_type.name as item_type_name', 'item.unit'])
            ->selectRaw('COUNT(ft.id) as transaction_count, SUM(ft.feed_quantity) as total_quantity, SUM(ft.total_cost) as total_cost')
            ->groupBy('item.id', 'item.name', 'item_type.name', 'item.unit')
            ->orderByDesc('total_cost')->limit(10)->get()->map(fn (object $row): array => $this->row([
                $this->cell($row->name, route('feed-items.show', $row->id)),
                $this->cell($row->item_type_name, null, true, 'center'),
                $this->cell($row->unit, null, false, 'center'),
                $this->cell($row->transaction_count.' transaksi', null, false, 'right'),
                $this->cell($this->quantity((float) $row->total_quantity).' '.$row->unit, null, false, 'right'),
                $this->cell($this->money((float) $row->total_cost), null, false, 'right'),
            ]))->all();
        $rows = $this->rows(
            $query->select(['ft.*', 'petak.name as petak_name', 'batch.batch_code', 'commodity.name as commodity_name', 'item.name as item_name', 'item_type.name as item_type_name', 'item.unit', 'vendor.name as vendor_name', 'creator.name as user_name'])
                ->orderByDesc('ft.transaction_date')->orderByDesc('ft.created_at')->orderByDesc('ft.id'),
            fn (object $row): array => $this->row([
                $this->cell($row->transaction_number, route('feeding.show', $row->id), false, 'left', true),
                $this->cell(Carbon::parse($row->transaction_date)->locale('id')->translatedFormat('d M Y, H:i'), null, false, 'center'),
                $this->cell($row->petak_name, null, false, 'center'),
                $this->cell($row->batch_code ?: 'Seluruh Petak', null, false, 'center'),
                $row->item_name,
                $this->cell($row->item_type_name, null, true, 'center'),
                $row->vendor_name ?: '—',
                $this->cell($this->quantity((float) $row->feed_quantity).' '.$row->unit, null, false, 'right'),
                $this->cell($this->money((float) $row->unit_cost), null, false, 'right'),
                $this->cell($this->money((float) $row->total_cost), null, false, 'right'),
                $this->cell($row->stock_quantity_snapshot !== null ? $this->quantity((float) $row->stock_quantity_snapshot).' ekor' : '—', null, false, 'right'),
                $row->user_name ?: 'Sistem',
            ]),
            $paginate,
            $perPage,
        );

        $page = $this->page('Laporan Penggunaan Barang/Item', 'Pantau penggunaan dan biaya Barang/Item yang tercatat.', route('reports.feeding'), $metrics, [
            $this->summary('Total Transaksi', $metrics['total'], null, 'feed'),
            $this->summary('Total Biaya', $this->money($metrics['cost']), null, 'coins'),
            $this->summary('Barang/Item Terpakai', $metrics['items'], null, 'package'),
            $this->summary('Petak Terlayani', $metrics['locations'], 'petak', 'map'),
        ], array_merge($this->transactionFilterFields(['location', 'commodity', 'vendor', 'user']), [
            $this->selectField('type', 'Semua Jenis Barang/Item', $this->options('item_types', 'id', 'name')),
            $this->selectField('feed_item_id', 'Semua Barang/Item', $this->options('feed_items', 'id', 'name')),
        ]), ['No. Transaksi', 'Tanggal', 'Petak', 'Cakupan', 'Barang/Item', 'Jenis Barang/Item', 'Vendor', 'Jumlah', 'Harga per Satuan', 'Total Biaya', 'Stok Saat Pencatatan', 'Dicatat Oleh'], $rows, 'Transaksi Penggunaan Barang/Item');
        $page['secondary'] = [
            'title' => 'Ringkasan per Item',
            'description' => 'Jumlah hanya dijumlahkan dalam item dengan satuan yang sama.',
            'columns' => ['Item', 'Jenis', 'Satuan', 'Jumlah Transaksi', 'Total Penggunaan', 'Total Biaya'],
            'rows' => $usage,
        ];

        return $page;
    }

    /** @param array<string, mixed> $filters */
    public function items(array $filters, bool $paginate = true, int $perPage = PageSize::DEFAULT): array
    {
        $query = $this->itemQuery($filters);
        $metrics = [
            'total' => (clone $query)->count('item.id'),
            'active' => (clone $query)->where('item.status', 'ACTIVE')->count('item.id'),
            'inactive' => (clone $query)->where('item.status', 'INACTIVE')->count('item.id'),
            'types' => (clone $query)->distinct()->count('item.item_type_id'),
        ];
        $rows = $this->rows(
            $query->select([
                'item.id', 'item.code', 'item.name', 'item.unit', 'item.default_price', 'item.status',
                'item_type.name as item_type_name', 'vendor.name as vendor_name',
            ])->orderBy('item.name')->orderBy('item.code')->orderBy('item.id'),
            fn (object $row): array => $this->row([
                $this->cell($row->code, route('feed-items.show', $row->id), false, 'left', true),
                $row->name,
                $this->cell($row->item_type_name, null, true, 'center'),
                $this->cell($row->unit, null, false, 'center'),
                $row->vendor_name ?: '—',
                $this->cell($this->decimalMoney($row->default_price), null, false, 'right'),
                $this->cell($row->status === 'ACTIVE' ? 'Aktif' : 'Tidak Aktif', null, true, 'center'),
            ]),
            $paginate,
            $perPage,
        );

        return $this->page(
            'Laporan Barang/Item',
            'Daftar master Barang/Item beserta jenis, satuan, Vendor default, harga default, dan status.',
            route('reports.items'),
            $metrics,
            [
                $this->summary('Total Barang/Item', $metrics['total'], null, 'package'),
                $this->summary('Aktif', $metrics['active'], null, 'check'),
                $this->summary('Tidak Aktif', $metrics['inactive'], null, 'power'),
                $this->summary('Jumlah Jenis', $metrics['types'], null, 'feed'),
            ],
            $this->itemFilterFields(),
            ['Kode', 'Nama Barang/Item', 'Jenis Barang/Item', 'Satuan', 'Vendor Default', 'Harga Default', 'Status'],
            $rows,
            'Daftar Barang/Item',
        );
    }

    /** @param array<string, mixed> $filters */
    public function vendors(array $filters, bool $paginate = true, int $perPage = PageSize::DEFAULT): array
    {
        $query = $this->vendorQuery($filters);

        $metrics = [
            'total' => (clone $query)->count('vendor.id'),
            'active' => (clone $query)->where('vendor.status', 'ACTIVE')->count('vendor.id'),
            'seed_used' => (clone $query)->whereIn(
                'vendor_type.semantic_type',
                [VendorType::SEMANTIC_SEED, VendorType::SEMANTIC_MULTIPLE],
            )->whereRaw('COALESCE(batch_usage.batch_count, 0) > 0')->count('vendor.id'),
            'feed_used' => (clone $query)->whereRaw('COALESCE(feeding_usage.feeding_count, 0) > 0')->count('vendor.id'),
        ];
        $rows = $this->rows(
            $query->select(['vendor.*', 'vendor_type.name as vendor_type_name'])->selectRaw('COALESCE(batch_usage.batch_count, 0) as batch_count, COALESCE(stocking_usage.stocking_value, 0) as stocking_value, COALESCE(feeding_usage.feeding_count, 0) as feeding_count, COALESCE(feeding_usage.feeding_cost, 0) as feeding_cost')
                ->orderBy('vendor.name')->orderBy('vendor.code'),
            fn (object $row): array => $this->row([
                $this->cell($row->code, null, false, 'left', true),
                $this->cell($row->name, route('vendors.show', $row->id)),
                $this->cell($row->vendor_type_name, null, true, 'center'),
                $this->cell($row->status === 'ACTIVE' ? 'Aktif' : 'Tidak Aktif', null, true, 'center'),
                $this->cell((string) $row->batch_count, null, false, 'center'),
                $this->cell($this->money((float) $row->stocking_value), null, false, 'right'),
                $this->cell((string) $row->feeding_count, null, false, 'right'),
                $this->cell($this->money((float) $row->feeding_cost), null, false, 'right'),
            ]),
            $paginate,
            $perPage,
        );

        return $this->page('Laporan Vendor', 'Ringkasan penggunaan Vendor bibit dan pakan tanpa perhitungan utang atau pembayaran.', route('reports.vendors'), $metrics, [
            $this->summary('Total Vendor', $metrics['total'], null, 'truck'),
            $this->summary('Vendor Aktif', $metrics['active'], null, 'check'),
            $this->summary('Vendor Bibit Terpakai', $metrics['seed_used'], null, 'seedling'),
            $this->summary('Vendor Pakan Terpakai', $metrics['feed_used'], null, 'feed'),
        ], [
            $this->searchField('Cari vendor...'),
            $this->selectField('type', 'Semua Jenis', $this->options('vendor_types', 'id', 'name')),
            $this->statusField(), $this->dateField('date_from', 'Tanggal mulai'), $this->dateField('date_to', 'Tanggal selesai'),
        ], ['Kode', 'Vendor', 'Jenis', 'Status', 'Jumlah Batch', 'Nilai Pembibitan', 'Transaksi Penggunaan', 'Biaya Penggunaan Barang/Item'], $rows, 'Ringkasan Vendor', 'Filter tanggal hanya memengaruhi nilai pembibitan dan penggunaan Barang/Item; jumlah Batch merupakan data master saat ini.');
    }

    /** @param array<string, mixed> $filters */
    public function commodities(array $filters, bool $paginate = true, int $perPage = PageSize::DEFAULT): array
    {
        $query = $this->commodityQuery($filters);

        $metrics = [
            'total' => (clone $query)->count('commodity.id'),
            'current_stock' => (float) (clone $query)->sum(DB::raw('COALESCE(stock_data.current_stock, 0)')),
            'stocked' => (float) (clone $query)->sum(DB::raw('COALESCE(stocking_data.stocked_quantity, 0)')),
            'mortality' => (float) (clone $query)->sum(DB::raw('COALESCE(adjustment_data.mortality, 0)')),
        ];
        $rows = $this->rows(
            $query->select(['commodity.*'])->selectRaw('COALESCE(batch_data.batch_count, 0) as batch_count, COALESCE(stock_data.current_stock, 0) as current_stock, COALESCE(stock_data.stock_value, 0) as stock_value, COALESCE(stocking_data.stocked_quantity, 0) as stocked_quantity, COALESCE(adjustment_data.mortality, 0) as mortality, COALESCE(adjustment_data.loss, 0) as loss')
                ->orderBy('commodity.name')->orderBy('commodity.code'),
            fn (object $row): array => $this->row([
                $this->cell($row->code, null, false, 'left', true),
                $this->cell($row->name, route('commodities.show', $row->id)),
                $this->cell($row->category ?: '—', null, false, 'center'),
                $this->cell($row->unit, null, false, 'center'),
                $this->cell($row->status === 'ACTIVE' ? 'Aktif' : 'Tidak Aktif', null, true, 'center'),
                $this->cell((string) $row->batch_count, null, false, 'center'),
                $this->cell($this->quantity((float) $row->current_stock).' '.$row->unit, null, false, 'right'),
                $this->cell($this->quantity((float) $row->stocked_quantity).' '.$row->unit, null, false, 'right'),
                $this->cell($this->quantity((float) $row->mortality).' '.$row->unit, null, false, 'right'),
                $this->cell($this->quantity((float) $row->loss).' '.$row->unit, null, false, 'right'),
                $this->cell($this->money((float) $row->stock_value), null, false, 'right'),
            ]),
            $paginate,
            $perPage,
        );

        return $this->page('Laporan Komoditas', 'Ringkasan posisi stok terkini dan aktivitas historis per komoditas.', route('reports.commodities'), $metrics, [
            $this->summary('Total Komoditas', $metrics['total'], null, 'package'),
            $this->summary('Stok Saat Ini', $this->quantity($metrics['current_stock']), 'ekor', 'seedling'),
            $this->summary('Bibit Masuk', $this->quantity($metrics['stocked']), 'ekor', 'history'),
            $this->summary('Kematian', $this->quantity($metrics['mortality']), 'ekor', 'warning'),
        ], [
            $this->searchField('Cari komoditas...'),
            $this->selectField('category', 'Semua Kategori', $this->distinctOptions('commodities', 'category')),
            $this->statusField(), $this->dateField('date_from', 'Tanggal mulai'), $this->dateField('date_to', 'Tanggal selesai'),
        ], ['Kode', 'Komoditas', 'Kategori', 'Satuan', 'Status', 'Batch', 'Stok Saat Ini', 'Bibit Masuk', 'Kematian', 'Kehilangan', 'Nilai Stok Saat Ini'], $rows, 'Ringkasan per Komoditas', 'Stok dan nilai stok selalu menunjukkan posisi saat ini; filter tanggal hanya memengaruhi pembibitan, kematian, dan kehilangan.');
    }

    /** @param array<string, mixed> $filters */
    public function locations(array $filters, bool $paginate = true, int $perPage = PageSize::DEFAULT): array
    {
        $query = $this->locationQuery($filters);

        $metrics = [
            'total' => (clone $query)->count('petak.id'),
            'active' => (clone $query)->where('petak.status', 'ACTIVE')->count('petak.id'),
            'current_stock' => (float) (clone $query)->sum(DB::raw('COALESCE(stock_data.current_stock, 0)')),
            'feeding_cost' => (float) (clone $query)->sum(DB::raw('COALESCE(feeding_data.feeding_cost, 0)')),
        ];
        $rows = $this->rows(
            $query->select(['petak.id', 'petak.code', 'petak.name', 'petak.status', 'tambak.name as tambak_name', 'area.name as area_name'])
                ->selectRaw('COALESCE(stock_data.batch_count, 0) as batch_count, COALESCE(stock_data.commodity_count, 0) as commodity_count, COALESCE(stock_data.current_stock, 0) as current_stock, COALESCE(stocking_data.stocked_quantity, 0) as stocked_quantity, COALESCE(adjustment_data.mortality, 0) as mortality, COALESCE(feeding_data.feeding_cost, 0) as feeding_cost, activity_data.last_activity')
                ->orderBy('tambak.name')->orderBy('petak.name'),
            fn (object $row): array => $this->row([
                $row->area_name ?: '—', $row->tambak_name ?: 'Tanpa Tambak',
                $this->cell($row->name, route('tambak.show', $row->id), false, 'center'),
                $this->cell($row->status === 'ACTIVE' ? 'Aktif' : 'Tidak Aktif', null, true, 'center'),
                $this->cell((string) $row->batch_count, null, false, 'center'),
                $this->cell((string) $row->commodity_count, null, false, 'right'),
                $this->cell($this->quantity((float) $row->current_stock).' ekor', null, false, 'right'),
                $this->cell($this->quantity((float) $row->stocked_quantity).' ekor', null, false, 'right'),
                $this->cell($this->quantity((float) $row->mortality).' ekor', null, false, 'right'),
                $this->cell($this->money((float) $row->feeding_cost), null, false, 'right'),
                $this->cell($row->last_activity ? Carbon::parse($row->last_activity)->locale('id')->translatedFormat('d M Y, H:i') : '—', null, false, 'center'),
            ]),
            $paginate,
            $perPage,
        );

        return $this->page('Laporan Tambak & Petak', 'Ringkasan stok dan aktivitas operasional berdasarkan hierarki lokasi.', route('reports.locations'), $metrics, [
            $this->summary('Total Petak', $metrics['total'], 'petak', 'map'),
            $this->summary('Petak Aktif', $metrics['active'], 'petak', 'check'),
            $this->summary('Stok Saat Ini', $this->quantity($metrics['current_stock']), 'ekor', 'seedling'),
            $this->summary('Biaya Penggunaan Barang/Item', $this->money($metrics['feeding_cost']), null, 'coins'),
        ], [
            $this->searchField('Cari tambak atau petak...'),
            $this->selectField('area_id', 'Semua Area', $this->locationOptions('AREA')),
            $this->selectField('tambak_id', 'Semua Tambak', $this->locationOptions('TAMBAK')),
            $this->statusField(), $this->dateField('date_from', 'Tanggal mulai'), $this->dateField('date_to', 'Tanggal selesai'),
        ], ['Area', 'Tambak', 'Petak', 'Status', 'Batch Aktif', 'Komoditas', 'Stok Saat Ini', 'Pembibitan', 'Kematian', 'Biaya Penggunaan Barang/Item', 'Aktivitas Terakhir'], $rows, 'Ringkasan Operasional per Petak', 'Stok saat ini selalu menunjukkan posisi terkini; filter tanggal hanya memengaruhi pembibitan, kematian, penggunaan Barang/Item, dan aktivitas terakhir.');
    }

    /** @param array<string, mixed> $filters */
    private function stockQuery(array $filters): Builder
    {
        $query = DB::table('pond_stocks as ps')
            ->join('locations as petak', 'petak.id', '=', 'ps.location_id')
            ->leftJoin('locations as tambak', 'tambak.id', '=', 'petak.parent_id')
            ->join('commodity_batches as batch', 'batch.id', '=', 'ps.batch_id')
            ->join('commodities as commodity', 'commodity.id', '=', 'batch.commodity_id')
            ->leftJoin('vendors as vendor', 'vendor.id', '=', 'batch.vendor_id')
            ->where('ps.quantity', '>', 0);
        $this->stockFilters($query, $filters);

        return $query;
    }

    /** @param array<string, mixed> $filters */
    private function stockingQuery(array $filters): Builder
    {
        $query = DB::table('stocking_transactions as st')
            ->join('locations as petak', 'petak.id', '=', 'st.location_id')
            ->leftJoin('locations as tambak', 'tambak.id', '=', 'petak.parent_id')
            ->join('commodity_batches as batch', 'batch.id', '=', 'st.batch_id')
            ->join('commodities as commodity', 'commodity.id', '=', 'batch.commodity_id')
            ->leftJoin('vendors as vendor', 'vendor.id', '=', 'batch.vendor_id')
            ->leftJoin('users as creator', 'creator.id', '=', 'st.created_by');
        $this->stockingFilters($query, $filters);

        return $query;
    }

    /** @param array<string, mixed> $filters */
    private function movementQuery(array $filters): Builder
    {
        $query = DB::table('stock_movements as sm')
            ->join('locations as source_location', 'source_location.id', '=', 'sm.from_location_id')
            ->join('locations as destination_location', 'destination_location.id', '=', 'sm.to_location_id')
            ->join('commodity_batches as batch', 'batch.id', '=', 'sm.batch_id')
            ->join('commodities as commodity', 'commodity.id', '=', 'batch.commodity_id')
            ->leftJoin('users as creator', 'creator.id', '=', 'sm.created_by');
        $this->movementFilters($query, $filters);

        return $query;
    }

    /** @param array<string, mixed> $filters */
    private function adjustmentQuery(array $filters): Builder
    {
        $query = DB::table('stock_adjustments as sa')
            ->join('locations as petak', 'petak.id', '=', 'sa.location_id')
            ->join('commodity_batches as batch', 'batch.id', '=', 'sa.batch_id')
            ->join('commodities as commodity', 'commodity.id', '=', 'batch.commodity_id')
            ->leftJoin('users as creator', 'creator.id', '=', 'sa.created_by');
        $this->adjustmentFilters($query, $filters);

        return $query;
    }

    /** @param array<string, mixed> $filters */
    private function purchaseQuery(array $filters): Builder
    {
        $query = DB::table('item_purchase_transactions as purchase')
            ->join('feed_items as item', 'item.id', '=', 'purchase.feed_item_id')
            ->join('item_types as item_type', 'item_type.id', '=', 'item.item_type_id')
            ->join('vendors as vendor', 'vendor.id', '=', 'purchase.vendor_id')
            ->leftJoin('users as creator', 'creator.id', '=', 'purchase.created_by');
        $this->purchaseFilters($query, $filters);

        return $query;
    }

    /** @param array<string, mixed> $filters */
    private function feedingQuery(array $filters): Builder
    {
        $query = DB::table('feeding_transactions as ft')
            ->join('locations as petak', 'petak.id', '=', 'ft.location_id')
            ->leftJoin('locations as tambak', 'tambak.id', '=', 'petak.parent_id')
            ->leftJoin('commodity_batches as batch', 'batch.id', '=', 'ft.batch_id')
            ->leftJoin('commodities as commodity', 'commodity.id', '=', 'batch.commodity_id')
            ->join('feed_items as item', 'item.id', '=', 'ft.feed_item_id')
            ->join('item_types as item_type', 'item_type.id', '=', 'item.item_type_id')
            ->leftJoin('vendors as vendor', 'vendor.id', '=', 'ft.vendor_id')
            ->leftJoin('users as creator', 'creator.id', '=', 'ft.created_by');
        $this->feedingFilters($query, $filters);

        return $query;
    }

    /** @param array<string, mixed> $filters */
    private function itemQuery(array $filters): Builder
    {
        $query = DB::table('feed_items as item')
            ->join('item_types as item_type', 'item_type.id', '=', 'item.item_type_id')
            ->leftJoin('vendors as vendor', 'vendor.id', '=', 'item.default_vendor_id');
        $this->itemFilters($query, $filters);

        return $query;
    }

    /** @param array<string, mixed> $filters */
    private function vendorQuery(array $filters): Builder
    {
        $batchAgg = DB::table('commodity_batches')->select('vendor_id')->selectRaw('COUNT(id) as batch_count')->whereNotNull('vendor_id')->groupBy('vendor_id');
        $stockingAgg = DB::table('stocking_transactions as st')->join('commodity_batches as batch', 'batch.id', '=', 'st.batch_id')->select('batch.vendor_id');
        $this->dateFilters($stockingAgg, 'st', $filters);
        $stockingAgg->selectRaw('SUM(st.total_cost) as stocking_value')->whereNotNull('batch.vendor_id')->groupBy('batch.vendor_id');
        $feedingAgg = DB::table('feeding_transactions as ft')->select('ft.vendor_id');
        $this->dateFilters($feedingAgg, 'ft', $filters);
        $feedingAgg->selectRaw('COUNT(ft.id) as feeding_count, SUM(ft.total_cost) as feeding_cost')->whereNotNull('ft.vendor_id')->groupBy('ft.vendor_id');

        $query = DB::table('vendors as vendor')
            ->join('vendor_types as vendor_type', 'vendor_type.id', '=', 'vendor.vendor_type_id')
            ->leftJoinSub($batchAgg, 'batch_usage', 'batch_usage.vendor_id', '=', 'vendor.id')
            ->leftJoinSub($stockingAgg, 'stocking_usage', 'stocking_usage.vendor_id', '=', 'vendor.id')
            ->leftJoinSub($feedingAgg, 'feeding_usage', 'feeding_usage.vendor_id', '=', 'vendor.id');
        if ($filters['search'] !== '') {
            $search = '%'.$filters['search'].'%';
            $query->where(fn (Builder $q) => $q->where('vendor.code', 'like', $search)
                ->orWhere('vendor.name', 'like', $search)
                ->orWhere('vendor.address', 'like', $search)
                ->orWhere('vendor_type.name', 'like', $search));
        }
        $this->whereOptional($query, 'vendor.vendor_type_id', $filters['type']);
        $this->whereOptional($query, 'vendor.status', $filters['status']);

        return $query;
    }

    /** @param array<string, mixed> $filters */
    private function commodityQuery(array $filters): Builder
    {
        $batchAgg = DB::table('commodity_batches')->select('commodity_id')->selectRaw('COUNT(id) as batch_count')->groupBy('commodity_id');
        $stockAgg = DB::table('pond_stocks as ps')->join('commodity_batches as batch', 'batch.id', '=', 'ps.batch_id')->where('ps.quantity', '>', 0)->select('batch.commodity_id')->selectRaw('SUM(ps.quantity) as current_stock, SUM(ps.quantity * batch.unit_cost) as stock_value')->groupBy('batch.commodity_id');
        $stockingAgg = DB::table('stocking_transactions as st')->join('commodity_batches as batch', 'batch.id', '=', 'st.batch_id')->select('batch.commodity_id');
        $this->dateFilters($stockingAgg, 'st', $filters);
        $stockingAgg->selectRaw('SUM(st.quantity) as stocked_quantity')->groupBy('batch.commodity_id');
        $adjustmentAgg = DB::table('stock_adjustments as sa')->join('commodity_batches as batch', 'batch.id', '=', 'sa.batch_id')->select('batch.commodity_id');
        $this->dateFilters($adjustmentAgg, 'sa', $filters);
        $adjustmentAgg->selectRaw("SUM(CASE WHEN sa.adjustment_type = 'MORTALITY' THEN ABS(sa.quantity_change) ELSE 0 END) as mortality, SUM(CASE WHEN sa.adjustment_type = 'LOSS' THEN ABS(sa.quantity_change) ELSE 0 END) as loss")->groupBy('batch.commodity_id');

        $query = DB::table('commodities as commodity')
            ->leftJoinSub($batchAgg, 'batch_data', 'batch_data.commodity_id', '=', 'commodity.id')
            ->leftJoinSub($stockAgg, 'stock_data', 'stock_data.commodity_id', '=', 'commodity.id')
            ->leftJoinSub($stockingAgg, 'stocking_data', 'stocking_data.commodity_id', '=', 'commodity.id')
            ->leftJoinSub($adjustmentAgg, 'adjustment_data', 'adjustment_data.commodity_id', '=', 'commodity.id');
        if ($filters['search'] !== '') {
            $search = '%'.$filters['search'].'%';
            $query->where(fn (Builder $q) => $q->where('commodity.code', 'like', $search)->orWhere('commodity.name', 'like', $search)->orWhere('commodity.category', 'like', $search));
        }
        $this->whereOptional($query, 'commodity.category', $filters['category']);
        $this->whereOptional($query, 'commodity.status', $filters['status']);

        return $query;
    }

    /** @param array<string, mixed> $filters */
    private function locationQuery(array $filters): Builder
    {
        $stockAgg = DB::table('pond_stocks as ps')->join('commodity_batches as batch', 'batch.id', '=', 'ps.batch_id')->where('ps.quantity', '>', 0)->select('ps.location_id')->selectRaw('COUNT(DISTINCT ps.batch_id) as batch_count, COUNT(DISTINCT batch.commodity_id) as commodity_count, SUM(ps.quantity) as current_stock')->groupBy('ps.location_id');
        $stockingAgg = DB::table('stocking_transactions as st')->select('st.location_id');
        $this->dateFilters($stockingAgg, 'st', $filters);
        $stockingAgg->selectRaw('SUM(st.quantity) as stocked_quantity')->groupBy('st.location_id');
        $adjustmentAgg = DB::table('stock_adjustments as sa')->select('sa.location_id')->where('sa.adjustment_type', 'MORTALITY');
        $this->dateFilters($adjustmentAgg, 'sa', $filters);
        $adjustmentAgg->selectRaw('SUM(ABS(sa.quantity_change)) as mortality')->groupBy('sa.location_id');
        $feedingAgg = DB::table('feeding_transactions as ft')->select('ft.location_id');
        $this->dateFilters($feedingAgg, 'ft', $filters);
        $feedingAgg->selectRaw('SUM(ft.total_cost) as feeding_cost')->groupBy('ft.location_id');

        $query = DB::table('locations as petak')
            ->leftJoin('locations as tambak', 'tambak.id', '=', 'petak.parent_id')
            ->leftJoin('locations as area', 'area.id', '=', 'tambak.parent_id')
            ->leftJoinSub($stockAgg, 'stock_data', 'stock_data.location_id', '=', 'petak.id')
            ->leftJoinSub($stockingAgg, 'stocking_data', 'stocking_data.location_id', '=', 'petak.id')
            ->leftJoinSub($adjustmentAgg, 'adjustment_data', 'adjustment_data.location_id', '=', 'petak.id')
            ->leftJoinSub($feedingAgg, 'feeding_data', 'feeding_data.location_id', '=', 'petak.id')
            ->leftJoinSub($this->locationActivityAggregate($filters), 'activity_data', 'activity_data.location_id', '=', 'petak.id')
            ->where('petak.location_type', 'PETAK');
        if ($filters['search'] !== '') {
            $search = '%'.$filters['search'].'%';
            $query->where(fn (Builder $q) => $q->where('petak.code', 'like', $search)->orWhere('petak.name', 'like', $search)->orWhere('tambak.name', 'like', $search)->orWhere('area.name', 'like', $search));
        }
        $this->whereOptional($query, 'area.id', $filters['area_id']);
        $this->whereOptional($query, 'tambak.id', $filters['tambak_id']);
        $this->whereOptional($query, 'petak.status', $filters['status']);

        return $query;
    }

    /**
     * @param  array<int, string>  $headers
     * @param  Closure(object): array<int, mixed>  $mapper
     * @param  array<string, string>  $columnFormats
     * @param  array<string, mixed>  $filters
     */
    private function definition(
        string $title,
        string $worksheet,
        string $filenamePrefix,
        array $headers,
        Builder $query,
        Closure $mapper,
        array $columnFormats,
        array $filters,
    ): ReportExportDefinition {
        return new ReportExportDefinition(
            title: $title,
            worksheet: $worksheet,
            filename: $this->exportFilename($filenamePrefix, $filters),
            headers: $headers,
            query: $query,
            mapper: $mapper,
            columnFormats: $columnFormats,
            metadata: [
                'Tanggal ekspor: '.Carbon::now(config('app.timezone'))->locale('id')->translatedFormat('d F Y, H:i'),
                'Filter: '.$this->filterSummary($filters),
            ],
        );
    }

    /** @param array<string, mixed> $filters */
    private function exportFilename(string $prefix, array $filters): string
    {
        if ($filters['date_from'] && $filters['date_to']) {
            return "{$prefix}-{$filters['date_from']}-sd-{$filters['date_to']}";
        }

        if ($filters['date_from']) {
            return "{$prefix}-mulai-{$filters['date_from']}";
        }

        if ($filters['date_to']) {
            return "{$prefix}-sampai-{$filters['date_to']}";
        }

        return $prefix.'-'.Carbon::now(config('app.timezone'))->format('Y-m-d');
    }

    /** @param array<string, mixed> $filters */
    private function filterSummary(array $filters): string
    {
        $parts = [];

        if ($filters['search'] !== '') {
            $parts[] = 'pencarian "'.$filters['search'].'"';
        }
        if ($filters['date_from']) {
            $parts[] = 'mulai '.$filters['date_from'];
        }
        if ($filters['date_to']) {
            $parts[] = 'sampai '.$filters['date_to'];
        }

        foreach ([
            'area_id' => ['locations', 'area'],
            'tambak_id' => ['locations', 'tambak'],
            'location_id' => ['locations', 'petak'],
            'commodity_id' => ['commodities', 'komoditas'],
            'batch_id' => ['commodity_batches', 'batch'],
            'vendor_id' => ['vendors', 'vendor'],
            'feed_item_id' => ['feed_items', 'item'],
            'user_id' => ['users', 'user'],
        ] as $key => [$table, $label]) {
            if (! $filters[$key]) {
                continue;
            }

            $nameColumn = $key === 'batch_id' ? 'batch_code' : 'name';
            $value = DB::table($table)->where('id', $filters[$key])->value($nameColumn);
            $parts[] = $label.' '.($value ?: '#'.$filters[$key]);
        }

        if ($filters['type']) {
            $lookupTypeName = is_numeric($filters['type'])
                ? DB::table(request()->routeIs('reports.vendors*') ? 'vendor_types' : 'item_types')->where('id', $filters['type'])->value('name')
                : null;
            $parts[] = 'jenis '.($lookupTypeName ?? UserFacing::ADJUSTMENT_TYPES[$filters['type']] ?? 'Lainnya');
        }
        if ($filters['status']) {
            $parts[] = 'status '.($filters['status'] === 'ACTIVE' ? 'Aktif' : 'Tidak Aktif');
        }
        if ($filters['category']) {
            $parts[] = 'kategori '.$filters['category'];
        }

        return $parts === [] ? 'Semua data' : implode(' · ', $parts);
    }

    /** @param array<string, mixed> $filters */
    private function stockFilters(Builder $query, array $filters): void
    {
        if ($filters['search'] !== '') {
            $search = '%'.$filters['search'].'%';
            $query->where(fn (Builder $q) => $q->where('batch.batch_code', 'like', $search)->orWhere('commodity.name', 'like', $search)->orWhere('petak.name', 'like', $search)->orWhere('tambak.name', 'like', $search)->orWhere('vendor.name', 'like', $search));
        }
        $this->whereOptional($query, 'petak.parent_id', $filters['tambak_id']);
        $this->whereOptional($query, 'ps.location_id', $filters['location_id']);
        $this->whereOptional($query, 'batch.commodity_id', $filters['commodity_id']);
        $this->whereOptional($query, 'ps.batch_id', $filters['batch_id']);
    }

    /** @param array<string, mixed> $filters */
    private function stockingFilters(Builder $query, array $filters): void
    {
        $this->dateFilters($query, 'st', $filters);
        $this->whereOptional($query, 'petak.parent_id', $filters['tambak_id']);
        $this->whereOptional($query, 'st.location_id', $filters['location_id']);
        $this->whereOptional($query, 'batch.commodity_id', $filters['commodity_id']);
        $this->whereOptional($query, 'batch.vendor_id', $filters['vendor_id']);
        $this->whereOptional($query, 'st.created_by', $filters['user_id']);
        if ($filters['search'] !== '') {
            $search = '%'.$filters['search'].'%';
            $query->where(fn (Builder $q) => $q->where('st.transaction_number', 'like', $search)->orWhere('batch.batch_code', 'like', $search)->orWhere('commodity.name', 'like', $search)->orWhere('petak.name', 'like', $search)->orWhere('vendor.name', 'like', $search)->orWhere('creator.name', 'like', $search));
        }
    }

    /** @param array<string, mixed> $filters */
    private function movementFilters(Builder $query, array $filters): void
    {
        $this->dateFilters($query, 'sm', $filters);
        $this->whereOptional($query, 'batch.commodity_id', $filters['commodity_id']);
        $this->whereOptional($query, 'sm.batch_id', $filters['batch_id']);
        $this->whereOptional($query, 'sm.created_by', $filters['user_id']);
        if ($filters['location_id']) {
            $query->where(fn (Builder $q) => $q->where('sm.from_location_id', $filters['location_id'])->orWhere('sm.to_location_id', $filters['location_id']));
        }
        if ($filters['search'] !== '') {
            $search = '%'.$filters['search'].'%';
            $query->where(fn (Builder $q) => $q->where('sm.transaction_number', 'like', $search)->orWhere('batch.batch_code', 'like', $search)->orWhere('commodity.name', 'like', $search)->orWhere('source_location.name', 'like', $search)->orWhere('destination_location.name', 'like', $search)->orWhere('creator.name', 'like', $search));
        }
    }

    /** @param array<string, mixed> $filters */
    private function adjustmentFilters(Builder $query, array $filters): void
    {
        $this->dateFilters($query, 'sa', $filters);
        $this->whereOptional($query, 'sa.location_id', $filters['location_id']);
        $this->whereOptional($query, 'batch.commodity_id', $filters['commodity_id']);
        $this->whereOptional($query, 'sa.batch_id', $filters['batch_id']);
        $this->whereOptional($query, 'sa.created_by', $filters['user_id']);
        $this->whereOptional($query, 'sa.adjustment_type', $filters['type']);
        if ($filters['search'] !== '') {
            $search = '%'.$filters['search'].'%';
            $query->where(fn (Builder $q) => $q->where('sa.transaction_number', 'like', $search)->orWhere('batch.batch_code', 'like', $search)->orWhere('commodity.name', 'like', $search)->orWhere('petak.name', 'like', $search)->orWhere('sa.reason', 'like', $search)->orWhere('creator.name', 'like', $search));
        }
    }

    /** @param array<string, mixed> $filters */
    private function purchaseFilters(Builder $query, array $filters): void
    {
        $this->dateFilters($query, 'purchase', $filters);
        $this->whereOptional($query, 'purchase.feed_item_id', $filters['feed_item_id']);
        $this->whereOptional($query, 'item.item_type_id', $filters['type']);
        $this->whereOptional($query, 'purchase.vendor_id', $filters['vendor_id']);
        $this->whereOptional($query, 'purchase.created_by', $filters['user_id']);
        if ($filters['search'] !== '') {
            $search = '%'.$filters['search'].'%';
            $query->where(fn (Builder $q) => $q->where('purchase.transaction_number', 'like', $search)
                ->orWhere('item.code', 'like', $search)
                ->orWhere('item.name', 'like', $search)
                ->orWhere('item_type.name', 'like', $search)
                ->orWhere('vendor.name', 'like', $search)
                ->orWhere('creator.name', 'like', $search));
        }
    }

    /** @param array<string, mixed> $filters */
    private function feedingFilters(Builder $query, array $filters): void
    {
        $this->dateFilters($query, 'ft', $filters);
        $this->whereOptional($query, 'ft.location_id', $filters['location_id']);
        $this->whereOptional($query, 'batch.commodity_id', $filters['commodity_id']);
        $this->whereOptional($query, 'ft.vendor_id', $filters['vendor_id']);
        $this->whereOptional($query, 'ft.feed_item_id', $filters['feed_item_id']);
        $this->whereOptional($query, 'ft.created_by', $filters['user_id']);
        $this->whereOptional($query, 'item.item_type_id', $filters['type']);
        if ($filters['search'] !== '') {
            $search = '%'.$filters['search'].'%';
            $query->where(fn (Builder $q) => $q->where('ft.transaction_number', 'like', $search)->orWhere('item.name', 'like', $search)->orWhere('petak.name', 'like', $search)->orWhere('batch.batch_code', 'like', $search)->orWhere('commodity.name', 'like', $search)->orWhere('vendor.name', 'like', $search)->orWhere('creator.name', 'like', $search));
        }
    }

    /** @param array<string, mixed> $filters */
    private function itemFilters(Builder $query, array $filters): void
    {
        $this->whereOptional($query, 'item.item_type_id', $filters['type']);
        $this->whereOptional($query, 'item.default_vendor_id', $filters['vendor_id']);
        $this->whereOptional($query, 'item.status', $filters['status']);
        if ($filters['search'] !== '') {
            $search = '%'.$filters['search'].'%';
            $query->where(fn (Builder $q) => $q->where('item.code', 'like', $search)
                ->orWhere('item.name', 'like', $search)
                ->orWhere('item_type.name', 'like', $search)
                ->orWhere('vendor.name', 'like', $search));
        }
    }

    /** @param array<string, mixed> $filters */
    private function dateFilters(Builder $query, string $alias, array $filters): void
    {
        if ($filters['date_from']) {
            $query->whereDate("{$alias}.transaction_date", '>=', $filters['date_from']);
        }
        if ($filters['date_to']) {
            $query->whereDate("{$alias}.transaction_date", '<=', $filters['date_to']);
        }
    }

    /** @param array<string, mixed> $filters */
    private function locationActivityAggregate(array $filters): Builder
    {
        $stocking = DB::table('stocking_transactions as st')->select('st.location_id', 'st.transaction_date');
        $movementOut = DB::table('stock_movements as sm')->selectRaw('sm.from_location_id as location_id, sm.transaction_date');
        $movementIn = DB::table('stock_movements as sm')->selectRaw('sm.to_location_id as location_id, sm.transaction_date');
        $adjustment = DB::table('stock_adjustments as sa')->select('sa.location_id', 'sa.transaction_date');
        $feeding = DB::table('feeding_transactions as ft')->select('ft.location_id', 'ft.transaction_date');
        foreach ([[$stocking, 'st'], [$movementOut, 'sm'], [$movementIn, 'sm'], [$adjustment, 'sa'], [$feeding, 'ft']] as [$query, $alias]) {
            $this->dateFilters($query, $alias, $filters);
        }
        $union = $stocking->unionAll($movementOut)->unionAll($movementIn)->unionAll($adjustment)->unionAll($feeding);

        return DB::query()->fromSub($union, 'activity')->select('location_id')->selectRaw('MAX(transaction_date) as last_activity')->groupBy('location_id');
    }

    private function whereOptional(Builder $query, string $column, mixed $value): void
    {
        if ($value !== null && $value !== '') {
            $query->where($column, $value);
        }
    }

    /** @return LengthAwarePaginator<int, array<string, mixed>>|Collection<int, array<string, mixed>> */
    private function rows(Builder $query, Closure $transform, bool $paginate, int $perPage): LengthAwarePaginator|Collection
    {
        if (! $paginate) {
            return $query->get()->map($transform);
        }

        $paginator = $query->paginate($perPage)->withQueryString();
        $paginator->setCollection($paginator->getCollection()->map($transform));

        return $paginator;
    }

    /** @return array<string, mixed> */
    private function page(string $title, string $description, string $route, array $metrics, array $summaryCards, array $filterFields, array $columns, LengthAwarePaginator|Collection $rows, string $tableTitle, ?string $notice = null): array
    {
        return compact('title', 'description', 'route', 'metrics', 'summaryCards', 'filterFields', 'columns', 'rows', 'tableTitle', 'notice');
    }

    /**
     * @param  array<string, mixed>  $filters
     * @param  array<int, array<string, mixed>>  $filterFields
     * @return array<int, array{label: string, value: string}>
     */
    private function humanReadableFilters(array $filters, array $filterFields): array
    {
        $fields = collect($filterFields)->keyBy('name');
        $summary = [];

        if ($filters['date_from'] || $filters['date_to']) {
            $from = $filters['date_from']
                ? Carbon::parse($filters['date_from'])->locale('id')->translatedFormat('d F Y')
                : null;
            $to = $filters['date_to']
                ? Carbon::parse($filters['date_to'])->locale('id')->translatedFormat('d F Y')
                : null;
            $summary[] = [
                'label' => 'Tanggal',
                'value' => $from && $to ? "{$from} - {$to}" : ($from ? "Mulai {$from}" : "Sampai {$to}"),
            ];
        }

        foreach ([
            'search' => 'Pencarian',
            'area_id' => 'Area',
            'tambak_id' => 'Tambak',
            'location_id' => 'Petak',
            'commodity_id' => 'Komoditas',
            'batch_id' => 'Batch',
            'vendor_id' => 'Vendor',
            'feed_item_id' => 'Barang/Item',
            'user_id' => 'Dicatat Oleh',
            'type' => 'Jenis',
            'status' => 'Status',
            'category' => 'Kategori',
        ] as $name => $label) {
            $value = $filters[$name] ?? null;
            $field = $fields->get($name);

            if (($value === null || $value === '') || $field === null) {
                continue;
            }

            if (($field['type'] ?? null) === 'select') {
                $option = collect($field['options'] ?? [])->first(
                    fn (array $option): bool => (string) $option['value'] === (string) $value
                );
                $value = $option['label'] ?? (string) $value;
            }

            $summary[] = ['label' => $label, 'value' => (string) $value];
        }

        return $summary;
    }

    /** @return array<string, mixed> */
    private function summary(string $label, string|int $value, ?string $suffix, string $icon): array
    {
        return compact('label', 'value', 'suffix', 'icon');
    }

    /** @return array<string, mixed> */
    private function reportCard(string $title, string $description, string $url, string $metric, string $icon): array
    {
        return compact('title', 'description', 'url', 'metric', 'icon');
    }

    /** @return array{cells: array<int, mixed>} */
    private function row(array $cells): array
    {
        return compact('cells');
    }

    /** @return array<string, mixed> */
    private function cell(string $text, ?string $url = null, bool $badge = false, string $align = 'left', bool $mono = false): array
    {
        return compact('text', 'url', 'badge', 'align', 'mono');
    }

    private function quantity(float $value): string
    {
        $decimals = floor(abs($value)) === abs($value) ? 0 : 3;

        return number_format($value, $decimals, ',', '.');
    }

    private function money(float $value): string
    {
        return 'Rp'.number_format($value, 0, ',', '.');
    }

    private function decimalMoney(string|int|float|null $value): string
    {
        return 'Rp'.DecimalDisplay::localized($value !== null ? (string) $value : null, '0');
    }

    /** @return array<string, mixed> */
    private function searchField(string $placeholder): array
    {
        return ['name' => 'search', 'type' => 'search', 'label' => 'Pencarian', 'placeholder' => $placeholder];
    }

    /** @param array<int, array{value: mixed, label: string}> $options */
    private function selectField(string $name, string $label, array $options): array
    {
        return ['name' => $name, 'type' => 'select', 'label' => $label, 'options' => $options];
    }

    /** @return array<string, string> */
    private function dateField(string $name, string $label): array
    {
        return ['name' => $name, 'type' => 'date', 'label' => $label];
    }

    /** @return array<string, mixed> */
    private function statusField(): array
    {
        return $this->selectField('status', 'Semua Status', [['value' => 'ACTIVE', 'label' => 'Aktif'], ['value' => 'INACTIVE', 'label' => 'Tidak Aktif']]);
    }

    /** @return array<int, array<string, mixed>> */
    private function stockFilterFields(): array
    {
        return [
            $this->searchField('Cari Batch, komoditas, atau lokasi...'),
            $this->selectField('tambak_id', 'Semua Tambak', $this->locationOptions('TAMBAK')),
            $this->selectField('location_id', 'Semua Petak', $this->locationOptions('PETAK')),
            $this->selectField('commodity_id', 'Semua Komoditas', $this->options('commodities', 'id', 'name')),
            $this->selectField('batch_id', 'Semua Batch', DB::table('commodity_batches as batch')
                ->join('pond_stocks as ps', 'ps.batch_id', '=', 'batch.id')
                ->where('ps.quantity', '>', 0)
                ->distinct()->orderBy('batch.batch_code')->get(['batch.id', 'batch.batch_code'])
                ->map(fn (object $row): array => ['value' => $row->id, 'label' => $row->batch_code])->all()),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function itemFilterFields(): array
    {
        return [
            $this->searchField('Cari kode, Barang/Item, jenis, atau Vendor default...'),
            $this->selectField('type', 'Semua Jenis Barang/Item', $this->options('item_types', 'id', 'name')),
            $this->selectField('vendor_id', 'Semua Vendor Default', $this->options('vendors', 'id', 'name')),
            $this->statusField(),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function itemTransactionFilterFields(): array
    {
        return [
            $this->searchField('Cari transaksi, Barang/Item, jenis, Vendor, atau pencatat...'),
            $this->selectField('feed_item_id', 'Semua Barang/Item', $this->options('feed_items', 'id', 'name')),
            $this->selectField('type', 'Semua Jenis Barang/Item', $this->options('item_types', 'id', 'name')),
            $this->selectField('vendor_id', 'Semua Vendor', $this->options('vendors', 'id', 'name')),
            $this->selectField('user_id', 'Semua Pencatat', $this->options('users', 'id', 'name')),
            $this->dateField('date_from', 'Tanggal mulai'),
            $this->dateField('date_to', 'Tanggal selesai'),
        ];
    }

    /** @param array<int, string> $includes */
    private function transactionFilterFields(array $includes): array
    {
        $fields = [$this->searchField('Cari transaksi...')];
        if (in_array('tambak', $includes, true)) {
            $fields[] = $this->selectField('tambak_id', 'Semua Tambak', $this->locationOptions('TAMBAK'));
        }
        if (in_array('location', $includes, true)) {
            $fields[] = $this->selectField('location_id', 'Semua Petak', $this->locationOptions('PETAK'));
        }
        if (in_array('commodity', $includes, true)) {
            $fields[] = $this->selectField('commodity_id', 'Semua Komoditas', $this->options('commodities', 'id', 'name'));
        }
        if (in_array('batch', $includes, true)) {
            $fields[] = $this->selectField('batch_id', 'Semua Batch', $this->options('commodity_batches', 'id', 'batch_code'));
        }
        if (in_array('vendor', $includes, true)) {
            $fields[] = $this->selectField('vendor_id', 'Semua Vendor', $this->options('vendors', 'id', 'name'));
        }
        if (in_array('user', $includes, true)) {
            $fields[] = $this->selectField('user_id', 'Semua Pencatat', $this->options('users', 'id', 'name'));
        }
        $fields[] = $this->dateField('date_from', 'Tanggal mulai');
        $fields[] = $this->dateField('date_to', 'Tanggal selesai');

        return $fields;
    }

    /** @return array<int, array{value: mixed, label: string}> */
    private function options(string $table, string $valueColumn, string $labelColumn): array
    {
        return DB::table($table)->whereNotNull($labelColumn)->orderBy($labelColumn)->get([$valueColumn, $labelColumn])
            ->map(fn (object $row): array => ['value' => $row->{$valueColumn}, 'label' => $row->{$labelColumn}])->all();
    }

    /** @return array<int, array{value: mixed, label: string}> */
    private function locationOptions(string $type): array
    {
        return DB::table('locations')->where('location_type', $type)->orderBy('name')->get(['id', 'name'])
            ->map(fn (object $row): array => ['value' => $row->id, 'label' => $row->name])->all();
    }

    /** @return array<int, array{value: mixed, label: string}> */
    private function distinctOptions(string $table, string $column): array
    {
        return DB::table($table)->whereNotNull($column)->where($column, '!=', '')->distinct()->orderBy($column)->pluck($column)
            ->map(fn (string $value): array => ['value' => $value, 'label' => $value])->all();
    }

    /** @param array<string, string> $labels */
    private function mapOptions(array $labels): array
    {
        return collect($labels)->map(fn (string $label, string $value): array => compact('value', 'label'))->values()->all();
    }
}
