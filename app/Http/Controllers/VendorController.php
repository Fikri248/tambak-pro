<?php

namespace App\Http\Controllers;

use App\Http\Requests\VendorRequest;
use App\Models\Vendor;
use App\Services\BusinessCodeGenerator;
use App\Support\PageSize;
use App\Support\UserFacing;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VendorController extends Controller
{
    public function index(Request $request): View
    {
        $search = mb_substr(trim((string) $request->query('search')), 0, 255);
        $type = in_array($request->query('type'), array_keys(UserFacing::VENDOR_TYPES), true)
            ? $request->query('type')
            : null;
        $status = in_array($request->query('status'), ['ACTIVE', 'INACTIVE'], true)
            ? $request->query('status')
            : null;

        $vendors = Vendor::query()
            ->withCount(['commodityBatches', 'defaultFeedItems'])
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%");
                });
            })
            ->when($type, fn (Builder $query, string $type) => $query->where('vendor_type', $type))
            ->when($status, fn (Builder $query, string $status) => $query->where('status', $status))
            ->orderBy('name')
            ->paginate(PageSize::resolve($request))
            ->withQueryString();

        return view('vendors.index', [
            'vendors' => $vendors,
            'typeLabels' => UserFacing::VENDOR_TYPES,
            'filters' => compact('search', 'type', 'status'),
            'summary' => [
                'total' => Vendor::query()->count(),
                'active' => Vendor::query()->where('status', 'ACTIVE')->count(),
                'seed' => Vendor::query()->where('vendor_type', 'SEED')->count(),
                'used' => Vendor::query()
                    ->where(function (Builder $query): void {
                        $query->whereHas('commodityBatches', fn (Builder $query) => $query->where('status', 'ACTIVE'))
                            ->orWhereHas('defaultFeedItems', fn (Builder $query) => $query->where('status', 'ACTIVE'));
                    })
                    ->count(),
            ],
        ]);
    }

    public function create(): View
    {
        return view('vendors.create', ['typeLabels' => UserFacing::VENDOR_TYPES]);
    }

    public function store(VendorRequest $request, BusinessCodeGenerator $codes): RedirectResponse
    {
        $vendor = $codes->create(Vendor::class, 'code', 'VND', [
            ...$request->safe()->except(['code', 'status']),
            'status' => 'ACTIVE',
        ]);

        return redirect()
            ->route('vendors.show', $vendor)
            ->with('success', 'Vendor berhasil ditambahkan.');
    }

    public function show(Vendor $vendor): View
    {
        $vendor->loadCount(['commodityBatches', 'defaultFeedItems', 'feedingTransactions']);

        $relatedBatches = $vendor->commodityBatches()
            ->with('commodity:id,code,name,unit')
            ->latest('purchase_date')
            ->latest('id')
            ->limit(12)
            ->get();
        $relatedFeedItems = $vendor->defaultFeedItems()
            ->orderBy('name')
            ->limit(12)
            ->get();
        $recentTransactions = $vendor->feedingTransactions()
            ->with([
                'location:id,code,name',
                'feedItem:id,code,name,unit',
                'createdBy:id,name',
            ])
            ->latest('transaction_date')
            ->latest('id')
            ->limit(6)
            ->get();

        return view('vendors.show', [
            'vendor' => $vendor,
            'relatedBatches' => $relatedBatches,
            'relatedFeedItems' => $relatedFeedItems,
            'recentTransactions' => $recentTransactions,
            'typeLabels' => UserFacing::VENDOR_TYPES,
            'itemTypeLabels' => UserFacing::FEED_ITEM_TYPES,
        ]);
    }

    public function edit(Vendor $vendor): View
    {
        return view('vendors.edit', [
            'vendor' => $vendor,
            'typeLabels' => UserFacing::VENDOR_TYPES,
        ]);
    }

    public function update(VendorRequest $request, Vendor $vendor): RedirectResponse
    {
        $vendor->update($request->safe()->except(['code', 'status']));

        return redirect()
            ->route('vendors.show', $vendor)
            ->with('success', 'Vendor berhasil diperbarui.');
    }

    public function status(Vendor $vendor): RedirectResponse
    {
        $status = DB::transaction(function () use ($vendor): string {
            $lockedVendor = Vendor::query()->lockForUpdate()->findOrFail($vendor->id);

            if ($lockedVendor->status === 'ACTIVE') {
                $hasActiveDependency = $lockedVendor->commodityBatches()->where('status', 'ACTIVE')->exists()
                    || $lockedVendor->defaultFeedItems()->where('status', 'ACTIVE')->exists();

                if ($hasActiveDependency) {
                    return 'HAS_ACTIVE_DEPENDENCY';
                }
            }

            $lockedVendor->update([
                'status' => $lockedVendor->status === 'ACTIVE' ? 'INACTIVE' : 'ACTIVE',
            ]);

            return $lockedVendor->status;
        });

        if ($status === 'HAS_ACTIVE_DEPENDENCY') {
            return back()->with('error', 'Vendor tidak dapat dinonaktifkan karena masih digunakan oleh data aktif.');
        }

        $message = $status === 'ACTIVE'
            ? 'Vendor berhasil diaktifkan.'
            : 'Vendor berhasil dinonaktifkan.';

        return back()->with('success', $message);
    }
}
