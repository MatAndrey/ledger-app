<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use App\Models\Account;
use MoonShine\Laravel\Models\MoonshineUser;

class AccountPolicy
{
    use HandlesAuthorization;

    public function viewAny(MoonshineUser $user): bool
    {
        return true;
    }

    public function view(MoonshineUser $user, Account $item): bool
    {
        return true;
    }

    public function create(MoonshineUser $user): bool
    {
        return true;
    }

    public function update(MoonshineUser $user, Account $item): bool
    {
        return true;
    }

    public function delete(MoonshineUser $user, Account $item): bool
    {
        if($item->journalEntries()->first()) return false;
        return true;
    }

    public function restore(MoonshineUser $user, Account $item): bool
    {
        return true;
    }

    public function forceDelete(MoonshineUser $user, Account $item): bool
    {
        return true;
    }

    public function massDelete(MoonshineUser $user): bool
    {
        return false;
    }
}
