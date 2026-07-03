<?php

namespace App\Services;

use App\Repositories\TransactionRepository;
use App\Models\Transaction;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Collection;

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
            throw ValidationException::withMessages(["Сумма дебета ($totalDebit) должна равняться сумме кредита ($totalCredit)"]);
        }
        return $this->transactionRepository->create($data);
    }

    public function updateTransaction(Transaction $transaction, $requestData): Transaction {
        $totalDebit = collect($requestData['journalEntries'])->where('type', 'debit')->sum('amount');
        $totalCredit = collect($requestData['journalEntries'])->where('type', 'credit')->sum('amount');
        if (abs($totalDebit - $totalCredit) > 0.01) {
            throw ValidationException::withMessages(["Сумма дебета ($totalDebit) должна равняться сумме кредита ($totalCredit)"]);
        }
        return $this->transactionRepository->update($transaction, $requestData);
    }

    public function deleteTransaction(Transaction $transaction) {
        return $this->transactionRepository->delete($transaction);
    }

    public function getAllTransactions(): Collection {
        return $this->transactionRepository->getAll();
    }
}