<?php

namespace App\Enums;

enum AccountTypes: string
{
    case Asset = 'asset';
    case Liability = 'liability';
    case Equity = 'equity';
    case Revenue = 'revenue';
    case Expense = 'expense';

    public function toString(): ?string {
        return match ($this) {
            self::Asset => 'Актив',
            self::Liability => 'Обязательство',
            self::Equity => 'Капитал',
            self::Revenue => 'Доход',
            self::Expense => 'Расход',
        };
    }
}
