<?php

namespace App\Http\Requests;

use App\Models\AccountDescription;
use App\Models\AccountType;
use App\Models\FinancialStatement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class AccountLookupRequest extends FormRequest
{
    public const TYPES = [
        'description' => [AccountDescription::class, 'new_description', 'description_id', 'Deskripsi'],
        'account_type' => [AccountType::class, 'new_account_type', 'account_type_id', 'Tipe Akun'],
        'financial_statement' => [FinancialStatement::class, 'new_financial_statement', 'financial_statement_id', 'Laporan Keuangan'],
    ];

    public function authorize(): bool
    {
        return $this->user()?->canAccess('chart-of-accounts.manage') === true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'lookup_type' => ['required', Rule::in(array_keys(self::TYPES))],
            'new_description' => ['exclude_unless:lookup_type,description', 'required', 'string', 'max:255'],
            'new_account_type' => ['exclude_unless:lookup_type,account_type', 'required', 'string', 'max:255'],
            'new_financial_statement' => ['exclude_unless:lookup_type,financial_statement', 'required', 'string', 'max:255'],
        ];
    }

    protected function prepareForValidation(): void
    {
        foreach (self::TYPES as [, $field]) {
            if ($this->exists($field)) {
                $this->merge([$field => trim((string) $this->input($field))]);
            }
        }
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $definition = self::TYPES[$this->input('lookup_type')] ?? null;

            if (! $definition) {
                return;
            }

            [$model, $field, , $label] = $definition;
            $name = (string) $this->input($field);

            if ($name !== '' && $model::query()->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->exists()) {
                $validator->errors()->add($field, "{$label} sudah tersedia.");
            }
        }];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'lookup_type.required' => 'Jenis data pilihan wajib ditentukan.',
            'lookup_type.in' => 'Jenis data pilihan tidak valid.',
            'new_description.required' => 'Nama Deskripsi wajib diisi.',
            'new_account_type.required' => 'Nama Tipe Akun wajib diisi.',
            'new_financial_statement.required' => 'Nama Laporan Keuangan wajib diisi.',
            '*.max' => 'Nama pilihan maksimal 255 karakter.',
        ];
    }
}
