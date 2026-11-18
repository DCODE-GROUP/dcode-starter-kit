<?php

namespace App\Policies;

use App\Models\User;
use Dcodegroup\LaravelAttachments\Models\Media;
use Illuminate\Auth\Access\HandlesAuthorization;

class MediaPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Media $media): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Media $media): bool
    {
        return true;
    }

    public function delete(User $user, Media $media): bool
    {
        return method_exists($user, 'isAdmin') ? $user->isAdmin() : false;
    }

    public function restore(User $user, Media $media): bool
    {
        return method_exists($user, 'isAdmin') ? $user->isAdmin() : false;
    }

    public function forceDelete(User $user, Media $media): bool
    {
        return method_exists($user, 'isAdmin') ? $user->isAdmin() : false;
    }
}
