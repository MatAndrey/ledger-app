<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Account;
use App\Services\LedgerService;
use Carbon\Carbon;


class AccountController extends Controller
{
    public function index(): JsonResponse {
        return response()->json(Account::all());
    }

    public function balance(Account $account, Request $request): JsonResponse {
        $balance = $account->balance;

        return response()->json([
            'account_id' => $account->id,
            'balance' => $balance
        ]);
    }

    public function trialBalance(LedgerService $ledgerService, Request $request)
    {
        $start = $request->input('start', Carbon::now()->startOfMonth()->toDateString());
        $end = $request->input('end', Carbon::now()->endOfMonth()->toDateString());
        $format = $request->input('format');

        if ($format === 'csv' || $format === 'xlsx') {
            $path= $ledgerService->generateTrialBalanceFile($start, $end, $format);
            $filename = 'trial_balance_' . $start . '_' . $end . '.' . ($format === 'csv' ? 'csv' : 'xlsx');
            return response()->download($path, $filename)->deleteFileAfterSend(true);
        }

        $report = $ledgerService->generateTrialBalance(Carbon::parse($start), Carbon::parse($end));

        return response()->json($report);
    }
}
