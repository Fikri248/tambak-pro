<?php

namespace App\Http\Controllers;

use App\Http\Requests\DashboardAnalyticsRequest;
use App\Models\AuditLog;
use App\Models\Commodity;
use App\Models\Location;
use App\Models\PondStock;
use App\Models\Vendor;
use App\Services\DashboardAnalyticsService;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardAnalyticsService $analytics) {}

    public function index(DashboardAnalyticsRequest $request): View
    {
        $filters = $request->validated();
        $recentActivities = AuditLog::query()
            ->latest('created_at')
            ->latest('id')
            ->limit(6)
            ->get();

        return view('dashboard', $this->analytics->get(
            $filters['period'],
            isset($filters['tambak_id']) ? (int) $filters['tambak_id'] : null,
        ) + [
            'filters' => $filters,
            'totalTambak' => Location::query()
                ->where('location_type', 'TAMBAK')
                ->where('status', 'ACTIVE')
                ->count(),
            'totalCommodities' => Commodity::query()->where('status', 'ACTIVE')->count(),
            'totalStock' => PondStock::query()->sum('quantity'),
            'totalVendors' => Vendor::query()->where('status', 'ACTIVE')->count(),
            'recentActivities' => $recentActivities,
            'activityLabels' => [
                'STOCKING_TRANSACTION' => 'Pembibitan',
                'STOCK_ADJUSTMENT' => 'Penyesuaian stok',
                'STOCK_MOVEMENT' => 'Pemindahan Stok',
                'FEEDING_TRANSACTION' => 'Pemberian pakan',
            ],
        ]);
    }
}
