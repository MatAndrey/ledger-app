<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Account;
use App\Services\LedgerService;
use Carbon\Carbon;
use Rap2hpoutre\FastExcel\FastExcel;


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

        $data = $ledgerService->generateTrialBalance(Carbon::parse($start), Carbon::parse($end));

        $format = $request->input('format');

        if ($format === 'csv' || $format === 'xlsx') {
            $rows = $data->map(function ($item) {
                return [
                    'Код' => $item->account->code ?? 'ИТОГО',
                    'Счёт' => $item->account?->name ?? 'ИТОГО',
                    'Тип' => $item->account?->type ?? '',
                    'Начальный Дебет' => $item->opening_debit,
                    'Начальный Кредит' => $item->opening_credit,
                    'Оборот Дебет' => $item->debit_turnover,
                    'Оборот Кредит' => $item->credit_turnover,
                    'Конечный Дебет' => $item->closing_debit,
                    'Конечный Кредит' => $item->closing_credit,
                ];
            })->toArray();

            $filename = 'trial_balance_' . now()->format('Ymd_His') . '.' . ($format === 'csv' ? 'csv' : 'xlsx');
            $path = storage_path('app/public/exports/' . $filename);

            if (!is_dir(dirname($path))) {
                mkdir(dirname($path), 0755, true);
            }

            $fastExcel = new FastExcel($rows);
            if ($format === 'csv') {
                $fastExcel->configureCsv(';');
            }
            $fastExcel->export($path);

            return response()->download($path, $filename)->deleteFileAfterSend(true);
        }

        return response()->json($data);
    }
}
