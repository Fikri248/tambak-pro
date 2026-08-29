<?php

namespace App\Http\Controllers;

use App\Http\Requests\AccountLookupRequest;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AccountLookupController extends Controller
{
    public function store(AccountLookupRequest $request): JsonResponse|RedirectResponse
    {
        [$model, $field, $foreignKey, $label] = AccountLookupRequest::TYPES[$request->string('lookup_type')->toString()];
        $name = $request->string($field)->trim()->toString();

        try {
            $option = $model::query()->create(['name' => $name, 'status' => 'ACTIVE']);
        } catch (QueryException $exception) {
            if (! in_array((string) ($exception->errorInfo[0] ?? $exception->getCode()), ['23000', '23505'], true)) {
                throw $exception;
            }

            throw ValidationException::withMessages([$field => "{$label} sudah tersedia."]);
        }

        $message = "{$label} berhasil ditambahkan.";

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'field' => $foreignKey,
                'option' => $this->optionPayload($option, $request->string('lookup_type')->toString()),
            ], 201);
        }

        $input = $this->parentInput($request);
        $input[$foreignKey] = (string) $option->id;

        return back()->withInput($input)->with('success', $message);
    }

    public function update(AccountLookupRequest $request, string $lookupType, int $lookup): JsonResponse|RedirectResponse
    {
        [$model, , $foreignKey, $label] = AccountLookupRequest::TYPES[$lookupType];
        $option = $model::query()->findOrFail($lookup);
        $name = $request->string('lookup_name')->trim()->toString();

        try {
            $option->update(['name' => $name]);
        } catch (QueryException $exception) {
            if (! $this->isUniqueConstraintViolation($exception)) {
                throw $exception;
            }

            throw ValidationException::withMessages(['lookup_name' => "{$label} sudah tersedia."]);
        }

        $message = "{$label} berhasil diperbarui.";

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'field' => $foreignKey,
                'option' => $this->optionPayload($option, $lookupType),
            ]);
        }

        return back()->withInput($this->parentInput($request))->with('success', $message);
    }

    public function destroy(AccountLookupRequest $request, string $lookupType, int $lookup): JsonResponse|RedirectResponse
    {
        [$model, , $foreignKey, $label] = AccountLookupRequest::TYPES[$lookupType];
        $option = $model::query()->findOrFail($lookup);
        $usageCount = $option->chartOfAccounts()->count();

        if ($usageCount > 0) {
            return $this->blockedDeleteResponse($request, $label, $usageCount);
        }

        try {
            DB::transaction(function () use ($model, $lookup, $label): void {
                $lockedOption = $model::query()->lockForUpdate()->findOrFail($lookup);
                $usageCount = $lockedOption->chartOfAccounts()->count();

                if ($usageCount > 0) {
                    throw ValidationException::withMessages([
                        'lookup_delete' => $this->dependencyMessage($label, $usageCount),
                    ]);
                }

                $lockedOption->delete();
            });
        } catch (QueryException $exception) {
            if (! $this->isForeignKeyConstraintViolation($exception)) {
                throw $exception;
            }

            return $this->blockedDeleteResponse($request, $label, $option->chartOfAccounts()->count() ?: 1);
        } catch (ValidationException $exception) {
            $usageCount = $option->chartOfAccounts()->count() ?: 1;

            return $this->blockedDeleteResponse($request, $label, $usageCount);
        }

        $message = "{$label} berhasil dihapus.";

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'field' => $foreignKey,
                'deleted_id' => (string) $option->id,
            ]);
        }

        $input = $this->parentInput($request);

        if ((string) ($input[$foreignKey] ?? '') === (string) $option->id) {
            $input[$foreignKey] = '';
        }

        return back()->withInput($input)->with('success', $message);
    }

    /** @return array{id: string, name: string, status: string, value: string, label: string, update_url: string, delete_url: string} */
    private function optionPayload(object $option, string $lookupType): array
    {
        return [
            'id' => (string) $option->id,
            'name' => $option->name,
            'status' => $option->status,
            'value' => (string) $option->id,
            'label' => $option->name,
            'update_url' => route('chart-of-accounts.lookups.update', [$lookupType, $option->id]),
            'delete_url' => route('chart-of-accounts.lookups.destroy', [$lookupType, $option->id]),
        ];
    }

    /** @return array<string, mixed> */
    private function parentInput(AccountLookupRequest $request): array
    {
        return $request->only([
            'number_code',
            'description_id',
            'account_type_id',
            'financial_statement_id',
        ]);
    }

    private function blockedDeleteResponse(AccountLookupRequest $request, string $label, int $usageCount): JsonResponse|RedirectResponse
    {
        $message = $this->dependencyMessage($label, $usageCount);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'errors' => ['lookup_delete' => [$message]],
            ], 409);
        }

        return back()->withInput($this->parentInput($request) + ['lookup_type' => $request->string('lookup_type')->toString()])
            ->withErrors(['lookup_delete' => $message]);
    }

    private function dependencyMessage(string $label, int $usageCount): string
    {
        $subject = match ($label) {
            'Tipe Akun' => 'Tipe akun',
            'Laporan Keuangan' => 'Laporan keuangan',
            default => $label,
        };

        return "{$subject} tidak dapat dihapus karena digunakan oleh {$usageCount} Chart of Accounts.";
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
