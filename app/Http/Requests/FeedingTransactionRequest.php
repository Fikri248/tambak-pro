<?php

namespace App\Http\Requests;

use App\Models\PondStock;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class FeedingTransactionRequest extends FormRequest
{
    private const MAX_QUANTITY = '999999999999999.999';

    private const MAX_UNIT_COST = '99999999999999.9999';

    private const MAX_TOTAL_COST = '9999999999999999.99';

    public function authorize(): bool
    {
        return $this->user()?->canAccess('feeding.create') === true;
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
            'batch_id' => ['nullable', 'integer', 'exists:commodity_batches,id'],
            'feed_item_id' => [
                'required',
                'integer',
                Rule::exists('feed_items', 'id')->where(fn (Builder $query) => $query->where('status', 'ACTIVE')),
            ],
            'vendor_id' => [
                'nullable',
                'integer',
                Rule::exists('vendors', 'id')->where(fn (Builder $query) => $query->where('status', 'ACTIVE')),
            ],
            'feed_quantity' => ['required', 'numeric', 'gt:0', 'decimal:0,3', 'max:'.self::MAX_QUANTITY],
            'unit_cost' => ['required', 'numeric', 'min:0', 'decimal:0,4', 'max:'.self::MAX_UNIT_COST],
            'notes' => ['nullable', 'string', 'max:16000'],
            'transaction_number' => ['prohibited'],
            'stock_quantity_snapshot' => ['prohibited'],
            'total_cost' => ['exclude'],
            'created_by' => ['prohibited'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! $validator->errors()->hasAny(['feed_quantity', 'unit_cost'])) {
                $totalCost = bcmul(
                    (string) $this->input('feed_quantity'),
                    (string) $this->input('unit_cost'),
                    7,
                );

                if (bccomp($totalCost, self::MAX_TOTAL_COST, 7) === 1) {
                    $validator->errors()->add('unit_cost', 'Total biaya hasil perhitungan melebihi kapasitas penyimpanan.');
                }
            }

            if ($validator->errors()->hasAny(['location_id', 'batch_id'])) {
                return;
            }

            $stocks = PondStock::query()
                ->where('location_id', $this->integer('location_id'))
                ->where('quantity', '>', 0);

            if ($this->filled('batch_id')) {
                if (! $stocks->where('batch_id', $this->integer('batch_id'))->exists()) {
                    $validator->errors()->add('batch_id', 'Batch tidak tersedia pada petak tersebut.');
                }

                return;
            }

            if (! $stocks->exists()) {
                $validator->errors()->add('location_id', 'Petak tidak memiliki stok aktif.');
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'batch_id' => $this->filled('batch_id') ? $this->input('batch_id') : null,
            'vendor_id' => $this->filled('vendor_id') ? $this->input('vendor_id') : null,
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
            'location_id.required' => 'Petak wajib dipilih.',
            'location_id.exists' => 'Petak yang dipilih tidak valid.',
            'batch_id.exists' => 'Batch tidak tersedia pada petak tersebut.',
            'feed_item_id.required' => 'Barang/Item wajib dipilih.',
            'feed_item_id.exists' => 'Barang/Item yang dipilih tidak aktif.',
            'vendor_id.exists' => 'Vendor yang dipilih tidak valid.',
            'feed_quantity.required' => 'Jumlah penggunaan wajib diisi.',
            'feed_quantity.gt' => 'Jumlah penggunaan harus lebih dari 0.',
            'feed_quantity.decimal' => 'Jumlah penggunaan maksimal memiliki 3 angka desimal.',
            'feed_quantity.max' => 'Jumlah penggunaan melebihi batas penyimpanan.',
            'unit_cost.required' => 'Harga per satuan wajib diisi.',
            'unit_cost.min' => 'Harga per satuan tidak boleh negatif.',
            'unit_cost.decimal' => 'Harga per satuan maksimal memiliki 4 angka desimal.',
            'unit_cost.max' => 'Harga per satuan melebihi batas penyimpanan.',
            'transaction_number.prohibited' => 'Nomor transaksi ditentukan oleh sistem.',
            'stock_quantity_snapshot.prohibited' => 'Stok saat pencatatan dihitung oleh sistem.',
            'created_by.prohibited' => 'Pengguna pencatat ditentukan oleh sistem.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'transaction_date' => 'Tanggal Transaksi',
            'location_id' => 'Petak',
            'batch_id' => 'Batch',
            'feed_item_id' => 'Barang/Item',
            'vendor_id' => 'Vendor',
            'feed_quantity' => 'Jumlah Penggunaan',
            'unit_cost' => 'Harga per Satuan',
            'total_cost' => 'Total Biaya',
            'stock_quantity_snapshot' => 'Stok Saat Pencatatan',
            'notes' => 'Catatan',
            'created_by' => 'Dicatat Oleh',
            'transaction_number' => 'No. Transaksi',
        ];
    }
}
