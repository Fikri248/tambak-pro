<?php

namespace App\Http\Controllers;

use App\Http\Requests\FeedItemRequest;
use App\Models\FeedItem;
use App\Models\Vendor;
use App\Services\BusinessCodeGenerator;
use App\Support\PageSize;
use App\Support\UserFacing;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FeedItemController extends Controller
{
    public function index(Request $request): View
    {
        $search = mb_substr(trim((string) $request->query('search')), 0, 255);
        $type = in_array($request->query('type'), array_keys(UserFacing::FEED_ITEM_TYPES), true)
            ? $request->query('type')
            : null;
        $status = in_array($request->query('status'), ['ACTIVE', 'INACTIVE'], true)
            ? $request->query('status')
            : null;

        $feedItems = FeedItem::query()
            ->with('defaultVendor:id,code,name,vendor_type,status')
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('unit', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhereHas('defaultVendor', fn (Builder $query) => $query->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($type, fn (Builder $query, string $type) => $query->where('item_type', $type))
            ->when($status, fn (Builder $query, string $status) => $query->where('status', $status))
            ->orderBy('name')
            ->paginate(PageSize::resolve($request))
            ->withQueryString();

        return view('feed-items.index', [
            'feedItems' => $feedItems,
            'typeLabels' => UserFacing::FEED_ITEM_TYPES,
            'filters' => compact('search', 'type', 'status'),
            'summary' => [
                'total' => FeedItem::query()->count(),
                'active' => FeedItem::query()->where('status', 'ACTIVE')->count(),
                'feed' => FeedItem::query()->where('item_type', 'FEED')->count(),
                'nutritionMedicine' => FeedItem::query()->whereIn('item_type', ['NUTRITION', 'MEDICINE'])->count(),
            ],
        ]);
    }

    public function create(): View
    {
        return view('feed-items.create', [
            'typeLabels' => UserFacing::FEED_ITEM_TYPES,
            'vendorTypeLabels' => UserFacing::VENDOR_TYPES,
            'vendors' => $this->availableVendors(),
        ]);
    }

    public function store(FeedItemRequest $request, BusinessCodeGenerator $codes): RedirectResponse
    {
        $validated = $request->safe()->except(['code', 'status']);
        $prefix = match ($validated['item_type']) {
            'FEED' => 'PKN',
            'NUTRITION' => 'NTR',
            'MEDICINE' => 'OBT',
            default => 'LNN',
        };
        $feedItem = $codes->create(FeedItem::class, 'code', $prefix, [
            ...$validated,
            'status' => 'ACTIVE',
        ]);

        return redirect()
            ->route('feed-items.show', $feedItem)
            ->with('success', 'Pakan, nutrisi, atau obat berhasil ditambahkan.');
    }

    public function show(FeedItem $feedItem): View
    {
        $feedItem->load('defaultVendor:id,code,name,vendor_type,status');
        $usageCount = $feedItem->feedingTransactions()->count();
        $totalUsage = (float) $feedItem->feedingTransactions()->sum('feed_quantity');
        $totalCost = (float) $feedItem->feedingTransactions()->sum('total_cost');
        $recentTransactions = $feedItem->feedingTransactions()
            ->with([
                'location:id,code,name',
                'batch:id,batch_code,commodity_id',
                'batch.commodity:id,name',
                'createdBy:id,name',
            ])
            ->latest('transaction_date')
            ->latest('id')
            ->limit(8)
            ->get();

        return view('feed-items.show', [
            'feedItem' => $feedItem,
            'recentTransactions' => $recentTransactions,
            'usageCount' => $usageCount,
            'totalUsage' => $totalUsage,
            'totalCost' => $totalCost,
            'typeLabels' => UserFacing::FEED_ITEM_TYPES,
        ]);
    }

    public function edit(FeedItem $feedItem): View
    {
        $feedItem->load('defaultVendor:id,code,name,vendor_type,status');

        return view('feed-items.edit', [
            'feedItem' => $feedItem,
            'typeLabels' => UserFacing::FEED_ITEM_TYPES,
            'vendorTypeLabels' => UserFacing::VENDOR_TYPES,
            'vendors' => $this->availableVendors($feedItem),
        ]);
    }

    public function update(FeedItemRequest $request, FeedItem $feedItem): RedirectResponse
    {
        $feedItem->update($request->safe()->except(['code', 'status']));

        return redirect()
            ->route('feed-items.show', $feedItem)
            ->with('success', 'Pakan, nutrisi, atau obat berhasil diperbarui.');
    }

    public function status(FeedItem $feedItem): RedirectResponse
    {
        $status = DB::transaction(function () use ($feedItem): string {
            $lockedFeedItem = FeedItem::query()->lockForUpdate()->findOrFail($feedItem->id);
            $lockedFeedItem->update([
                'status' => $lockedFeedItem->status === 'ACTIVE' ? 'INACTIVE' : 'ACTIVE',
            ]);

            return $lockedFeedItem->status;
        });

        $message = $status === 'ACTIVE'
            ? 'Kebutuhan berhasil diaktifkan.'
            : 'Kebutuhan berhasil dinonaktifkan.';

        return back()->with('success', $message);
    }

    /**
     * @return Collection<int, Vendor>
     */
    private function availableVendors(?FeedItem $feedItem = null): Collection
    {
        return Vendor::query()
            ->where(function (Builder $query) use ($feedItem): void {
                $query->where(function (Builder $query): void {
                    $query->where('status', 'ACTIVE')
                        ->whereIn('vendor_type', ['FEED', 'MULTIPLE']);
                });

                if ($feedItem?->default_vendor_id) {
                    $query->orWhere('id', $feedItem->default_vendor_id);
                }
            })
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'vendor_type', 'status']);
    }
}
