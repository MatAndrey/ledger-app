<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\TransactionService;
use App\Models\Transaction;
use App\Http\Requests\StoreTransactionRequest;
use Illuminate\Http\Response;

class TransactionController extends Controller
{
    public function __construct(protected TransactionService $transactionService) {}

    /** @throws \Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException */
    public function store(StoreTransactionRequest $request): JsonResponse
    {
        $transaction = $this->transactionService->createTransaction($request->validated());

        return response()->json([
            'message' => 'Transaction created successfully',
            'data' => $transaction->load('journalEntries.account'),
        ], 201);
    }

    /** @throws \Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException */
    public function show(Transaction $transaction): JsonResponse  {
        return response()->json($transaction->load('journalEntries'), 200);
    }

    /** @throws \Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException */
    public function index(): JsonResponse  {
        $transactions = $this->transactionService->getAllTransactions();
        return response()->json($transactions, 200);
    }

    /** @throws \Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException */
    public function update(Transaction $transaction, StoreTransactionRequest $request): JsonResponse  {
        if($transaction->is_posted) {
            return response()->json([
                'message' => 'Cannot update posted transaction'
            ], 403);
        }
        $transaction = $this->transactionService->updateTransaction($transaction, $request->validated());
        return response()->json($transaction, 200);
    }

    /** @throws \Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException */
    public function destroy(Transaction $transaction): Response | JsonResponse  {
        if($transaction->is_posted) {
            return response()->json([
                'message' => 'Cannot delete posted transaction'
            ], 403);
        }
        $transaction = $this->transactionService->deleteTransaction($transaction);
        return response(null, 204);
    }
}
