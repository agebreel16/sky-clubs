<?php

namespace App\Policies;

use App\Models\Reward;
use App\Models\User;

class RewardPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    public function view(User $user, Reward $reward): bool
    {
        return $user->is_active;
    }

    public function create(User $user): bool
    {
        return in_array($user->role, ['super_admin', 'admin', 'finance_officer']);
    }

    public function update(User $user, Reward $reward): bool
    {
        return in_array($user->role, ['super_admin', 'admin', 'finance_officer']);
    }

    public function delete(User $user, Reward $reward): bool
    {
        return $user->role === 'super_admin';
    }

    public function restore(User $user, Reward $reward): bool
    {
        return $user->role === 'super_admin';
    }

    public function forceDelete(User $user, Reward $reward): bool
    {
        return false;
    }
}
