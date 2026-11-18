<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\User;
use App\Support\DcodeStarterKitPermissions;
use Illuminate\Auth\Access\HandlesAuthorization;

class RolePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can(DcodeStarterKitPermissions::ROLES_VIEW_LIST);
    }

    public function view(User $user, Role $role): bool
    {
        return $user->can(DcodeStarterKitPermissions::ROLES_VIEW);
    }

    public function create(User $user): bool
    {
        return $user->can(DcodeStarterKitPermissions::ROLES_CREATE);
    }

    public function update(User $user, Role $role): bool
    {
        return $user->can(DcodeStarterKitPermissions::ROLES_UPDATE);
    }

    public function delete(User $user, Role $role): bool
    {
        return $user->can(DcodeStarterKitPermissions::ROLES_DELETE);
    }
}
