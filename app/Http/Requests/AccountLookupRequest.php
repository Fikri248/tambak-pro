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
        $rules = [
            'lookup_type' => ['required', Rule::in(array_keys(self::TYPES))],
        ];

        if ($this->isMethod('PATCH')) {
            $rules['lookup_name'] = ['required', 'string', 'max:255'];

            return $rules;
        }

        if ($this->isMethod('POST')) {
            $rules['new_description'] = ['exclude_unless:lookup_type,description', 'required', 'string', 'max:255'];
            $rules['new_account_type'] = ['exclude_unless:lookup_type,account_type', 'required', 'string', 'max:255'];
            $rules['new_financial_statement'] = ['exclude_unless:lookup_type,financial_statement', 'required', 'string', 'max:255'];
        }

        return $rules;
    }

    protected function prepareForValidation(): void
    {
        $lookupType = (string) ($this->route('lookupType') ?: $this->input('lookup_type'));

        if ($lookupType !== '') {
            $this->merge(['lookup_type' => $lookupType]);
        }

        foreach (self::TYPES as [, $field]) {
            if ($this->exists($field)) {
                $this->merge([$field => trim((string) $this->input($field))]);
            }
        }

        if ($this->isMethod('PATCH')) {
            $lookup = (string) $this->route('lookup');
            $fallbackName = $this->input("lookup_names.{$lookupType}.{$lookup}");
            $this->merge(['lookup_name' => trim((string) ($this->input('lookup_name') ?? $fallbackName))]);
        }
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $definition = self::TYPES[$this->input('lookup_type')] ?? null;

            if (! $definition) {
                return;
            }

            [$model, $createField, , $label] = $definition;
            $field = $this->isMethod('PATCH') ? 'lookup_name' : $createField;
            $name = (string) $this->input($field);

            $query = $model::query()->whereRaw('LOWER(name) = ?', [mb_strtolower($name)]);

            if ($this->isMethod('PATCH')) {
                $query->whereKeyNot((int) $this->route('lookup'));
            }

            if ($name !== '' && $query->exists()) {
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
            'lookup_name.required' => 'Nama pilihan wajib diisi.',
            '*.max' => 'Nama pilihan maksimal 255 karakter.',
        ];
    }
}
