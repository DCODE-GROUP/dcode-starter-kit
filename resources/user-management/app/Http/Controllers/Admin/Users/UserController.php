<?php

namespace App\Http\Controllers\Admin\Users;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\User\StoreRequest;
use App\Http\Requests\Admin\User\UpdateRequest;
use App\Models\Role;
use App\Models\User;
use Dcodegroup\LaravelDsgTable\Facades\DsgTable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', User::class);

        return Inertia::render('Admin/Users/Index', [
            'fields' => DsgTable::fields('users'),
            'status' => $request->session()->get('status'),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', User::class);

        return Inertia::render('Admin/Users/CreateEdit', [
            'user' => null,
            'roles' => Role::query()->orderBy('name')->get(['id', 'name']),
            'title' => 'Create user',
        ]);
    }

    public function edit(User $user): Response
    {
        Gate::authorize('update', $user);

        return Inertia::render('Admin/Users/CreateEdit', [
            'user' => $user->load('roles'),
            'roles' => Role::query()->orderBy('name')->get(['id', 'name']),
            'title' => 'Edit user',
        ]);
    }

    public function store(StoreRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $roleIds = $data['role_ids'] ?? [];
        $profilePhoto = $data['profile_photo'] ?? null;
        unset($data['role_ids'], $data['password_confirmation'], $data['profile_photo'], $data['remove_profile_photo']);
        $data['password'] = Hash::make($data['password']);
        $data['active'] = (bool) ($data['active'] ?? false);

        $user = new User;
        $user->forceFill($data)->save();
        $this->updateProfilePhoto($user, $profilePhoto);
        $user->syncRoles($roleIds);

        return to_route('admin.users.index')->with('status', [
            'title' => 'Success',
            'message' => 'User created successfully.',
            'type' => 'success',
        ]);
    }

    public function update(UpdateRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();
        $roleIds = $data['role_ids'] ?? null;
        $profilePhoto = $data['profile_photo'] ?? null;
        $removeProfilePhoto = (bool) ($data['remove_profile_photo'] ?? false);
        unset($data['role_ids'], $data['password_confirmation'], $data['profile_photo'], $data['remove_profile_photo']);

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        } else {
            $data['password'] = Hash::make($data['password']);
        }

        $isCurrentUser = $user->getKey() === auth()->id();

        if ($isCurrentUser) {
            unset($data['active']);
            $roleIds = null;
        } else {
            $data['active'] = (bool) ($data['active'] ?? false);
        }

        $user->forceFill($data)->save();
        $this->updateProfilePhoto($user, $profilePhoto, $removeProfilePhoto);

        if ($roleIds !== null && ! $isCurrentUser) {
            $user->syncRoles($roleIds);
        }

        return to_route('admin.users.index')->with('status', [
            'title' => 'Success',
            'message' => 'User updated successfully.',
            'type' => 'success',
        ]);
    }

    public function destroy(User $user): RedirectResponse
    {
        Gate::authorize('delete', $user);
        $profilePhotoPath = $user->profile_photo_path;
        $user->delete();

        if ($profilePhotoPath) {
            Storage::disk('public')->delete($profilePhotoPath);
        }

        return to_route('admin.users.index')->with('status', [
            'title' => 'Success',
            'message' => 'User deleted successfully.',
            'type' => 'success',
        ]);
    }

    private function updateProfilePhoto(User $user, mixed $profilePhoto, bool $remove = false): void
    {
        if (! $remove && ! $profilePhoto instanceof UploadedFile) {
            return;
        }

        $oldPath = $user->profile_photo_path;

        if ($profilePhoto instanceof UploadedFile) {
            $newPath = $profilePhoto->store('profile-photos', 'public');

            if ($newPath === false) {
                return;
            }

            $user->forceFill(['profile_photo_path' => $newPath])->save();

            if ($oldPath) {
                Storage::disk('public')->delete($oldPath);
            }

            return;
        }

        $user->forceFill(['profile_photo_path' => null])->save();

        if ($oldPath) {
            Storage::disk('public')->delete($oldPath);
        }
    }
}
