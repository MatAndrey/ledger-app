<?php

namespace App\Enums;

enum JournalEntryTypes: string
{
    case Debit = 'debit';
    case Credit = 'credit';
}
