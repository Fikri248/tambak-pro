<?php

namespace Database\Seeders;

use App\Models\AccountDescription;
use App\Models\AccountType;
use App\Models\FinancialStatement;
use Illuminate\Database\Seeder;

class ChartOfAccountSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedOptions(AccountDescription::class, [
            'Kas',
            'Bank',
            'Piutang Usaha',
            'Persediaan',
            'Utang Usaha',
            'Modal',
            'Pendapatan Usaha',
            'Beban Operasional',
        ]);

        $this->seedOptions(AccountType::class, [
            'Aset',
            'Liabilitas',
            'Ekuitas',
            'Pendapatan',
            'Beban',
        ]);

        $this->seedOptions(FinancialStatement::class, [
            'Neraca',
            'Laba Rugi',
        ]);
    }

    /**
     * @param  class-string<AccountDescription|AccountType|FinancialStatement>  $model
     * @param  list<string>  $names
     */
    private function seedOptions(string $model, array $names): void
    {
        foreach ($names as $name) {
            $model::query()->updateOrCreate(['name' => $name], ['status' => 'ACTIVE']);
        }
    }
}
