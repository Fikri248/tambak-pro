<?php

namespace App\Http\Controllers;

use App\Http\Requests\VendorTypeRequest;
use App\Models\VendorType;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VendorTypeController extends Controller
{
    public function store(VendorTypeRequest $request): JsonResponse|RedirectResponse
    {
        try {
            $vendorType = VendorType::query()->create([
                'name' => $request->string('vendor_type_name')->trim()->toString(),
            ]);
        } catch (QueryException $exception) {
            if (! $this->isUniqueConstraintViolation($exception)) {
                throw $exception;
            }

            throw ValidationException::withMessages(['vendor_type_name' => 'Jenis Vendor sudah tersedia.']);
        }

        $message = 'Jenis Vendor berhasil ditambahkan.';

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'option' => $this->optionPayload($vendorType),
            ], 201);
        }

        return back()->withInput($this->parentInput($request) + [
            'vendor_type_id' => (string) $vendorType->id,
        ])->with('success', $message);
    }

    public function update(VendorTypeRequest $request, VendorType $vendorType): JsonResponse|RedirectResponse
    {
        try {
            $vendorType->update([
                'name' => $request->string('vendor_type_name')->trim()->toString(),
            ]);
        } catch (QueryException $exception) {
            if (! $this->isUniqueConstraintViolation($exception)) {
                throw $exception;
            }

            throw ValidationException::withMessages(['vendor_type_name' => 'Jenis Vendor sudah tersedia.']);
        }

        $message = 'Jenis Vendor berhasil diperbarui.';

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'option' => $this->optionPayload($vendorType),
            ]);
        }

        return back()->withInput($this->parentInput($request))->with('success', $message);
    }

    public function destroy(VendorTypeRequest $request, VendorType $vendorType): JsonResponse|RedirectResponse
    {
        if ($vendorType->is_system) {
            return $this->blockedResponse($request, 'Jenis Vendor bawaan sistem tidak dapat dihapus.');
        }

        $usageCount = $vendorType->vendors()->count();

        if ($usageCount > 0) {
            return $this->blockedResponse($request, $this->dependencyMessage($usageCount));
        }

        try {
            DB::transaction(function () use ($vendorType): void {
                $lockedType = VendorType::query()->lockForUpdate()->findOrFail($vendorType->id);

                if ($lockedType->is_system) {
                    throw ValidationException::withMessages([
                        'vendor_type_delete' => 'Jenis Vendor bawaan sistem tidak dapat dihapus.',
                    ]);
                }

                $usageCount = $lockedType->vendors()->count();

                if ($usageCount > 0) {
                    throw ValidationException::withMessages([
                        'vendor_type_delete' => $this->dependencyMessage($usageCount),
                    ]);
                }

                $lockedType->delete();
            });
        } catch (ValidationException $exception) {
            return $this->blockedResponse(
                $request,
                collect($exception->errors())->flatten()->first() ?: 'Jenis Vendor tidak dapat dihapus.',
            );
        } catch (QueryException $exception) {
            if (! $this->isForeignKeyConstraintViolation($exception)) {
                throw $exception;
            }

            return $this->blockedResponse($request, $this->dependencyMessage($vendorType->vendors()->count() ?: 1));
        }

        $message = 'Jenis Vendor berhasil dihapus.';

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'deleted_id' => (string) $vendorType->id,
            ]);
        }

        $input = $this->parentInput($request);

        if ((string) ($input['vendor_type_id'] ?? '') === (string) $vendorType->id) {
            $input['vendor_type_id'] = '';
        }

        return back()->withInput($input)->with('success', $message);
    }

    /** @return array<string, mixed> */
    private function optionPayload(VendorType $vendorType): array
    {
        return [
            'id' => (string) $vendorType->id,
            'name' => $vendorType->name,
            'value' => (string) $vendorType->id,
            'label' => $vendorType->name,
            'is_system' => $vendorType->is_system,
            'update_url' => route('vendor-types.update', $vendorType),
            'delete_url' => route('vendor-types.destroy', $vendorType),
        ];
    }

    /** @return array<string, mixed> */
    private function parentInput(VendorTypeRequest $request): array
    {
        return $request->only([
            'name',
            'vendor_type_id',
            'phone',
            'email',
            'address',
            'description',
        ]);
    }

    private function blockedResponse(VendorTypeRequest $request, string $message): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'errors' => ['vendor_type_delete' => [$message]],
            ], 409);
        }

        return back()->withInput($this->parentInput($request))
            ->withErrors(['vendor_type_delete' => $message]);
    }

    private function dependencyMessage(int $usageCount): string
    {
        return "Jenis Vendor tidak dapat dihapus karena digunakan oleh {$usageCount} Vendor.";
    }

    private function isUniqueConstraintViolation(QueryException $exception): bool
    {
        return in_array((string) ($exception->errorInfo[0] ?? $exception->getCode()), ['23000', '23505'], true);
    }

    private function isForeignKeyConstraintViolation(QueryException $exception): bool
    {
        return in_array((string) ($exception->errorInfo[0] ?? $exception->getCode()), ['23000', '23503'], true);
    }
}
