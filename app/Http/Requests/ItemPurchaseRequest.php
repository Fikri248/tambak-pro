<?php

namespace App\Http\Requests;

use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ItemPurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        $ability = $this->isMethod('POST') ? 'item-purchases.create' : 'item-purchases.update';

        return $this->user()?->canAccess($ability) === true;
    }

    public function rules(): array
    {
        $current = $this->route('itemPurchase');

        return [
            'transaction_number' => ['prohibited'],
            'transaction_date' => ['required', 'date'],
            'feed_item_id' => ['required', 'integer', Rule::exists('feed_items', 'id')->where(
                fn (Builder $query) => $query->where('status', 'ACTIVE')->when($current, fn (Builder $query) => $query->orWhere('id', $current->feed_item_id)),
            )],
            'vendor_id' => ['required', 'integer', Rule::exists('vendors', 'id')->where(
                fn (Builder $query) => $query->where('status', 'ACTIVE')->when($current, fn (Builder $query) => $query->orWhere('id', $current->vendor_id)),
            )],
            'quantity' => ['required', 'numeric', 'gt:0', 'decimal:0,3', 'max:999999999999999.999'],
            'unit_cost' => ['required', 'numeric', 'min:0', 'decimal:0,4', 'max:99999999999999.9999'],
            'total_cost' => ['exclude'],
            'created_by' => ['prohibited'],
            'notes' => ['nullable', 'string', 'max:16000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['notes' => $this->filled('notes') ? trim((string) $this->input('notes')) : null]);
    }

    public function messages(): array
    {
        return [
            'transaction_number.prohibited' => 'Nomor transaksi dibuat otomatis dan tidak dapat diubah.',
            'transaction_date.required' => 'Tanggal Pembelian wajib diisi.',
            'feed_item_id.exists' => 'Barang/Item yang dipilih tidak aktif.',
            'vendor_id.exists' => 'Vendor yang dipilih tidak aktif.',
            'quantity.gt' => 'Jumlah harus lebih dari 0.',
            'unit_cost.min' => 'Harga Satuan tidak boleh negatif.',
        ];
    }
}
