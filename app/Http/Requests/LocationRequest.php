<?php

namespace App\Http\Requests;

use App\Models\Location;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class LocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canAccess('locations.manage') === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'code' => ['prohibited'],
            'name' => ['required', 'string', 'max:255'],
            'location_type' => ['required', Rule::in(['AREA', 'TAMBAK', 'PETAK', 'OTHER'])],
            'parent_id' => ['nullable', 'integer', 'exists:locations,id'],
            'address' => ['nullable', 'string', 'max:16000'],
            'description' => ['nullable', 'string', 'max:16000'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['location_type', 'parent_id'])) {
                    return;
                }

                $location = $this->route('location');
                $parent = $this->filled('parent_id')
                    ? Location::query()->find($this->integer('parent_id'))
                    : null;
                $type = $this->string('location_type')->toString();

                if ($type === 'AREA' && $parent) {
                    $validator->errors()->add('parent_id', 'Area tidak dapat memiliki induk lokasi.');
                }

                if ($type === 'TAMBAK' && $parent && $parent->location_type !== 'AREA') {
                    $validator->errors()->add('parent_id', 'Induk Tambak harus berupa Area.');
                }

                if ($type === 'PETAK' && $parent && $parent->location_type !== 'TAMBAK') {
                    $validator->errors()->add('parent_id', 'Induk Petak harus berupa Tambak.');
                }

                if ($location instanceof Location && $parent && $location->wouldCreateCycleWith($parent)) {
                    $validator->errors()->add('parent_id', 'Induk lokasi tidak boleh membentuk siklus hierarki.');
                }
            },
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'parent_id' => $this->input('parent_id') ?: null,
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
            'code.prohibited' => 'Kode lokasi dibuat otomatis oleh sistem dan tidak dapat diubah.',
            'name.required' => 'Nama lokasi wajib diisi.',
            'location_type.required' => 'Tipe lokasi wajib dipilih.',
            'location_type.in' => 'Tipe lokasi tidak valid.',
            'parent_id.exists' => 'Induk lokasi tidak ditemukan.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'code' => 'Kode Lokasi',
            'name' => 'Nama Lokasi',
            'location_type' => 'Tipe Lokasi',
            'parent_id' => 'Induk Lokasi',
            'address' => 'Alamat',
            'description' => 'Deskripsi',
        ];
    }
}
