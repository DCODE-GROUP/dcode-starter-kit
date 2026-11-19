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
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
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
        unset($data['role_ids'], $data['password_confirmation']);
        $data['password'] = Hash::make($data['password']);
        $data['active'] = (bool) ($data['active'] ?? false);

        $user = new User;
        $user->forceFill($data)->save();
        $user->syncRoles($roleIds);

        return to_route('admin.users.index')->with('status', 'User created.');
    }

    public function update(UpdateRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();
        $roleIds = $data['role_ids'] ?? null;
        unset($data['role_ids'], $data['password_confirmation']);

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        } else {
            $data['password'] = Hash::make($data['password']);
        }

        if ($user->is(auth()->user())) {
            unset($data['active'], $roleIds);
        } else {
            $data['active'] = (bool) ($data['active'] ?? false);
        }

        $user->forceFill($data)->save();

        if ($roleIds !== null && ! $user->is(auth()->user())) {
            $user->syncRoles($roleIds);
        }

        return to_route('admin.users.edit', $user)->with('status', 'User updated.');
    }

    public function destroy(User $user): RedirectResponse
    {
        Gate::authorize('delete', $user);
        $user->delete();

        return to_route('admin.users.index')->with('status', 'User deleted.');
    }
}
