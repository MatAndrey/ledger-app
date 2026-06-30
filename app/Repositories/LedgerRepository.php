<?php

namespace App\Repositories;

use App\Models\Account;
use App\Models\JournalEntry;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LedgerRepository
{
    public function getAll(): Collection
    {
        return Account::orderBy('code')->get();
    }

    public function getOpeningBalances(Carbon $date): Collection
    {
        return JournalEntry::whereHas('transaction', function ($q) use ($date) {
                $q->where('date', '<', $date);
            })
            ->select('account_id')
            ->selectRaw('SUM(CASE WHEN type = ? THEN amount ELSE 0 END) as debit_sum', ['debit'])
            ->selectRaw('SUM(CASE WHEN type = ? THEN amount ELSE 0 END) as credit_sum', ['credit'])
            ->groupBy('account_id')
            ->get();
    }

    public function getTurnovers(Carbon $start, Carbon $end): Collection
    {
        return JournalEntry::whereHas('transaction', function ($q) use ($start, $end) {
                $q->whereBetween('date', [$start, $end]);
            })
            ->select('account_id')
            ->selectRaw('SUM(CASE WHEN type = ? THEN amount ELSE 0 END) as debit_turnover', ['debit'])
            ->selectRaw('SUM(CASE WHEN type = ? THEN amount ELSE 0 END) as credit_turnover', ['credit'])
            ->groupBy('account_id')
            ->get();
    }
}