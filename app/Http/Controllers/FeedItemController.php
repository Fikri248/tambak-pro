<?php

namespace App\Http\Controllers;

use App\Http\Requests\FeedItemRequest;
use App\Models\FeedItem;
use App\Models\ItemType;
use App\Models\Vendor;
use App\Services\BusinessCodeGenerator;
use App\Support\PageSize;
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
        $typeInput = trim((string) $request->query('type'));
        $type = $typeInput !== ''
            ? ItemType::query()->whereKey($typeInput)->orWhere('code', mb_strtoupper($typeInput))->value('id')
            : null;
        $status = in_array($request->query('status'), ['ACTIVE', 'INACTIVE'], true)
            ? $request->query('status')
            : null;

        $feedItems = FeedItem::query()
            ->with(['itemType:id,name,semantic_type', 'defaultVendor:id,code,name,vendor_type_id,status', 'defaultVendor.vendorType:id,name,semantic_type'])
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('unit', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhereHas('defaultVendor', fn (Builder $query) => $query->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($type, fn (Builder $query, int $type) => $query->where('item_type_id', $type))
            ->when($status, fn (Builder $query, string $status) => $query->where('status', $status))
            ->orderBy('name')
            ->paginate(PageSize::resolve($request))
            ->withQueryString();

        return view('feed-items.index', [
            'feedItems' => $feedItems,
            'typeLabels' => $this->itemTypes()->pluck('name', 'id')->all(),
            'filters' => compact('search', 'type', 'status'),
            'summary' => [
                'total' => FeedItem::query()->count(),
                'active' => FeedItem::query()->where('status', 'ACTIVE')->count(),
                'feed' => FeedItem::query()->whereHas('itemType', fn (Builder $query) => $query->where('semantic_type', ItemType::SEMANTIC_FEED))->count(),
                'nutritionMedicine' => FeedItem::query()->whereHas('itemType', fn (Builder $query) => $query->whereIn('semantic_type', [ItemType::SEMANTIC_NUTRITION, ItemType::SEMANTIC_MEDICINE]))->count(),
            ],
            'itemTypes' => $this->itemTypes(),
        ]);
    }

    public function create(): View
    {
        return view('feed-items.create', [
            'itemTypes' => $this->itemTypes(),
            'vendors' => $this->availableVendors(),
        ]);
    }

    public function store(FeedItemRequest $request, BusinessCodeGenerator $codes): RedirectResponse
    {
        $validated = $request->safe()->except(['code', 'status']);
        $itemType = ItemType::query()->findOrFail($validated['item_type_id']);
        $prefix = match ($itemType->semantic_type) {
            ItemType::SEMANTIC_FEED => 'PKN',
            ItemType::SEMANTIC_NUTRITION => 'NTR',
            ItemType::SEMANTIC_MEDICINE => 'OBT',
            default => 'LNN',
        };
        $feedItem = $codes->create(FeedItem::class, 'code', $prefix, [
            ...$validated,
            'status' => 'ACTIVE',
        ]);

        return redirect()
            ->route('feed-items.show', $feedItem)
            ->with('success', 'Barang/Item berhasil ditambahkan.');
    }

    public function show(FeedItem $feedItem): View
    {
        $feedItem->load(['itemType:id,name,semantic_type', 'defaultVendor:id,code,name,vendor_type_id,status', 'defaultVendor.vendorType:id,name,semantic_type']);
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
        ]);
    }

    public function edit(FeedItem $feedItem): View
    {
        $feedItem->load(['itemType:id,name,semantic_type', 'defaultVendor:id,code,name,vendor_type_id,status', 'defaultVendor.vendorType:id,name,semantic_type']);

        return view('feed-items.edit', [
            'feedItem' => $feedItem,
            'itemTypes' => $this->itemTypes(),
            'vendors' => $this->availableVendors($feedItem),
        ]);
    }

    public function update(FeedItemRequest $request, FeedItem $feedItem): RedirectResponse
    {
        $feedItem->update($request->safe()->except(['code', 'status']));

        return redirect()
            ->route('feed-items.show', $feedItem)
            ->with('success', 'Barang/Item berhasil diperbarui.');
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
            ->with('vendorType:id,name,semantic_type')
            ->where(function (Builder $query) use ($feedItem): void {
                $query->where(function (Builder $query): void {
                    $query->where('status', 'ACTIVE');

                });

                if ($feedItem?->default_vendor_id) {
                    $query->orWhere('id', $feedItem->default_vendor_id);
                }
            })
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'vendor_type_id', 'status']);
    }

    /** @return Collection<int, ItemType> */
    private function itemTypes(): Collection
    {
        return ItemType::query()->orderByDesc('is_system')->orderBy('id')->get();
    }
}
