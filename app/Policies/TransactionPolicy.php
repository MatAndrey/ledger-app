<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use App\Models\Transaction;
use MoonShine\Laravel\Models\MoonshineUser;

class TransactionPolicy
{
    use HandlesAuthorization;

    public function viewAny(MoonshineUser $user): bool
    {
        return true;
    }

    public function view(MoonshineUser $user, Transaction $item): bool
    {
        return true;
    }

    public function create(MoonshineUser $user): bool
    {
        return true;
    }

    public function update(MoonshineUser $user, Transaction $item): bool
    {
        return false;
    }

    public function delete(MoonshineUser $user, Transaction $item): bool
    {
        return false;
    }

    public function restore(MoonshineUser $user, Transaction $item): bool
    {
        return false;
    }

    public function forceDelete(MoonshineUser $user, Transaction $item): bool
    {
        return false;
    }

    public function massDelete(MoonshineUser $user): bool
    {
        return false;
    }
}
