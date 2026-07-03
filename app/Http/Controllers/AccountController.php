<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Account;
use App\Services\AccountService;
use App\Http\Requests\StoreAccountRequest;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class AccountController extends Controller
{
    public function __construct(protected AccountService $accountService) {}

    /** @throws \Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException */
    public function index(): JsonResponse {
        $accounts = $this->accountService->getAll();
        return response()->json($accounts);
    }

    /** @throws \Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException */
    public function balance(Account $account, Request $request): JsonResponse {
        $validated = $request->validate(['asOf'  => 'nullable|date']);
        $asOf = isset($validated['asOf']) ? Carbon::parse($validated['asOf']) : null;
        $balance = $this->accountService->getBalance($account, $asOf);

        return response()->json([
            'account_id' => $account->id,
            'balance' => $balance
        ]);
    }

    /** @throws \Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException */
    public function trialBalance(Request $request): JsonResponse 
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

        $report = $this->accountService->generateTrialBalance(Carbon::parse($start), Carbon::parse($end));

        return response()->json($report);
    }

    /** @throws \Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException */
    public function store(StoreAccountRequest $request): JsonResponse  {
        $account = $this->accountService->createAccount($request->validated());
        return response()->json([
            'message' => 'Account created successfully',
            'data' => $account
        ], 201);
    }

    /** @throws \Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException */
    public function show(Account $account): JsonResponse  {
        return response()->json($account, 200);
    }

    /** @throws \Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException */
    public function destroy(Account $account)  {
        $account = $this->accountService->destroyAccount($account);
        return response(null, 204);
    }

    /** @throws \Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException */
    public function update(Account $account, StoreAccountRequest $request)  {

        $account = $this->accountService->updateAccount($account, $request->validated());
        return response()->json($account, 200);
    }
}
