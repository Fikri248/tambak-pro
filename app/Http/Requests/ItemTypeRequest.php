<?php

namespace App\Http\Requests;

use App\Models\ItemType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ItemTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canAccess('feed-items.manage') === true;
    }

    public function rules(): array
    {
        return [
            'item_type_name' => [
                Rule::requiredIf(fn (): bool => ! $this->isMethod('DELETE')),
                'nullable',
                'string',
                'max:255',
            ],
            'code' => ['prohibited'],
            'semantic_type' => ['prohibited'],
            'is_system' => ['prohibited'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('item_type_name')) {
            $this->merge(['item_type_name' => trim((string) $this->input('item_type_name'))]);
        }
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->isMethod('DELETE') || ! $this->filled('item_type_name')) {
                return;
            }

            $query = ItemType::query()->where('normalized_name', ItemType::normalizeName((string) $this->input('item_type_name')));
            $current = $this->route('itemType');

            if ($current instanceof ItemType) {
                $query->whereKeyNot($current->id);
            }

            if ($query->exists()) {
                $validator->errors()->add('item_type_name', 'Jenis Barang/Item sudah tersedia.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'item_type_name.required' => 'Nama Jenis Barang/Item wajib diisi.',
            'item_type_name.unique' => 'Jenis Barang/Item sudah tersedia.',
            'code.prohibited' => 'Kode Jenis Barang/Item dibuat otomatis dan tidak dapat diubah.',
            'semantic_type.prohibited' => 'Semantic Jenis Barang/Item tidak dapat diubah.',
            'is_system.prohibited' => 'Status sistem Jenis Barang/Item tidak dapat diubah.',
        ];
    }
}
