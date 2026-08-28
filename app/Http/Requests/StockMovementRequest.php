<?php

namespace App\Http\Requests;

use App\Models\PondStock;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StockMovementRequest extends FormRequest
{
    private const MAX_QUANTITY = '999999999999999.999';

    public function authorize(): bool
    {
        return $this->user()?->canAccess('movements.create') === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'transaction_date' => ['required', 'date'],
            'from_location_id' => [
                'required',
                'integer',
                Rule::exists('locations', 'id')->where(
                    fn (Builder $query) => $query
                        ->where('location_type', 'PETAK')
                        ->where('status', 'ACTIVE'),
                ),
            ],
            'to_location_id' => [
                'required',
                'integer',
                'different:from_location_id',
                Rule::exists('locations', 'id')->where(
                    fn (Builder $query) => $query
                        ->where('location_type', 'PETAK')
                        ->where('status', 'ACTIVE'),
                ),
            ],
            'batch_id' => [
                'required',
                'integer',
                Rule::exists('pond_stocks', 'batch_id')->where(
                    fn (Builder $query) => $query
                        ->where('location_id', $this->input('from_location_id'))
                        ->where('quantity', '>', 0),
                ),
            ],
            'quantity' => ['required', 'numeric', 'gt:0', 'decimal:0,3', 'max:'.self::MAX_QUANTITY],
            'notes' => ['nullable', 'string', 'max:16000'],
            'created_by' => ['prohibited'],
            'transaction_number' => ['prohibited'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->hasAny(['from_location_id', 'batch_id', 'quantity'])) {
                return;
            }

            $available = PondStock::query()
                ->where('location_id', $this->integer('from_location_id'))
                ->where('batch_id', $this->integer('batch_id'))
                ->value('quantity');

            if ($available === null || (float) $available <= 0) {
                $validator->errors()->add('batch_id', 'Batch tidak memiliki stok yang dapat dipindahkan dari petak asal.');

                return;
            }

            if ((float) $this->input('quantity') > (float) $available) {
                $validator->errors()->add('quantity', 'Stok tidak mencukupi untuk pemindahan.');
            }
        });
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
            'from_location_id.required' => 'Petak asal wajib dipilih.',
            'from_location_id.exists' => 'Petak asal tidak valid.',
            'to_location_id.required' => 'Petak tujuan wajib dipilih.',
            'to_location_id.exists' => 'Petak tujuan tidak valid.',
            'to_location_id.different' => 'Petak asal dan petak tujuan harus berbeda.',
            'batch_id.required' => 'Batch wajib dipilih.',
            'batch_id.exists' => 'Batch tidak memiliki stok yang dapat dipindahkan dari petak asal.',
            'quantity.required' => 'Jumlah yang dipindahkan wajib diisi.',
            'quantity.gt' => 'Jumlah yang dipindahkan harus lebih dari 0.',
            'quantity.decimal' => 'Jumlah yang dipindahkan maksimal memiliki 3 angka desimal.',
            'quantity.max' => 'Jumlah yang dipindahkan melebihi batas penyimpanan.',
            'created_by.prohibited' => 'Pengguna pencatat ditentukan oleh sistem.',
            'transaction_number.prohibited' => 'Nomor transaksi ditentukan oleh sistem.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'transaction_date' => 'Tanggal Transaksi',
            'from_location_id' => 'Petak Asal',
            'to_location_id' => 'Petak Tujuan',
            'batch_id' => 'Batch',
            'quantity' => 'Jumlah Dipindahkan',
            'notes' => 'Catatan',
            'created_by' => 'Dicatat Oleh',
            'transaction_number' => 'No. Transaksi',
        ];
    }
}
