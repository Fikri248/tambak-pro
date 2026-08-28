<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransactionHistoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canAccess('history.view') === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', Rule::in(['STOCKING', 'MOVEMENT', 'ADJUSTMENT', 'FEEDING'])],
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'commodity_id' => ['nullable', 'integer', 'exists:commodities,id'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'search' => $this->filled('search') ? trim((string) $this->input('search')) : null,
            'type' => $this->filled('type') ? mb_strtoupper(trim((string) $this->input('type'))) : null,
            'location_id' => $this->filled('location_id') ? $this->input('location_id') : null,
            'commodity_id' => $this->filled('commodity_id') ? $this->input('commodity_id') : null,
            'user_id' => $this->filled('user_id') ? $this->input('user_id') : null,
            'date_from' => $this->filled('date_from') ? $this->input('date_from') : null,
            'date_to' => $this->filled('date_to') ? $this->input('date_to') : null,
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'type.in' => 'Tipe transaksi tidak valid.',
            'location_id.exists' => 'Petak yang dipilih tidak tersedia.',
            'commodity_id.exists' => 'Komoditas yang dipilih tidak tersedia.',
            'user_id.exists' => 'Pengguna yang dipilih tidak tersedia.',
            'date_from.date' => 'Tanggal mulai tidak valid.',
            'date_to.date' => 'Tanggal selesai tidak valid.',
            'date_to.after_or_equal' => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'search' => 'Pencarian',
            'type' => 'Tipe Transaksi',
            'location_id' => 'Petak',
            'commodity_id' => 'Komoditas',
            'user_id' => 'Dicatat Oleh',
            'date_from' => 'Tanggal Mulai',
            'date_to' => 'Tanggal Selesai',
        ];
    }
}
