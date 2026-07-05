<?php

namespace App\Repositories;

use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class TransactionRepository
{
    public function create(array $data): Transaction
    {
        return DB::transaction(function () use ($data) {
            $transaction = Transaction::create([
                'date' => $data['date'],
                'description' => $data['description'],
                'created_at' => now(),
                'is_posted' => $data['is_posted'],
            ]);

            foreach ($data['journal_entries'] as $entry) {
                $journalEntry = $transaction->journalEntries()->create([
                    'account_id' => $entry['account_id'],
                    'amount' => $entry['amount'],
                    'type' => $entry['type'],
                ]);
            }

            return $transaction;
        });
    }

    public function update(Transaction $transaction, array $data): Transaction {
        return DB::transaction(function () use ($transaction, $data) {
            $transaction->date = $data['date'];
            $transaction->description = $data['description'];
            $transaction->is_posted = $data['is_posted'];

            $oldEntries = $transaction->journalEntries;
            foreach ($oldEntries as $entry) {
                $entry->delete();
            }

            foreach ($data['journal_entries'] as $entry) {
                $transaction->journalEntries()->create([
                    'account_id' => $entry['account_id'],
                    'amount' => $entry['amount'],
                    'type' => $entry['type'],
                ]);
            }

            $transaction->save();

            return $transaction->fresh()->load('journalEntries');
        });
    }

    public function delete(Transaction $transaction) {
        $transaction->delete();
    }

    public function getAll() {
        return Transaction::All();
    }
}