<?php

namespace App\Services;

use App\Repositories\TransactionRepository;
use App\Models\Transaction;

class TransactionService
{
    public function __construct(protected TransactionRepository $transactionRepository)
    {
    }

    public function createTransaction(array $data): Transaction
    {
        return $this->transactionRepository->createTransaction($data);
    }
}