<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Cast;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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
    use HasFactory;

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
