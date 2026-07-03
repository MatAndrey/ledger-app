<?php

namespace App\Services;

use App\Repositories\TransactionRepository;
use App\Models\Transaction;
use Illuminate\Validation\ValidationException;

class TransactionService
{
    public function __construct(protected TransactionRepository $transactionRepository)
    {
    }

    public function createTransaction(array $data): Transaction
    {
        $totalDebit = collect($data['journalEntries'])->where('type', 'debit')->sum('amount');
        $totalCredit = collect($data['journalEntries'])->where('type', 'credit')->sum('amount');
        if (abs($totalDebit - $totalCredit) > 0.01) {
            throw new ValidationException("Сумма дебета ($totalDebit) должна равняться сумме кредита ($totalCredit)");
        }
        return $this->transactionRepository->createTransaction($data);
    }
}