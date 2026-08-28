<?php

namespace App\Http\Requests;

use App\Models\PondStock;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StockAdjustmentRequest extends FormRequest
{
    private const MAX_QUANTITY = '999999999999999.999';

    private const TYPES = [
        'MORTALITY',
        'LOSS',
        'CORRECTION_IN',
        'CORRECTION_OUT',
        'OTHER',
    ];

    public function authorize(): bool
    {
        return $this->user()?->canAccess('adjustments.create') === true;
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
            'batch_id' => [
                'required',
                'integer',
                Rule::exists('pond_stocks', 'batch_id')->where(
                    fn (Builder $query) => $query
                        ->where('location_id', $this->input('location_id'))
                        ->where('quantity', '>', 0),
                ),
            ],
            'adjustment_type' => ['required', Rule::in(self::TYPES)],
            'direction' => ['nullable', 'required_if:adjustment_type,OTHER', Rule::in(['IN', 'OUT'])],
            'quantity' => ['required', 'numeric', 'gt:0', 'decimal:0,3', 'max:'.self::MAX_QUANTITY],
            'reason' => ['required', 'string', 'max:2000'],
            'created_by' => ['prohibited'],
            'transaction_number' => ['prohibited'],
            'quantity_change' => ['prohibited'],
            'quantity_before' => ['prohibited'],
            'quantity_after' => ['prohibited'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->hasAny([
                'location_id',
                'batch_id',
                'adjustment_type',
                'direction',
                'quantity',
            ])) {
                return;
            }

            if (! $this->isNegativeAdjustment()) {
                return;
            }

            $available = PondStock::query()
                ->where('location_id', $this->integer('location_id'))
                ->where('batch_id', $this->integer('batch_id'))
                ->value('quantity');

            if ($available === null) {
                $validator->errors()->add('batch_id', 'Batch tidak tersedia pada petak tersebut.');

                return;
            }

            if ((float) $this->input('quantity') > (float) $available) {
                $validator->errors()->add('quantity', 'Stok tidak mencukupi untuk perubahan ini.');
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'adjustment_type' => mb_strtoupper(trim((string) $this->input('adjustment_type'))),
            'direction' => $this->filled('direction')
                ? mb_strtoupper(trim((string) $this->input('direction')))
                : null,
            'reason' => trim((string) $this->input('reason')),
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
            'batch_id.required' => 'Batch wajib dipilih.',
            'batch_id.exists' => 'Batch tidak tersedia pada petak tersebut.',
            'adjustment_type.required' => 'Jenis perubahan wajib dipilih.',
            'adjustment_type.in' => 'Jenis perubahan tidak valid.',
            'direction.required_if' => 'Arah perubahan wajib dipilih untuk jenis Lainnya.',
            'direction.in' => 'Arah perubahan tidak valid.',
            'quantity.required' => 'Jumlah perubahan wajib diisi.',
            'quantity.gt' => 'Jumlah perubahan harus lebih dari 0.',
            'quantity.decimal' => 'Jumlah perubahan maksimal memiliki 3 angka desimal.',
            'quantity.max' => 'Jumlah perubahan melebihi batas penyimpanan.',
            'reason.required' => 'Alasan perubahan wajib diisi.',
            'reason.max' => 'Alasan perubahan maksimal 2.000 karakter.',
            'created_by.prohibited' => 'Pengguna pencatat ditentukan oleh sistem.',
            'transaction_number.prohibited' => 'Nomor transaksi ditentukan oleh sistem.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'transaction_date' => 'Tanggal Transaksi',
            'location_id' => 'Petak',
            'batch_id' => 'Batch',
            'adjustment_type' => 'Jenis Perubahan',
            'direction' => 'Arah Perubahan',
            'quantity' => 'Jumlah Perubahan',
            'reason' => 'Alasan',
            'created_by' => 'Dicatat Oleh',
            'transaction_number' => 'No. Transaksi',
            'quantity_before' => 'Jumlah Sebelum',
            'quantity_change' => 'Perubahan',
            'quantity_after' => 'Jumlah Sesudah',
        ];
    }

    private function isNegativeAdjustment(): bool
    {
        $type = (string) $this->input('adjustment_type');

        return in_array($type, ['MORTALITY', 'LOSS', 'CORRECTION_OUT'], true)
            || ($type === 'OTHER' && $this->input('direction') === 'OUT');
    }
}
