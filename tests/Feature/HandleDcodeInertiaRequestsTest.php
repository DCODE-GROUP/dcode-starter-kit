<?php

declare(strict_types=1);

it('ships the inertia middleware as host application code', function () {
    $path = __DIR__.'/../../resources/frontend/app/Http/Middleware/HandleDcodeInertiaRequests.php';
    $contents = file_get_contents($path);
    $navigation = file_get_contents(__DIR__.'/../../resources/frontend/app/Services/NavigationService.php');

    expect($contents)->toBeString()
        ->and($contents)->toContain('namespace App\\Http\\Middleware;')
        ->and($contents)->toContain('protected $rootView')
        ->and($contents)->toContain("'app'")
        ->and($contents)->toContain('NavigationService')
        ->and(is_file(__DIR__.'/../../resources/frontend/app/Services/NavigationService.php'))->toBeTrue()
        ->and($navigation)->toContain("'permissions'")
        ->toContain('cachedMenu')
        ->toContain('filterMenuByPermissions');
});

it('ships split host controllers for the frontend foundation', function () {
    $root = __DIR__.'/../../resources/frontend/app/Http/Controllers';

    expect(is_file($root.'/DashboardController.php'))->toBeTrue()
        ->and(is_file($root.'/Auth/AuthenticatedSessionController.php'))->toBeTrue()
        ->and(is_file($root.'/Auth/NewPasswordController.php'))->toBeTrue()
        ->and(is_file($root.'/Auth/PasswordResetLinkController.php'))->toBeTrue()
        ->and(is_file($root.'/Settings/ProfileController.php'))->toBeTrue()
        ->and(is_file($root.'/Settings/PasswordController.php'))->toBeTrue()
        ->and(is_file($root.'/DcodeFrontendController.php'))->toBeFalse();
});
