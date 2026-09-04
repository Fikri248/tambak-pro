<?php

namespace App\Http\Controllers;

use App\Http\Requests\VendorRequest;
use App\Models\Vendor;
use App\Models\VendorType;
use App\Services\BusinessCodeGenerator;
use App\Support\PageSize;
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
        $typeInput = trim((string) $request->query('type'));
        $type = filter_var($typeInput, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE);
        $type = $type && VendorType::query()->whereKey($type)->exists()
            ? $type
            : VendorType::query()->where('code', mb_strtoupper($typeInput))->value('id');
        $status = in_array($request->query('status'), ['ACTIVE', 'INACTIVE'], true)
            ? $request->query('status')
            : null;

        $vendors = Vendor::query()
            ->with('vendorType:id,name,semantic_type')
            ->withCount(['commodityBatches', 'defaultFeedItems'])
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%")
                        ->orWhereHas('vendorType', fn (Builder $query) => $query->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($type, fn (Builder $query, int $type) => $query->where('vendor_type_id', $type))
            ->when($status, fn (Builder $query, string $status) => $query->where('status', $status))
            ->orderBy('name')
            ->paginate(PageSize::resolve($request))
            ->withQueryString();

        return view('vendors.index', [
            'vendors' => $vendors,
            'typeLabels' => VendorType::query()->orderBy('name')->pluck('name', 'id')->all(),
            'filters' => compact('search', 'type', 'status'),
            'summary' => [
                'total' => Vendor::query()->count(),
                'active' => Vendor::query()->where('status', 'ACTIVE')->count(),
                'seed' => Vendor::query()->whereHas(
                    'vendorType',
                    fn (Builder $query) => $query->where('semantic_type', VendorType::SEMANTIC_SEED),
                )->count(),
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
        return view('vendors.create', $this->formData());
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
        $vendor->load('vendorType:id,name,semantic_type')
            ->loadCount(['commodityBatches', 'defaultFeedItems', 'feedingTransactions']);

        $relatedBatches = $vendor->commodityBatches()
            ->with('commodity:id,code,name,unit')
            ->latest('purchase_date')
            ->latest('id')
            ->limit(12)
            ->get();
        $relatedFeedItems = $vendor->defaultFeedItems()
            ->with('itemType:id,name')
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
        ]);
    }

    public function edit(Vendor $vendor): View
    {
        return view('vendors.edit', $this->formData($vendor));
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

    /** @return array<string, mixed> */
    private function formData(?Vendor $vendor = null): array
    {
        $vendorTypes = VendorType::query()->orderBy('name')->get();

        return [
            'vendor' => $vendor,
            'vendorTypes' => $vendorTypes,
        ];
    }
}
