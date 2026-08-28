<?php

namespace App\Http\Requests;

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
            'vendor_type' => ['required', Rule::in(['SEED', 'FEED', 'SERVICE', 'MULTIPLE', 'OTHER'])],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email:rfc', 'max:255'],
            'address' => ['nullable', 'string', 'max:16000'],
            'description' => ['nullable', 'string', 'max:16000'],
            'status' => ['prohibited'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'phone' => $this->filled('phone') ? trim((string) $this->input('phone')) : null,
            'email' => $this->filled('email') ? mb_strtolower(trim((string) $this->input('email'))) : null,
            'address' => $this->filled('address') ? trim((string) $this->input('address')) : null,
            'description' => $this->filled('description') ? trim((string) $this->input('description')) : null,
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
            'vendor_type.required' => 'Jenis Vendor wajib dipilih.',
            'vendor_type.in' => 'Jenis Vendor tidak valid.',
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
            'vendor_type' => 'Jenis Vendor',
            'phone' => 'Telepon',
            'email' => 'Email',
            'address' => 'Alamat',
            'description' => 'Deskripsi',
            'status' => 'Status',
        ];
    }
}
