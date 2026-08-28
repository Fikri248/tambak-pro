<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStockMovementRequest extends FormRequest
{
    private const MAX_QUANTITY = '999999999999999.999';

    public function authorize(): bool
    {
        return $this->user()?->canAccess('movements.update') === true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'transaction_date' => ['required', 'date'],
            'from_location_id' => ['required', 'integer', 'exists:locations,id'],
            'to_location_id' => ['required', 'integer', 'different:from_location_id', 'exists:locations,id'],
            'batch_id' => ['required', 'integer', 'exists:commodity_batches,id'],
            'quantity' => ['required', 'numeric', 'gt:0', 'decimal:0,3', 'max:'.self::MAX_QUANTITY],
            'notes' => ['nullable', 'string', 'max:16000'],
            'created_by' => ['prohibited'],
            'transaction_number' => ['prohibited'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'notes' => $this->filled('notes') ? trim((string) $this->input('notes')) : null,
        ]);
    }

    /** @return array<string, string> */
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
            'batch_id.exists' => 'Batch tidak tersedia.',
            'quantity.required' => 'Jumlah yang dipindahkan wajib diisi.',
            'quantity.gt' => 'Jumlah yang dipindahkan harus lebih dari 0.',
            'quantity.decimal' => 'Jumlah yang dipindahkan maksimal memiliki 3 angka desimal.',
            'quantity.max' => 'Jumlah yang dipindahkan melebihi batas penyimpanan.',
            'created_by.prohibited' => 'Pengguna pencatat tidak dapat diubah.',
            'transaction_number.prohibited' => 'Nomor transaksi tidak dapat diubah.',
        ];
    }
}
