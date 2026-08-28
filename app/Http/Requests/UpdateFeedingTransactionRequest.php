<?php

namespace App\Http\Requests;

use Illuminate\Validation\Validator;

class UpdateFeedingTransactionRequest extends FeedingTransactionRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canAccess('feeding.update') === true;
    }

    public function withValidator(Validator $validator): void
    {
        // Snapshot validation and recalculation depend on whether the stock
        // context changed, so they run under locks in the update transaction.
    }
}
