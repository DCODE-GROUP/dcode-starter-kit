<?php

namespace App\Policies;

use App\Models\User;
use App\Support\DcodeStarterKitPermissions;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can(DcodeStarterKitPermissions::USERS_VIEW_LIST);
    }

    public function view(User $user, User $model): bool
    {
        return $user->id === $model->id || $user->can(DcodeStarterKitPermissions::USERS_VIEW);
    }

    public function create(User $user): bool
    {
        return $user->can(DcodeStarterKitPermissions::USERS_CREATE);
    }

    public function update(User $user, User $model): bool
    {
        return $user->id === $model->id || $user->can(DcodeStarterKitPermissions::USERS_UPDATE);
    }

    public function delete(User $user, User $model): bool
    {
        return $user->id !== $model->id && $user->can(DcodeStarterKitPermissions::USERS_DELETE);
    }
}
