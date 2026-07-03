<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Account;
use App\Services\AccountService;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class AccountController extends Controller
{
    /** @throws \Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException */
    public function index(): JsonResponse {
        return response()->json(Account::all());
    }

    /** @throws \Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException */
    public function balance(AccountService $accountService, Account $account, Request $request): JsonResponse {
        $validated = $request->validate(['asOf'  => 'nullable|date']);
        $asOf = isset($validated['asOf']) ? Carbon::parse($validated['asOf']) : null;
        $balance = $accountService->getBalance($account, $asOf);

        return response()->json([
            'account_id' => $account->id,
            'balance' => $balance
        ]);
    }

    /** @throws \Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException */
    public function trialBalance(AccountService $accountService, Request $request)
    {
         $validated = $request->validate([
            'start'  => 'nullable|date',
            'end'    => 'nullable|date'
        ]);

        $start = $validated['start'] ?? Carbon::now()->startOfMonth()->toDateString();
        $end   = $validated['end'] ?? Carbon::now()->endOfMonth()->toDateString();

        if (isset($validated['start']) && isset($validated['end'])) {
            $startCarbon = Carbon::parse($start);
            $endCarbon = Carbon::parse($end);
            if ($startCarbon->greaterThan($endCarbon)) {
                throw ValidationException::withMessages([
                    'end' => 'Дата окончания периода должна быть позже или равна дате начала.',
                ]);
            }
        }

        $report = $accountService->generateTrialBalance(Carbon::parse($start), Carbon::parse($end));

        return response()->json($report);
    }
}
