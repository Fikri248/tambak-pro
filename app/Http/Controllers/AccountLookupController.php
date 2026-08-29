<?php

namespace App\Http\Controllers;

use App\Http\Requests\AccountLookupRequest;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
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
                'option' => ['value' => (string) $option->id, 'label' => $option->name],
            ], 201);
        }

        $input = $request->only([
            'number_code',
            'description_id',
            'account_type_id',
            'financial_statement_id',
        ]);
        $input[$foreignKey] = (string) $option->id;

        return back()->withInput($input)->with('success', $message);
    }
}
