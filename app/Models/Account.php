<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Cast;
use Illuminate\Database\Eloquent\Model;


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
}
