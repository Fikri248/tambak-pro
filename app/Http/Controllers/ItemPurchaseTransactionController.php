<?php

namespace App\Http\Controllers;

use App\Http\Requests\ItemPurchaseRequest;
use App\Models\AuditLog;
use App\Models\FeedItem;
use App\Models\ItemPurchaseTransaction;
use App\Models\ItemType;
use App\Models\Vendor;
use App\Support\PageSize;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ItemPurchaseTransactionController extends Controller
{
    private const MAX_TOTAL_COST = '9999999999999999.99';

    public function index(Request $request): View
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'feed_item_id' => ['nullable', 'integer', 'exists:feed_items,id'],
            'vendor_id' => ['nullable', 'integer', 'exists:vendors,id'],
            'item_type_id' => ['nullable', 'integer', 'exists:item_types,id'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);
        $search = mb_substr(trim((string) ($validated['search'] ?? '')), 0, 255);
        $itemId = $request->integer('feed_item_id') ?: null;
        $vendorId = $request->integer('vendor_id') ?: null;
        $typeId = $request->integer('item_type_id') ?: null;
        $dateFrom = $validated['date_from'] ?? null;
        $dateTo = $validated['date_to'] ?? null;

        $query = ItemPurchaseTransaction::query()
            ->with(['feedItem:id,code,name,item_type_id,unit', 'feedItem.itemType:id,name', 'vendor:id,code,name', 'createdBy:id,name'])
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(fn (Builder $query) => $query
                    ->where('transaction_number', 'like', "%{$search}%")
                    ->orWhereHas('feedItem', fn (Builder $query) => $query->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('feedItem.itemType', fn (Builder $query) => $query->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('vendor', fn (Builder $query) => $query->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('createdBy', fn (Builder $query) => $query->where('name', 'like', "%{$search}%")));
            })
            ->when($itemId, fn (Builder $query, int $id) => $query->where('feed_item_id', $id))
            ->when($vendorId, fn (Builder $query, int $id) => $query->where('vendor_id', $id))
            ->when($typeId, fn (Builder $query, int $id) => $query->whereHas('feedItem', fn (Builder $query) => $query->where('item_type_id', $id)))
            ->when($dateFrom, fn (Builder $query, string $date) => $query->whereDate('transaction_date', '>=', $date))
            ->when($dateTo, fn (Builder $query, string $date) => $query->whereDate('transaction_date', '<=', $date));

        $summaryQuery = clone $query;
        $transactions = $query->latest('transaction_date')->latest('id')->paginate(PageSize::resolve($request))->withQueryString();

        return view('item-purchases.index', [
            'transactions' => $transactions,
            'summary' => ['total' => (clone $summaryQuery)->count(), 'cost' => (float) (clone $summaryQuery)->sum('total_cost')],
            'filters' => compact('search', 'itemId', 'vendorId', 'typeId', 'dateFrom', 'dateTo'),
            'feedItems' => FeedItem::query()->orderBy('name')->get(['id', 'name']),
            'vendors' => Vendor::query()->orderBy('name')->get(['id', 'name']),
            'itemTypes' => ItemType::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function create(): View
    {
        return view('item-purchases.create', $this->formData());
    }

    public function store(ItemPurchaseRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $purchase = DB::transaction(function () use ($request, $validated): ItemPurchaseTransaction {
            $item = FeedItem::query()->whereKey($validated['feed_item_id'])->where('status', 'ACTIVE')->lockForUpdate()->first();
            $vendor = Vendor::query()->whereKey($validated['vendor_id'])->where('status', 'ACTIVE')->lockForUpdate()->first();
            if (! $item) {
                throw ValidationException::withMessages(['feed_item_id' => 'Barang/Item yang dipilih tidak aktif.']);
            }
            if (! $vendor) {
                throw ValidationException::withMessages(['vendor_id' => 'Vendor yang dipilih tidak aktif.']);
            }
            $total = $this->total($validated['quantity'], $validated['unit_cost']);

            $purchase = ItemPurchaseTransaction::query()->create([
                'transaction_number' => 'PBL-TMP-'.Str::uuid(),
                'transaction_date' => Carbon::parse($validated['transaction_date']),
                'feed_item_id' => $item->id,
                'vendor_id' => $vendor->id,
                'quantity' => $validated['quantity'],
                'unit_cost' => $validated['unit_cost'],
                'total_cost' => $total,
                'created_by' => $request->user()->id,
                'notes' => $validated['notes'] ?? null,
            ]);
            $purchase->update(['transaction_number' => sprintf('PBL-%06d', $purchase->id)]);
            $this->audit($request, 'CREATE', $purchase, null, $this->values($purchase), "Pembelian Barang/Item {$purchase->transaction_number} dicatat");

            return $purchase;
        }, 3);

        return redirect()->route('item-purchases.show', $purchase)->with('success', 'Pembelian Barang/Item berhasil dicatat.');
    }

    public function show(ItemPurchaseTransaction $itemPurchase): View
    {
        $itemPurchase->load(['feedItem.itemType', 'vendor.vendorType', 'createdBy']);

        return view('item-purchases.show', compact('itemPurchase'));
    }

    public function edit(ItemPurchaseTransaction $itemPurchase): View
    {
        $itemPurchase->load(['feedItem.itemType', 'vendor.vendorType']);

        return view('item-purchases.edit', ['itemPurchase' => $itemPurchase, ...$this->formData($itemPurchase)]);
    }

    public function update(ItemPurchaseRequest $request, ItemPurchaseTransaction $itemPurchase): RedirectResponse
    {
        $validated = $request->validated();
        DB::transaction(function () use ($request, $itemPurchase, $validated): void {
            $purchase = ItemPurchaseTransaction::query()->lockForUpdate()->findOrFail($itemPurchase->id);
            $item = FeedItem::query()->whereKey($validated['feed_item_id'])->lockForUpdate()->first();
            $vendor = Vendor::query()->whereKey($validated['vendor_id'])->lockForUpdate()->first();
            if (! $item || ($item->status !== 'ACTIVE' && $item->id !== $purchase->feed_item_id)) {
                throw ValidationException::withMessages(['feed_item_id' => 'Barang/Item yang dipilih tidak aktif.']);
            }
            if (! $vendor || ($vendor->status !== 'ACTIVE' && $vendor->id !== $purchase->vendor_id)) {
                throw ValidationException::withMessages(['vendor_id' => 'Vendor yang dipilih tidak aktif.']);
            }
            $old = $this->values($purchase);
            $purchase->update([
                'transaction_date' => Carbon::parse($validated['transaction_date']),
                'feed_item_id' => $item->id, 'vendor_id' => $vendor->id,
                'quantity' => $validated['quantity'], 'unit_cost' => $validated['unit_cost'],
                'total_cost' => $this->total($validated['quantity'], $validated['unit_cost']),
                'notes' => $validated['notes'] ?? null,
            ]);
            $this->audit($request, 'UPDATE', $purchase, $old, $this->values($purchase), "Pembelian Barang/Item {$purchase->transaction_number} diperbarui");
        }, 3);

        return redirect()->route('item-purchases.show', $itemPurchase)->with('success', 'Pembelian Barang/Item berhasil diperbarui.');
    }

    public function destroy(Request $request, ItemPurchaseTransaction $itemPurchase): RedirectResponse
    {
        DB::transaction(function () use ($request, $itemPurchase): void {
            $purchase = ItemPurchaseTransaction::query()->lockForUpdate()->findOrFail($itemPurchase->id);
            $this->audit($request, 'DELETE', $purchase, $this->values($purchase), null, "Pembelian Barang/Item {$purchase->transaction_number} dihapus");
            $purchase->delete();
        }, 3);

        return redirect()->route('item-purchases.index')->with('success', 'Pembelian Barang/Item berhasil dihapus. Stok Barang/Item tidak berubah.');
    }

    /** @return array<string, mixed> */
    private function formData(?ItemPurchaseTransaction $purchase = null): array
    {
        return [
            'feedItems' => FeedItem::query()->with('itemType:id,name')->where(fn (Builder $query) => $query->where('status', 'ACTIVE')->when($purchase, fn (Builder $query) => $query->orWhere('id', $purchase->feed_item_id)))->orderBy('name')->get(),
            'vendors' => Vendor::query()->with('vendorType:id,name')->where(fn (Builder $query) => $query->where('status', 'ACTIVE')->when($purchase, fn (Builder $query) => $query->orWhere('id', $purchase->vendor_id)))->orderBy('name')->get(),
        ];
    }

    private function total(mixed $quantity, mixed $unitCost): string
    {
        $total = bcadd(bcmul((string) $quantity, (string) $unitCost, 3), '0.005', 2);
        if (bccomp($total, self::MAX_TOTAL_COST, 2) === 1) {
            throw ValidationException::withMessages(['unit_cost' => 'Total Biaya melebihi kapasitas penyimpanan.']);
        }

        return $total;
    }

    /** @return array<string, mixed> */
    private function values(ItemPurchaseTransaction $purchase): array
    {
        return ['transaction_date' => $purchase->transaction_date->toDateTimeString(), 'feed_item_id' => $purchase->feed_item_id, 'vendor_id' => $purchase->vendor_id, 'quantity' => (string) $purchase->quantity, 'unit_cost' => (string) $purchase->unit_cost, 'total_cost' => (string) $purchase->total_cost, 'notes' => $purchase->notes, 'created_by' => $purchase->created_by];
    }

    private function audit(Request $request, string $action, ItemPurchaseTransaction $purchase, ?array $old, ?array $new, string $description): void
    {
        AuditLog::query()->create(['user_id' => $request->user()->id, 'action' => $action, 'module' => 'ITEM_PURCHASE_TRANSACTION', 'record_id' => $purchase->id, 'transaction_number' => $purchase->transaction_number, 'description' => $description, 'old_values' => $old, 'new_values' => $new]);
    }
}
