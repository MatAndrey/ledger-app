<?php

namespace App\Observers;

use App\Models\JournalEntry;
use Illuminate\Support\Facades\Cache;

class JournalEntryObserver
{
    public function created(JournalEntry $journalEntry): void
    {
        $this->clearCache($journalEntry->account_id);
    }

    public function updated(JournalEntry $journalEntry): void
    {
        $this->clearCache($journalEntry->account_id);

        if ($journalEntry->isDirty('account_id')) {
            $oldAccountId = $journalEntry->getOriginal('account_id');
            $this->clearCache($oldAccountId);
        }
    }

    private function clearCache(int $accountId): void
    {
        Cache::forget("account_{$accountId}_balance");
    }
}
