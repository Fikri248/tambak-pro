<?php

namespace App\Http\Requests;

use App\Models\VendorType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class VendorTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canAccess('vendors.manage') === true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return $this->isMethod('DELETE') ? [] : [
            'vendor_type_name' => ['required', 'string', 'max:255'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->isMethod('DELETE')) {
            $fallback = $this->input('vendor_type_names.'.$this->routeTypeId());
            $this->merge([
                'vendor_type_name' => trim((string) ($this->input('vendor_type_name') ?? $fallback)),
            ]);
        }
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($this->isMethod('DELETE') || $validator->errors()->has('vendor_type_name')) {
                return;
            }

            $query = VendorType::query()
                ->where('normalized_name', VendorType::normalizeName((string) $this->input('vendor_type_name')));

            if ($this->isMethod('PATCH')) {
                $query->whereKeyNot($this->routeTypeId());
            }

            if ($query->exists()) {
                $validator->errors()->add('vendor_type_name', 'Jenis Vendor sudah tersedia.');
            }
        }];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'vendor_type_name.required' => 'Nama Jenis Vendor wajib diisi.',
            'vendor_type_name.max' => 'Nama Jenis Vendor maksimal 255 karakter.',
        ];
    }

    private function routeTypeId(): int
    {
        $vendorType = $this->route('vendorType');

        return $vendorType instanceof VendorType ? (int) $vendorType->getKey() : (int) $vendorType;
    }
}
