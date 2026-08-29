<?php

namespace App\Http\Requests;

use App\Models\ChartOfAccount;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

class ChartOfAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canAccess('chart-of-accounts.manage') === true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $account = $this->route('chartOfAccount');
        $account = $account instanceof ChartOfAccount ? $account : null;

        return [
            'number_code' => [
                'required',
                'string',
                'max:50',
                'regex:/^\d+$/',
                Rule::unique('chart_of_accounts', 'number_code')->ignore($account),
            ],
            'description_id' => ['required', $this->activeLookup('account_descriptions', $account?->description_id)],
            'account_type_id' => ['required', $this->activeLookup('account_types', $account?->account_type_id)],
            'financial_statement_id' => ['required', $this->activeLookup('financial_statements', $account?->financial_statement_id)],
            'status' => ['prohibited'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->exists('number_code')) {
            $this->merge(['number_code' => trim((string) $this->input('number_code'))]);
        }
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'number_code.required' => 'Nomor akun wajib diisi.',
            'number_code.string' => 'Nomor akun harus berupa teks numerik.',
            'number_code.max' => 'Nomor akun maksimal 50 digit.',
            'number_code.regex' => 'Nomor akun hanya boleh berisi angka 0-9.',
            'number_code.unique' => 'Nomor akun sudah digunakan.',
            'description_id.required' => 'Deskripsi wajib dipilih.',
            'description_id.exists' => 'Deskripsi tidak aktif atau tidak valid.',
            'account_type_id.required' => 'Tipe Akun wajib dipilih.',
            'account_type_id.exists' => 'Tipe Akun tidak aktif atau tidak valid.',
            'financial_statement_id.required' => 'Laporan Keuangan wajib dipilih.',
            'financial_statement_id.exists' => 'Laporan Keuangan tidak aktif atau tidak valid.',
            'status.prohibited' => 'Status hanya dapat diubah melalui aksi status Chart of Accounts.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'number_code' => 'Nomor Akun',
            'description_id' => 'Deskripsi',
            'account_type_id' => 'Tipe Akun',
            'financial_statement_id' => 'Laporan Keuangan',
        ];
    }

    private function activeLookup(string $table, ?int $currentId): Exists
    {
        return Rule::exists($table, 'id')->where(function (Builder $query) use ($currentId): void {
            $query->where(function (Builder $query) use ($currentId): void {
                $query->where('status', 'ACTIVE');

                if ($currentId !== null) {
                    $query->orWhere('id', $currentId);
                }
            });
        });
    }
}
