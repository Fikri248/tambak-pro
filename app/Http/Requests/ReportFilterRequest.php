<?php

namespace App\Http\Requests;

use App\Models\VendorType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReportFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canAccess('reports.view') === true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'area_id' => ['nullable', 'integer', 'exists:locations,id'],
            'tambak_id' => ['nullable', 'integer', 'exists:locations,id'],
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'commodity_id' => ['nullable', 'integer', 'exists:commodities,id'],
            'batch_id' => ['nullable', 'integer', 'exists:commodity_batches,id'],
            'vendor_id' => ['nullable', 'integer', 'exists:vendors,id'],
            'feed_item_id' => ['nullable', 'integer', 'exists:feed_items,id'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'type' => $this->routeIs('reports.vendors*')
                ? ['nullable', 'integer', 'exists:vendor_types,id']
                : ['nullable', Rule::in([
                    'MORTALITY', 'LOSS', 'CORRECTION_IN', 'CORRECTION_OUT', 'OTHER',
                    'FEED', 'NUTRITION', 'MEDICINE',
                ])],
            'status' => ['nullable', Rule::in(['ACTIVE', 'INACTIVE'])],
            'category' => ['nullable', 'string', 'max:100'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'format' => ['nullable', Rule::in(['csv', 'xlsx'])],
        ];
    }

    protected function prepareForValidation(): void
    {
        $nullableIds = ['area_id', 'tambak_id', 'location_id', 'commodity_id', 'batch_id', 'vendor_id', 'feed_item_id', 'user_id'];
        $normalized = [];

        foreach ($nullableIds as $field) {
            $normalized[$field] = $this->filled($field) ? $this->input($field) : null;
        }

        $type = $this->filled('type') ? mb_strtoupper(trim((string) $this->input('type'))) : null;

        if ($type !== null && $this->routeIs('reports.vendors*') && ! ctype_digit($type)) {
            $type = (string) (VendorType::query()->where('code', $type)->value('id') ?? '');
        }

        $this->merge($normalized + [
            'search' => $this->filled('search') ? trim((string) $this->input('search')) : null,
            'type' => $type,
            'status' => $this->filled('status') ? mb_strtoupper(trim((string) $this->input('status'))) : null,
            'category' => $this->filled('category') ? trim((string) $this->input('category')) : null,
            'date_from' => $this->filled('date_from') ? $this->input('date_from') : null,
            'date_to' => $this->filled('date_to') ? $this->input('date_to') : null,
            'format' => $this->filled('format') ? mb_strtolower(trim((string) $this->input('format'))) : null,
        ]);
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'type.in' => 'Jenis filter tidak valid.',
            'status.in' => 'Status filter tidak valid.',
            'date_from.date' => 'Tanggal mulai tidak valid.',
            'date_to.date' => 'Tanggal selesai tidak valid.',
            'date_to.after_or_equal' => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
            'format.in' => 'Format ekspor harus CSV atau Excel.',
            '*.exists' => 'Pilihan filter tidak tersedia.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'search' => 'Pencarian',
            'area_id' => 'Area',
            'tambak_id' => 'Tambak',
            'location_id' => 'Petak',
            'commodity_id' => 'Komoditas',
            'batch_id' => 'Batch',
            'vendor_id' => 'Vendor',
            'feed_item_id' => 'Pakan / Nutrisi / Obat',
            'user_id' => 'Dicatat Oleh',
            'type' => 'Jenis',
            'status' => 'Status',
            'category' => 'Kategori',
            'date_from' => 'Tanggal Mulai',
            'date_to' => 'Tanggal Selesai',
            'format' => 'Format Export',
        ];
    }
}
