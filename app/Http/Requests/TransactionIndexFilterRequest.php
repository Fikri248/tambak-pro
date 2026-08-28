<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransactionIndexFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'date_from.date' => 'Tanggal mulai tidak valid.',
            'date_to.date' => 'Tanggal selesai tidak valid.',
            'date_to.after_or_equal' => 'Tanggal selesai tidak boleh lebih awal dari tanggal mulai.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'date_from' => 'Tanggal Mulai',
            'date_to' => 'Tanggal Selesai',
        ];
    }
}
