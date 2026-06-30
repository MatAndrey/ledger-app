<?php

namespace App\Observers;

use App\Models\Transaction;
use Illuminate\Support\Facades\Cache;

class TransactionObserver
{
    public function deleting(Transaction $transaction): void
    {
        $this->clearCacheForTransaction($transaction);
    }

    private function clearCacheForTransaction(Transaction $transaction): void
    {
        $accountIds = $transaction->journalEntries()
            ->pluck('account_id')
            ->unique();

        foreach ($accountIds as $accountId) {
            Cache::forget("account_{$accountId}_balance");
        }
    }
}
