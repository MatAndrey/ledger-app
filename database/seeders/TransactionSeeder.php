<?php

namespace Database\Seeders;

use App\Models\Transaction;
use App\Models\JournalEntry;
use App\Enums\JournalEntryTypes;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class TransactionSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = \App\Models\Account::all()->keyBy('id');

        $accountCash = $accounts->where('code', 2)->first(); // Касса
        $accountBank = $accounts->where('code', 1)->first(); // Расчетный счет
        $accountGoods = $accounts->where('code', 3)->first(); // Товары
        $accountCapital = $accounts->where('code', 4)->first(); // Уставный капитал
        $accountRent = $accounts->where('code', 5)->first(); // Расходы на аренду

        if (!$accountCash || !$accountBank || !$accountGoods || !$accountCapital || !$accountRent) {
            throw new \Exception('Accounts not found, run AccountSeeder first.');
        }

        for ($i = 1; $i <= 20; $i++) {
            $date = Carbon::now()->subDays(rand(1, 60));

            $transaction = Transaction::create([
                'date' => $date,
                'description' => "Транзакция #$i",
                'created_at' => $date,
                'is_posted' => true
            ]);

            switch (rand(1, 5)) {
                case 1: // Дебет расчетного счета, кредит уставного капитала
                    $amount = rand(1000, 10000);
                    JournalEntry::create([
                        'transaction_id' => $transaction->id,
                        'account_id' => $accountBank->id,
                        'amount' => $amount,
                        'type' => JournalEntryTypes::Debit,
                    ]);
                    JournalEntry::create([
                        'transaction_id' => $transaction->id,
                        'account_id' => $accountCapital->id,
                        'amount' => $amount,
                        'type' => JournalEntryTypes::Credit,
                    ]);
                    break;

                case 2: // Оплата аренды с расчетного счета
                    $amount = rand(500, 3000);
                    JournalEntry::create([
                        'transaction_id' => $transaction->id,
                        'account_id' => $accountRent->id,
                        'amount' => $amount,
                        'type' => JournalEntryTypes::Debit,
                    ]);
                    JournalEntry::create([
                        'transaction_id' => $transaction->id,
                        'account_id' => $accountBank->id,
                        'amount' => $amount,
                        'type' => JournalEntryTypes::Credit,
                    ]);
                    break;

                case 3: // Поступление товаров за наличные
                    $amount = rand(200, 5000);
                    JournalEntry::create([
                        'transaction_id' => $transaction->id,
                        'account_id' => $accountGoods->id,
                        'amount' => $amount,
                        'type' => JournalEntryTypes::Debit,
                    ]);
                    JournalEntry::create([
                        'transaction_id' => $transaction->id,
                        'account_id' => $accountCash->id,
                        'amount' => $amount,
                        'type' => JournalEntryTypes::Credit,
                    ]);
                    break;

                case 4: // Внесение наличных на расчетный счет
                    $amount = rand(1000, 7000);
                    JournalEntry::create([
                        'transaction_id' => $transaction->id,
                        'account_id' => $accountBank->id,
                        'amount' => $amount,
                        'type' => JournalEntryTypes::Debit,
                    ]);
                    JournalEntry::create([
                        'transaction_id' => $transaction->id,
                        'account_id' => $accountCash->id,
                        'amount' => $amount,
                        'type' => JournalEntryTypes::Credit,
                    ]);
                    break;

                case 5: // Продажа товаров (поступление на расчетный счет)
                    $amount = rand(3000, 15000);
                    JournalEntry::create([
                        'transaction_id' => $transaction->id,
                        'account_id' => $accountBank->id,
                        'amount' => $amount,
                        'type' => JournalEntryTypes::Debit,
                    ]);
                    JournalEntry::create([
                        'transaction_id' => $transaction->id,
                        'account_id' => $accountGoods->id,
                        'amount' => $amount,
                        'type' => JournalEntryTypes::Credit,
                    ]);
                    break;
            }
        }
    }
}