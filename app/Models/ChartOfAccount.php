<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChartOfAccount extends Model
{
    protected $fillable = [
        'number_code',
        'description_id',
        'account_type_id',
        'financial_statement_id',
        'status',
    ];

    public function description(): BelongsTo
    {
        return $this->belongsTo(AccountDescription::class, 'description_id');
    }

    public function accountType(): BelongsTo
    {
        return $this->belongsTo(AccountType::class);
    }

    public function financialStatement(): BelongsTo
    {
        return $this->belongsTo(FinancialStatement::class);
    }
}
