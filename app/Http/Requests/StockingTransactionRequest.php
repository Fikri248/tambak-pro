<?php

namespace App\Http\Requests;

use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StockingTransactionRequest extends FormRequest
{
    private const MAX_QUANTITY = '999999999999999.999';

    private const MAX_TOTAL_COST = '9999999999999999.99';

    private const MAX_UNIT_COST = '99999999999999.9999';

    public function authorize(): bool
    {
        return $this->user()?->canAccess('stocking.create') === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'transaction_date' => ['required', 'date'],
            'location_id' => [
                'required',
                'integer',
                Rule::exists('locations', 'id')->where(
                    fn (Builder $query) => $query
                        ->where('location_type', 'PETAK')
                        ->where('status', 'ACTIVE'),
                ),
            ],
            'commodity_id' => [
                'required',
                'integer',
                Rule::exists('commodities', 'id')->where(
                    fn (Builder $query) => $query->where('status', 'ACTIVE'),
                ),
            ],
            'vendor_id' => [
                'required',
                'integer',
                Rule::exists('vendors', 'id')->where(
                    fn (Builder $query) => $query
                        ->where('status', 'ACTIVE')
                        ->whereIn('vendor_type', ['SEED', 'MULTIPLE']),
                ),
            ],
            'batch_code' => ['prohibited'],
            'quantity' => ['required', 'numeric', 'gt:0', 'decimal:0,3', 'max:'.self::MAX_QUANTITY],
            'total_cost' => ['required', 'numeric', 'min:0', 'decimal:0,2', 'max:'.self::MAX_TOTAL_COST],
            'notes' => ['nullable', 'string', 'max:16000'],
            'created_by' => ['prohibited'],
            'unit_cost' => ['prohibited'],
        ];
    }

    /** @return array<int, callable(Validator): void> */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['quantity', 'total_cost'])) {
                    return;
                }

                $maximumTotalForQuantity = bcmul(
                    (string) $this->input('quantity'),
                    self::MAX_UNIT_COST,
                    7,
                );

                if (bccomp((string) $this->input('total_cost'), $maximumTotalForQuantity, 7) === 1) {
                    $validator->errors()->add('total_cost', 'Harga per satuan hasil perhitungan melebihi kapasitas penyimpanan.');
                }
            },
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'notes' => $this->filled('notes') ? trim((string) $this->input('notes')) : null,
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'transaction_date.required' => 'Tanggal transaksi wajib diisi.',
            'transaction_date.date' => 'Tanggal transaksi tidak valid.',
            'location_id.required' => 'Lokasi petak wajib dipilih.',
            'location_id.exists' => 'Lokasi petak yang dipilih tidak valid.',
            'commodity_id.required' => 'Komoditas wajib dipilih.',
            'commodity_id.exists' => 'Komoditas yang dipilih tidak aktif.',
            'vendor_id.required' => 'Vendor wajib dipilih.',
            'vendor_id.exists' => 'Vendor yang dipilih tidak dapat digunakan untuk pembibitan.',
            'batch_code.prohibited' => 'Kode Batch dibuat otomatis oleh sistem.',
            'quantity.required' => 'Jumlah bibit wajib diisi.',
            'quantity.gt' => 'Jumlah bibit harus lebih dari 0.',
            'quantity.decimal' => 'Jumlah bibit maksimal memiliki 3 angka desimal.',
            'quantity.max' => 'Jumlah bibit melebihi batas penyimpanan.',
            'total_cost.required' => 'Total biaya wajib diisi.',
            'total_cost.min' => 'Total biaya tidak boleh negatif.',
            'total_cost.decimal' => 'Total biaya maksimal memiliki 2 angka desimal.',
            'total_cost.max' => 'Total biaya melebihi batas penyimpanan.',
            'created_by.prohibited' => 'Pengguna pencatat ditentukan oleh sistem.',
            'unit_cost.prohibited' => 'Harga per satuan dihitung oleh sistem.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'transaction_date' => 'Tanggal Transaksi',
            'location_id' => 'Petak',
            'commodity_id' => 'Komoditas',
            'vendor_id' => 'Vendor Bibit',
            'batch_code' => 'Kode Batch',
            'quantity' => 'Jumlah Bibit',
            'total_cost' => 'Total Biaya',
            'unit_cost' => 'Harga per Satuan',
            'notes' => 'Catatan',
            'created_by' => 'Dicatat Oleh',
        ];
    }
}
