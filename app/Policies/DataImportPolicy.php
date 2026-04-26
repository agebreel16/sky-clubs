<?php

namespace App\Policies;

use App\Models\DataImport;
use App\Models\User;

class DataImportPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    public function view(User $user, DataImport $dataImport): bool
    {
        return $user->is_active;
    }

    public function create(User $user): bool
    {
        return in_array($user->role, ['super_admin', 'admin', 'data_entry']);
    }

    public function update(User $user, DataImport $dataImport): bool
    {
        return false;
    }

    public function delete(User $user, DataImport $dataImport): bool
    {
        return $user->role === 'super_admin';
    }
}
