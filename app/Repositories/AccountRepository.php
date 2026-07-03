<?php

namespace App\Repositories;

use App\Models\Account;
use App\Models\JournalEntry;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class AccountRepository
{
    public function getAll(): Collection
    {
        return Account::orderBy('code')->get();
    }

    public function getOpeningBalances(Carbon $date): Collection
    {
        return JournalEntry::whereHas('transaction', function ($q) use ($date) {
                $q->where('date', '<', $date)->where('is_posted', true);
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
                $q->whereBetween('date', [$start, $end])->where('is_posted', true);
            })
            ->select('account_id')
            ->selectRaw('SUM(CASE WHEN type = ? THEN amount ELSE 0 END) as debit_turnover', ['debit'])
            ->selectRaw('SUM(CASE WHEN type = ? THEN amount ELSE 0 END) as credit_turnover', ['credit'])
            ->groupBy('account_id')
            ->get();
    }

    public function getJournalEntriesSum(Account $account, ?string $type = '', ?Carbon $asOf): int {
        $query = $account->journalEntries();
        if ($asOf) {
            $query->whereHas('transaction', fn($q) => $q->where('date', '<=', $asOf));
        }

        if($type) {
            $query->where('type', $type);
        }

        return $query->sum('amount');
    }

    public function getByCode(int $code): ?Account {
        return Account::where('code', $code)->first();
    }

    public function store($data): Account {
        return Account::create($data);
    }

    public function destroy(Account $account) {
        $account->delete();
    }

    public function update(Account $account, array $data)  {
        $account->name = $data['name'];
        $account->code = $data['code'];
        $account->type = $data['type'];
        $account->is_active = $data['is_active'];
        $account->save();
        return $account;
    }
}