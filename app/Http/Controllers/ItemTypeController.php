<?php

namespace App\Http\Controllers;

use App\Http\Requests\ItemTypeRequest;
use App\Models\ItemType;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ItemTypeController extends Controller
{
    public function store(ItemTypeRequest $request): JsonResponse|RedirectResponse
    {
        $itemType = ItemType::query()->create(['name' => trim((string) $request->input('item_type_name'))]);
        $message = 'Jenis Barang/Item berhasil ditambahkan.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $message, 'option' => $this->optionPayload($itemType)], 201);
        }

        return back()->withInput($this->parentInput($request) + ['item_type_id' => (string) $itemType->id])
            ->with('success', $message);
    }

    public function update(ItemTypeRequest $request, ItemType $itemType): JsonResponse|RedirectResponse
    {
        $itemType->update(['name' => trim((string) $request->input('item_type_name'))]);
        $message = 'Jenis Barang/Item berhasil diperbarui.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $message, 'option' => $this->optionPayload($itemType)]);
        }

        return back()->withInput($this->parentInput($request))->with('success', $message);
    }

    public function destroy(ItemTypeRequest $request, ItemType $itemType): JsonResponse|RedirectResponse
    {
        if ($itemType->is_system) {
            return $this->blockedResponse($request, 'Jenis Barang/Item bawaan sistem tidak dapat dihapus.');
        }

        $usageCount = $itemType->feedItems()->count();

        if ($usageCount > 0) {
            return $this->blockedResponse($request, $this->dependencyMessage($usageCount));
        }

        try {
            DB::transaction(function () use ($itemType): void {
                $lockedType = ItemType::query()->lockForUpdate()->findOrFail($itemType->id);
                $count = $lockedType->feedItems()->count();

                if ($lockedType->is_system || $count > 0) {
                    throw ValidationException::withMessages([
                        'item_type_delete' => $lockedType->is_system
                            ? 'Jenis Barang/Item bawaan sistem tidak dapat dihapus.'
                            : $this->dependencyMessage($count),
                    ]);
                }

                $lockedType->delete();
            });
        } catch (ValidationException $exception) {
            return $this->blockedResponse($request, collect($exception->errors())->flatten()->first());
        } catch (QueryException) {
            return $this->blockedResponse($request, $this->dependencyMessage($itemType->feedItems()->count() ?: 1));
        }

        $message = 'Jenis Barang/Item berhasil dihapus.';

        return $request->expectsJson()
            ? response()->json(['message' => $message, 'deleted_id' => $itemType->id])
            : back()->withInput($this->parentInput($request))->with('success', $message);
    }

    /** @return array<string, mixed> */
    private function optionPayload(ItemType $itemType): array
    {
        return [
            'id' => $itemType->id,
            'name' => $itemType->name,
            'is_system' => $itemType->is_system,
            'update_url' => route('item-types.update', $itemType),
            'delete_url' => route('item-types.destroy', $itemType),
        ];
    }

    private function blockedResponse(ItemTypeRequest $request, string $message): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 409);
        }

        return back()->withInput($this->parentInput($request))->withErrors(['item_type_delete' => $message]);
    }

    private function dependencyMessage(int $count): string
    {
        return "Jenis Barang/Item tidak dapat dihapus karena digunakan oleh {$count} Barang/Item.";
    }

    /** @return array<string, mixed> */
    private function parentInput(ItemTypeRequest $request): array
    {
        return $request->except(['_token', '_method', 'item_type_name', 'code', 'semantic_type', 'is_system']);
    }
}
