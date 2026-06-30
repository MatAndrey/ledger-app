<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Cast;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Enums\AccountTypes;


#[Fillable(['name', 'code', 'type', 'is_active'])]
#[Cast('type', AccountTypes::class)]
#[Cast('is_active', 'boolean')]
#[Cast('code', 'integer')]
class Account extends Model
{
    public function journalEntries(): HasMany
    {
        return $this->hasMany(JournalEntry::class);
    }

    public function transactions()
    {
        return $this->hasManyThrough(
            Transaction::class,
            JournalEntry::class
        );
    }

    public function getBalanceAttribute(): float
    {
        $cacheKey = "account_{$this->id}_balance";
        
        return Cache::remember($cacheKey, 60, function () {
            $query = $this->journalEntries();

            $debitSum = (clone $query)->where('type', 'debit')->sum('amount');
            $creditSum = (clone $query)->where('type', 'credit')->sum('amount');

            if (in_array($this->type, [AccountTypes::Asset, AccountTypes::Expense])) {
                return $debitSum - $creditSum;
            }
            return $creditSum - $debitSum;
        });
    }
}
