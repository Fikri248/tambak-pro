<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DashboardAnalyticsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canAccess('dashboard.view') === true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'period' => ['required', Rule::in(['30d', '3m', '6m', '12m'])],
            'tambak_id' => [
                'nullable',
                'integer',
                Rule::exists('locations', 'id')->where('location_type', 'TAMBAK'),
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'period' => $this->filled('period') ? mb_strtolower(trim((string) $this->input('period'))) : '30d',
            'tambak_id' => $this->filled('tambak_id') ? $this->input('tambak_id') : null,
        ]);
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'period.in' => 'Periode analitik tidak valid.',
            'tambak_id.exists' => 'Tambak yang dipilih tidak tersedia.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'period' => 'Periode',
            'tambak_id' => 'Tambak',
        ];
    }
}
