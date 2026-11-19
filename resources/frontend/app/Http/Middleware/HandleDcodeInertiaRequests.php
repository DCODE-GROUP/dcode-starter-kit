<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\NavigationService;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleDcodeInertiaRequests extends Middleware
{
    /** @var string */
    protected $rootView = 'dcode-starter-kit::app'; // @pest-ignore-type

    /** @return array<string, mixed> */
    public function __construct(private readonly NavigationService $navigation) {}

    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => fn () => $request->user(),
                'permissions' => fn () => $request->user() && method_exists($request->user(), 'getAllPermissions')
                    ? $request->user()->getAllPermissions()->pluck('name')->values()
                    : [],
            ],
            'translations' => [
                'auth' => trans('dcode-starter-kit::messages.auth'),
                'dashboard' => trans('dcode-starter-kit::messages.dashboard'),
                'account' => trans('dcode-starter-kit::messages.account'),
                'navigation' => trans('dcode-starter-kit::messages.navigation'),
            ],
            'navigation' => [
                'menu' => fn () => $this->navigation->menu($request),
                'profile' => fn () => $this->navigation->profile(),
            ],
        ];
    }
}
