<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Enums\AccountTypes;
use Illuminate\Database\Seeder;

class AccountSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            ['name' => 'Расчетный счет', 'code' => 1, 'type' => AccountTypes::Asset, 'is_active' => true],
            ['name' => 'Касса', 'code' => 2, 'type' => AccountTypes::Asset, 'is_active' => true],
            ['name' => 'Товары', 'code' => 3, 'type' => AccountTypes::Asset, 'is_active' => true],
            ['name' => 'Уставный капитал', 'code' => 4, 'type' => AccountTypes::Equity, 'is_active' => true],
            ['name' => 'Расходы на аренду', 'code' => 5, 'type' => AccountTypes::Expense, 'is_active' => true],
        ];

        foreach ($accounts as $data) {
            Account::create($data);
        }
    }
}