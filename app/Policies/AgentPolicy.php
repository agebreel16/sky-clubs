<?php

namespace App\Policies;

use App\Models\Agent;
use App\Models\User;

class AgentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    public function view(User $user, Agent $agent): bool
    {
        return $user->is_active;
    }

    public function create(User $user): bool
    {
        return in_array($user->role, ['super_admin', 'admin', 'supervisor', 'data_entry']);
    }

    public function update(User $user, Agent $agent): bool
    {
        return in_array($user->role, ['super_admin', 'admin', 'supervisor', 'data_entry']);
    }

    public function delete(User $user, Agent $agent): bool
    {
        return in_array($user->role, ['super_admin', 'admin']);
    }

    public function restore(User $user, Agent $agent): bool
    {
        return in_array($user->role, ['super_admin', 'admin']);
    }

    public function forceDelete(User $user, Agent $agent): bool
    {
        return false;
    }
}
