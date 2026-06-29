<?php

namespace App\Enums;

enum JournalEntryTypes: string
{
    case Debit = 'debit';
    case Credit = 'credit';

    public function toString(): ?string {
        return match ($this) {
            self::Debit => 'Дебет',
            self::Credit => 'Кредит'
        };
    }
}
