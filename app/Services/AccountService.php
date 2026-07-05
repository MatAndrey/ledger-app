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
use Illuminate\Validation\ValidationException;

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

            $type = $account->type instanceof AccountTypes ? $account->type->value : $account->type;
            if (in_array($type, ['asset', 'expense'])) {
                return $debitSum - $creditSum;
            }
            return $creditSum - $debitSum;
        });
    }

    public function generateTrialBalance(Carbon $start, Carbon $end): Collection
    {
        $accounts = $this->accountRepository->getAll();

        $openingBalances = $this->accountRepository->getBalancesAt($start)->keyBy('account_id');
        $closingBalances = $this->accountRepository->getBalancesAt($end)->keyBy('account_id');

        $report = collect();

        foreach ($accounts as $account) {
            $opening = $openingBalances->get($account->id);
            $closing = $closingBalances->get($account->id);

            $openingDebit = $opening ? (float) $opening->debit_sum : 0;
            $openingCredit = $opening ? (float) $opening->credit_sum : 0;
            $closingDebit = $closing ? (float) $closing->debit_sum : 0;
            $closingCredit = $closing ? (float) $closing->credit_sum : 0;
            $debitTurnover = round($closingDebit - $openingDebit, 2);
            $creditTurnover = round($closingCredit - $openingCredit, 2);

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

    public function createAccount(array $requestData): Account {
        return $this->accountRepository->store($requestData);
    }

    public function getAllAccounts(): Collection {
        return $this->accountRepository->getAll();
    }

    public function destroyAccount(Account $account) {
        $this->accountRepository->destroy($account);
    }

    public function updateAccount(Account $account, array $requestData)  {
        return $this->accountRepository->update($account, $requestData);
    }
}