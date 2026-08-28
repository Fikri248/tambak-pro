<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CommodityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canAccess('commodities.manage') === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'code' => ['prohibited'],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
            'unit' => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:16000'],
            'status' => ['prohibited'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'category' => $this->filled('category') ? trim((string) $this->input('category')) : null,
            'unit' => trim((string) $this->input('unit', 'ekor')),
            'description' => $this->filled('description') ? trim((string) $this->input('description')) : null,
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.prohibited' => 'Kode komoditas dibuat otomatis oleh sistem dan tidak dapat diubah.',
            'name.required' => 'Nama komoditas wajib diisi.',
            'unit.required' => 'Satuan komoditas wajib diisi.',
            'status.prohibited' => 'Status hanya dapat diubah melalui aksi status komoditas.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'code' => 'Kode Komoditas',
            'name' => 'Nama Komoditas',
            'category' => 'Kategori',
            'unit' => 'Satuan',
            'description' => 'Deskripsi',
            'status' => 'Status',
        ];
    }
}
