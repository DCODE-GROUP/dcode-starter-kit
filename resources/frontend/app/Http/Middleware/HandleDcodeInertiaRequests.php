<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleDcodeInertiaRequests extends Middleware
{
    /** @var string */
    protected $rootView = 'dcode-starter-kit::app'; // @pest-ignore-type

    /** @return array<string, mixed> */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => fn () => $request->user(),
            ],
            'translations' => [
                'auth' => trans('dcode-starter-kit::messages.auth'),
                'dashboard' => trans('dcode-starter-kit::messages.dashboard'),
                'account' => trans('dcode-starter-kit::messages.account'),
                'navigation' => trans('dcode-starter-kit::messages.navigation'),
            ],
        ];
    }
}
