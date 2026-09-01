<?php

namespace App\Http\Requests;

use App\Models\VendorType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VendorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canAccess('vendors.manage') === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'code' => ['prohibited'],
            'name' => ['required', 'string', 'max:255'],
            'vendor_type_id' => ['required', 'integer', Rule::exists('vendor_types', 'id')],
            'vendor_type' => ['exclude'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email:rfc', 'max:255'],
            'address' => ['nullable', 'string', 'max:16000'],
            'description' => ['nullable', 'string', 'max:16000'],
            'status' => ['prohibited'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $legacyTypeId = null;

        if (! $this->filled('vendor_type_id') && $this->filled('vendor_type')) {
            $legacyTypeId = VendorType::query()
                ->where('code', mb_strtoupper(trim((string) $this->input('vendor_type'))))
                ->value('id');
        }

        $this->merge([
            'name' => trim((string) $this->input('name')),
            'phone' => $this->filled('phone') ? trim((string) $this->input('phone')) : null,
            'email' => $this->filled('email') ? mb_strtolower(trim((string) $this->input('email'))) : null,
            'address' => $this->filled('address') ? trim((string) $this->input('address')) : null,
            'description' => $this->filled('description') ? trim((string) $this->input('description')) : null,
            'vendor_type_id' => $this->filled('vendor_type_id') ? $this->input('vendor_type_id') : $legacyTypeId,
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.prohibited' => 'Kode Vendor dibuat otomatis oleh sistem dan tidak dapat diubah.',
            'name.required' => 'Nama Vendor wajib diisi.',
            'vendor_type_id.required' => 'Jenis Vendor wajib dipilih.',
            'vendor_type_id.exists' => 'Jenis Vendor tidak valid.',
            'email.email' => 'Format email Vendor tidak valid.',
            'status.prohibited' => 'Status hanya dapat diubah melalui aksi status Vendor.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'code' => 'Kode Vendor',
            'name' => 'Nama Vendor',
            'vendor_type_id' => 'Jenis Vendor',
            'phone' => 'Telepon',
            'email' => 'Email',
            'address' => 'Alamat',
            'description' => 'Deskripsi',
            'status' => 'Status',
        ];
    }
}
