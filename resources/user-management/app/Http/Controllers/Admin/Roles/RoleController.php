<?php

namespace App\Http\Controllers\Admin\Roles;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Role\StoreRequest;
use App\Http\Requests\Admin\Role\UpdateRequest;
use App\Models\Role;
use App\Support\DcodeStarterKitPermissions;
use Dcodegroup\LaravelDsgTable\Facades\DsgTable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class RoleController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Role::class);

        return Inertia::render('Admin/Roles/Index', [
            'fields' => DsgTable::fields('roles'),
            'status' => $request->session()->get('status'),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Role::class);

        return Inertia::render('Admin/Roles/CreateEdit', [
            'role' => null,
            'permissions' => DcodeStarterKitPermissions::all(),
            'title' => 'Create role',
        ]);
    }

    public function edit(Role $role): Response
    {
        Gate::authorize('update', $role);

        return Inertia::render('Admin/Roles/CreateEdit', [
            'role' => [
                'id' => $role->id,
                'name' => $role->name,
                'permissions' => $role->permissions->pluck('name')->values(),
            ],
            'permissions' => DcodeStarterKitPermissions::all(),
            'title' => 'Edit role',
        ]);
    }

    public function store(StoreRequest $request): RedirectResponse
    {
        $role = Role::query()->create([
            'name' => $request->validated('name'),
            'guard_name' => 'web',
        ]);
        $role->syncPermissions($request->validated('permissions', []));

        return to_route('admin.roles.index')->with('status', 'Role created.');
    }

    public function update(UpdateRequest $request, Role $role): RedirectResponse
    {
        $role->update(['name' => $request->validated('name')]);
        $role->syncPermissions($request->validated('permissions', []));

        return to_route('admin.roles.edit', $role)->with('status', 'Role updated.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        Gate::authorize('delete', $role);
        $role->delete();

        return to_route('admin.roles.index')->with('status', 'Role deleted.');
    }
}
