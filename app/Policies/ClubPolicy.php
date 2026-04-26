<?php

namespace App\Policies;

use App\Models\Club;
use App\Models\User;

class ClubPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    public function view(User $user, Club $club): bool
    {
        return $user->is_active;
    }

    public function create(User $user): bool
    {
        return in_array($user->role, ['super_admin', 'admin']);
    }

    public function update(User $user, Club $club): bool
    {
        return in_array($user->role, ['super_admin', 'admin']);
    }

    public function delete(User $user, Club $club): bool
    {
        return $user->role === 'super_admin';
    }
}
