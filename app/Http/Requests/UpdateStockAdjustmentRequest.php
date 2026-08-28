<?php

namespace App\Http\Requests;

use Illuminate\Database\Query\Builder;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateStockAdjustmentRequest extends StockAdjustmentRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canAccess('adjustments.update') === true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $rules = parent::rules();
        $rules['batch_id'] = [
            'required',
            'integer',
            Rule::exists('commodity_batches', 'id')->where(
                fn (Builder $query) => $query->whereIn('status', ['ACTIVE', 'CLOSED']),
            ),
        ];

        return $rules;
    }

    public function withValidator(Validator $validator): void
    {
        // Stock sufficiency is checked only after reversing the original effect
        // under row locks in the update transaction.
    }
}
