<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use App\Support\DcodeStarterKitPermissions;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

// Dcode Starter Kit database seeder
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = collect(DcodeStarterKitPermissions::all())
            ->map(fn (string $name): Permission => Permission::query()->firstOrCreate([
                'name' => $name,
                'guard_name' => 'web',
            ]));

        $adminRole = Role::query()->firstOrCreate([
            'name' => 'Admin',
            'guard_name' => 'web',
        ]);
        $adminRole->givePermissionTo($permissions);

        $admin = User::query()->firstOrCreate(
            ['email' => 'forge@dcodegroup.com'],
            [
                'first_name' => 'Dcode',
                'last_name' => 'Admin',
                'password' => Hash::make('Dcode123!!'),
                'active' => true,
                'email_verified_at' => now(),
            ],
        );

        $admin->forceFill(['active' => true])->save();
        $admin->syncRoles([$adminRole]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
