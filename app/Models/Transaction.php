<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Cast;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Enums\JournalEntryTypes;

#[Fillable(['date', 'description', 'created_at'])]
#[Cast('date', 'date')]
#[Cast('created_at', 'datetime')]
#[WithoutTimestamps]
class Transaction extends Model
{
    public function journalEntries(): HasMany
    {
        return $this->hasMany(JournalEntry::class);
    }

    public function getTotalAmountAttribute(): float
    {
        return (float) $this->journalEntries()
            ->where('type', \App\Enums\JournalEntryTypes::Debit)
            ->sum('amount');
    }

    public function accounts()
    {
        return $this->belongsToMany(Account::class, 'journal_entries')
                    ->withPivot('amount', 'type')
                    ->distinct();
    }

    public function getAccountsListAttribute(): string
    {
        return $this->accounts->pluck('name')->implode(', ');
    }
}
