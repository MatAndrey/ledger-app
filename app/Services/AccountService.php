<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;
use App\Repositories\AccountRepository;
use App\Repositories\JournalEntryRepository;
use App\Enums\AccountTypes;
use App\Models\Account;
use Rap2hpoutre\FastExcel\FastExcel;

class AccountService
{
    public function __construct(
        protected AccountRepository $accountRepository
        ) {}

    public function getBalance(Account $account, ?Carbon $asOf = null) {
        $cacheKey = "account_{$account->id}_balance_{$asOf}";
    
        return Cache::remember($cacheKey, 60, function () use ($account, $asOf) {
            $debitSum = $this->accountRepository->getJournalEntriesSum($account, 'debit', $asOf);
            $creditSum = $this->accountRepository->getJournalEntriesSum($account, 'credit', $asOf);

            if ($account->type == AccountTypes::Asset || $account->type == AccountTypes::Expense) {
                return $debitSum - $creditSum;
            }
            return $creditSum - $debitSum;
        });
    }

    public function generateTrialBalance(Carbon $start, Carbon $end): Collection
    {
        $accounts = $this->accountRepository->getAll();

        $openingBalances = $this->accountRepository->getOpeningBalances($start)->keyBy('account_id');
        $turnovers = $this->accountRepository->getTurnovers($start, $end)->keyBy('account_id');

        $report = collect();

        foreach ($accounts as $account) {
            $opening = $openingBalances->get($account->id);
            $turnover = $turnovers->get($account->id);

            $openingDebit = $opening ? (float) $opening->debit_sum : 0;
            $openingCredit = $opening ? (float) $opening->credit_sum : 0;
            $debitTurnover = $turnover ? (float) $turnover->debit_turnover : 0;
            $creditTurnover = $turnover ? (float) $turnover->credit_turnover : 0;

            $closingDebit = $openingDebit + $debitTurnover;
            $closingCredit = $openingCredit + $creditTurnover;

            $report->push((object) [
                'account' => $account,
                'opening_debit'  => $openingDebit,
                'opening_credit' => $openingCredit,
                'debit_turnover' => $debitTurnover,
                'credit_turnover'=> $creditTurnover,
                'closing_debit' => $closingDebit,
                'closing_credit' => $closingCredit
            ]);
        }

        $totals = (object) [
            'account' => (object) [
                'name' => 'Итого'
            ],
            'opening_debit'  => $report->sum('opening_debit'),
            'opening_credit' => $report->sum('opening_credit'),
            'debit_turnover' => $report->sum('debit_turnover'),
            'credit_turnover'=> $report->sum('credit_turnover'),
            'closing_debit'   => $report->sum('closing_debit'),
            'closing_credit'  => $report->sum('closing_credit'),
            'is_total' => true,
        ];
        $report->push($totals);

        return $report;
    }

    public function generateTrialBalanceFile(Carbon $start, Carbon $end, string $format = 'csv'): string  {
        $report = $this->generateTrialBalance($start, $end);
        $rows = $report->map(function ($item) {
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

        $filename = 'trial_balance_' . $start . '_' . $end . '.' . ($format === 'csv' ? 'csv' : 'xlsx');
        $path = storage_path('app/public/exports/' . $filename);

        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        $fastExcel = new FastExcel($rows);
        if ($format === 'csv') {
            $fastExcel->configureCsv(';');
        }
        $fastExcel->export($path);

        if ($format === 'csv') {
            $content = file_get_contents($path);
            $content = "\xEF\xBB\xBF" . $content;
            file_put_contents($path, $content);
        }            

        return $path;
    }
}