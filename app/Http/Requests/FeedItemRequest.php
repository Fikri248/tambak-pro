<?php

namespace App\Http\Requests;

use App\Models\ItemType;
use App\Models\Vendor;
use App\Models\VendorType;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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
            'item_type_id' => ['required', 'integer', Rule::exists('item_types', 'id')],
            'default_vendor_id' => [
                'nullable',
                'integer',
                Rule::exists('vendors', 'id')->where(fn (Builder $query) => $query->where('status', 'ACTIVE')),
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
            'item_type_id' => $this->resolveItemTypeId(),
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
            'name.required' => 'Nama Barang/Item wajib diisi.',
            'item_type_id.required' => 'Jenis Barang/Item wajib dipilih.',
            'item_type_id.exists' => 'Jenis Barang/Item tidak valid.',
            'default_vendor_id.exists' => 'Vendor utama harus aktif.',
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
            'item_type_id' => 'Jenis Barang/Item',
            'default_vendor_id' => 'Vendor Utama',
            'unit' => 'Satuan',
            'default_price' => 'Harga Default',
            'description' => 'Deskripsi',
            'status' => 'Status',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! $this->filled('default_vendor_id') || ! $this->filled('item_type_id')) {
                return;
            }

            $itemType = ItemType::query()->find($this->integer('item_type_id'));

            if ($itemType?->semantic_type === ItemType::SEMANTIC_OTHER) {
                return;
            }

            $eligible = Vendor::query()
                ->whereKey($this->integer('default_vendor_id'))
                ->where('status', 'ACTIVE')
                ->whereHas('vendorType', fn ($query) => $query->whereIn('semantic_type', [VendorType::SEMANTIC_FEED, VendorType::SEMANTIC_MULTIPLE]))
                ->exists();

            if (! $eligible) {
                $validator->errors()->add('default_vendor_id', 'Vendor utama untuk jenis canonical harus berupa Vendor Pakan atau Vendor Beragam yang aktif.');
            }
        });
    }

    private function resolveItemTypeId(): mixed
    {
        if ($this->filled('item_type_id')) {
            return $this->input('item_type_id');
        }

        if ($this->filled('item_type')) {
            return ItemType::query()->where('code', mb_strtoupper(trim((string) $this->input('item_type'))))->value('id');
        }

        return null;
    }
}
