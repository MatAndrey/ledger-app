<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\TransactionService;
use App\Http\Requests\StoreTransactionRequest;

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
}
