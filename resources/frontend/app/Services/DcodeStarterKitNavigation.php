<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Request;

class DcodeStarterKitNavigation
{
    /** @return array<int, array<string, mixed>> */
    public function menu(Request $request): array
    {
        $permissions = $this->permissionNames($request);
        $settings = [];

        if (in_array('admin-portal-users-view-list', $permissions, true)) {
            $settings[] = [
                'title' => __('dcode-starter-kit::messages.navigation.users'),
                'icon' => 'users-01',
                'url' => route('admin.users.index'),
                'isActive' => $request->routeIs('admin.users.*'),
                'inertia' => true,
            ];
        }

        if (in_array('admin-portal-roles-permissions-view-list', $permissions, true)) {
            $settings[] = [
                'title' => __('dcode-starter-kit::messages.navigation.roles'),
                'icon' => 'shield-01',
                'url' => route('admin.roles.index'),
                'isActive' => $request->routeIs('admin.roles.*'),
                'inertia' => true,
            ];
        }

        return array_values(array_filter([
            [
                'title' => __('dcode-starter-kit::messages.navigation.dashboard'),
                'icon' => 'home-01',
                'url' => route('admin.dashboard'),
                'isActive' => $request->routeIs('admin.dashboard'),
                'inertia' => true,
            ],
            $settings === [] ? null : [
                'title' => __('dcode-starter-kit::messages.navigation.settings'),
                'icon' => 'settings-02',
                'isActive' => collect($settings)->contains('isActive', true),
                'submenu' => $settings,
            ],
        ]));
    }

    /** @return array<int, array<string, mixed>> */
    public function profile(Request $request): array
    {
        return [
            [
                'title' => __('dcode-starter-kit::messages.navigation.profile'),
                'icon' => 'user-01',
                'url' => route('profile.edit'),
                'inertia' => true,
            ],
            [
                'title' => __('dcode-starter-kit::messages.navigation.logout'),
                'icon' => 'log-out-01',
                'url' => route('logout'),
                'action' => 'logout',
            ],
        ];
    }

    /** @return array<int, string> */
    private function permissionNames(Request $request): array
    {
        $user = $request->user();

        if (! $user || ! method_exists($user, 'getAllPermissions')) {
            return [];
        }

        return $user->getAllPermissions()->pluck('name')->values()->all();
    }
}
