<?php

namespace App\Http\Requests;

use App\Models\VendorType;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FeedItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canAccess('feed-items.manage') === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'code' => ['prohibited'],
            'name' => ['required', 'string', 'max:255'],
            'item_type' => ['required', Rule::in(['FEED', 'NUTRITION', 'MEDICINE', 'OTHER'])],
            'default_vendor_id' => [
                'nullable',
                'integer',
                Rule::exists('vendors', 'id')->where(
                    fn (Builder $query) => $query
                        ->where('status', 'ACTIVE')
                        ->whereIn('vendor_type_id', VendorType::query()
                            ->select('id')
                            ->whereIn('semantic_type', [VendorType::SEMANTIC_FEED, VendorType::SEMANTIC_MULTIPLE])),
                ),
            ],
            'unit' => ['required', 'string', 'max:50'],
            'default_price' => ['required', 'numeric', 'min:0', 'max:9999999999999999.99'],
            'description' => ['nullable', 'string', 'max:16000'],
            'status' => ['prohibited'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'item_type' => mb_strtoupper(trim((string) $this->input('item_type'))),
            'default_vendor_id' => $this->filled('default_vendor_id') ? $this->input('default_vendor_id') : null,
            'unit' => trim((string) $this->input('unit')),
            'description' => $this->filled('description') ? trim((string) $this->input('description')) : null,
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.prohibited' => 'Kode kebutuhan dibuat otomatis oleh sistem dan tidak dapat diubah.',
            'name.required' => 'Nama pakan, nutrisi, atau obat wajib diisi.',
            'item_type.required' => 'Jenis kebutuhan wajib dipilih.',
            'item_type.in' => 'Jenis kebutuhan tidak valid.',
            'default_vendor_id.exists' => 'Vendor utama harus aktif dan berjenis Vendor Pakan atau Vendor Beragam.',
            'unit.required' => 'Satuan wajib diisi.',
            'default_price.required' => 'Harga default wajib diisi.',
            'default_price.numeric' => 'Harga default harus berupa angka.',
            'default_price.min' => 'Harga default tidak boleh negatif.',
            'status.prohibited' => 'Status hanya dapat diubah melalui aksi status kebutuhan.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'code' => 'Kode',
            'name' => 'Nama',
            'item_type' => 'Jenis Kebutuhan',
            'default_vendor_id' => 'Vendor Utama',
            'unit' => 'Satuan',
            'default_price' => 'Harga Default',
            'description' => 'Deskripsi',
            'status' => 'Status',
        ];
    }
}
