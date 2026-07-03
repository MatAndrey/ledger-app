<?php

namespace App\Repositories;

use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class TransactionRepository
{
    public function createTransaction(array $data): Transaction
    {
        return DB::transaction(function () use ($data) {
            $transaction = Transaction::create([
                'date' => $data['date'],
                'description' => $data['description'],
                'created_at' => now(),
                'is_posted' => true,
            ]);

            foreach ($data['journalEntries'] as $entry) {
                $journalEntry = $transaction->journalEntries()->create([
                    'account_id' => $entry['account_id'],
                    'amount' => $entry['amount'],
                    'type' => $entry['type'],
                ]);
            }

            return $transaction;
        });
    }
}