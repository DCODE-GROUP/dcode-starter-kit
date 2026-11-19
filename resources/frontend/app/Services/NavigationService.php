<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Request;

class NavigationService
{
    /** @var array<int, array<string, mixed>>|null */
    protected ?array $cachedMenu = null;

    /** @return array<int, array<string, mixed>> */
    public function menu(Request $request): array
    {
        if ($this->cachedMenu !== null) {
            return $this->cachedMenu;
        }

        $menu = $this->filterMenuByPermissions($this->getMenu($request), $this->permissionNames($request));

        return $this->cachedMenu = collect($menu)->filter(fn ($item) => $item !== null)->values()->all();
    }

    /** @return array<int, array<string, mixed>> */
    public function profile(): array
    {
        return [
            [
                'title' => __('messages.navigation.profile'),
                'icon' => 'user-01',
                'url' => route('profile.edit'),
                'inertia' => true,
            ],
            [
                'title' => __('messages.navigation.logout'),
                'icon' => 'log-out-01',
                'url' => route('logout'),
                'action' => 'logout',
            ],
        ];
    }

    /** @return array<int, array<string, mixed>> */
    protected function getMenu(Request $request): array
    {
        return [
            [
                'title' => __('messages.navigation.dashboard'),
                'icon' => 'home-01',
                'url' => route('admin.dashboard'),
                'isActive' => $request->routeIs('admin.dashboard'),
                'inertia' => true,
            ],
            [
                'title' => __('messages.navigation.settings'),
                'icon' => 'settings-02',
                'submenu' => [
                    [
                        'title' => __('messages.navigation.users'),
                        'icon' => 'users-01',
                        'url' => route('admin.users.index'),
                        'permissions' => ['admin-portal-users-view-list'],
                        'isActive' => $request->routeIs('admin.users.*'),
                        'inertia' => true,
                    ],
                    [
                        'title' => __('messages.navigation.roles'),
                        'icon' => 'shield-01',
                        'url' => route('admin.roles.index'),
                        'permissions' => ['admin-portal-roles-permissions-view-list'],
                        'isActive' => $request->routeIs('admin.roles.*'),
                        'inertia' => true,
                    ],
                ],
            ],
        ];
    }

    /** @param array<int, array<string, mixed>> $menu */
    /** @param array<int, string> $permissions */
    /** @return array<int, array<string, mixed>|null> */
    protected function filterMenuByPermissions(array $menu, array $permissions): array
    {
        return collect($menu)->map(function (array $item) use ($permissions): ?array {
            if (isset($item['permissions']) && ! $this->hasPermissions($item['permissions'], $permissions)) {
                return null;
            }

            if (isset($item['submenu'])) {
                $item['submenu'] = $this->filterMenuByPermissions($item['submenu'], $permissions);

                if ($item['submenu'] === []) {
                    return null;
                }
            }

            return $item;
        })->all();
    }

    /** @param array<int, string> $required */
    /** @param array<int, string> $permissions */
    private function hasPermissions(array $required, array $permissions): bool
    {
        return collect($required)->every(fn (string $permission): bool => in_array($permission, $permissions, true));
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
