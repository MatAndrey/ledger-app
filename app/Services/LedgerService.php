<?php

namespace App\Services;

use App\Repositories\LedgerRepository;
use App\Enums\AccountTypes;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class LedgerService
{
    public function __construct(protected LedgerRepository $ledgerRepo)
    {
    }

    public function generateTrialBalance(Carbon $start, Carbon $end): Collection
    {
        $accounts = $this->ledgerRepo->getAll();

        $openingBalances = $this->ledgerRepo->getOpeningBalances($start)->keyBy('account_id');
        $turnovers = $this->ledgerRepo->getTurnovers($start, $end)->keyBy('account_id');

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
}