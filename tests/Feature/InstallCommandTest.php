<?php

declare(strict_types=1);

use DcodeStarterKit\DcodeStarterKit\Actions\InstallDcodeStarterKitDependencies;

it('installs runtime and development dependencies with Composer from the application root', function () {
    $calls = [];
    $runner = function (array $arguments, string $workingDirectory, callable $output) use (&$calls): bool {
        $calls[] = [$arguments, $workingDirectory];

        return true;
    };

    app()->instance(InstallDcodeStarterKitDependencies::class, new InstallDcodeStarterKitDependencies($runner));

    $this->artisan('dcode:install')
        ->expectsOutputToContain('Dcode Starter Kit dependencies installed successfully.')
        ->assertSuccessful();

    expect($calls)->toHaveCount(2)
        ->and($calls[0])->toBe([
            [
                'composer',
                'require',
                'bugsnag/bugsnag-laravel',
                'dcodegroup/activity-log',
                'dcodegroup/laravel-attachments-backend',
                'spatie/cpu-load-health-check',
                'spatie/laravel-health',
                'spatie/laravel-log-dumper',
                'spatie/laravel-login-link',
                'spatie/laravel-model-status',
                'spatie/laravel-permission',
                'spatie/laravel-query-builder',
                'tightenco/ziggy',
            ],
            base_path(),
        ])
        ->and($calls[1])->toBe([
            ['composer', 'require', '--dev', 'dcode-group/composer-git-hooks'],
            base_path(),
        ]);
});

it('does not install development dependencies when runtime installation fails', function () {
    $calls = [];
    $runner = function (array $arguments, string $workingDirectory, callable $output) use (&$calls): bool {
        $calls[] = $arguments;

        return false;
    };

    app()->instance(InstallDcodeStarterKitDependencies::class, new InstallDcodeStarterKitDependencies($runner));

    $this->artisan('dcode:install')
        ->expectsOutputToContain('installation failed during runtime dependency installation')
        ->assertFailed();

    expect($calls)->toHaveCount(1);
});

it('reports development installation failure after runtime installation succeeds', function () {
    $calls = 0;
    $runner = function () use (&$calls): bool {
        $calls++;

        return $calls === 1;
    };

    app()->instance(InstallDcodeStarterKitDependencies::class, new InstallDcodeStarterKitDependencies($runner));

    $this->artisan('dcode:install')
        ->expectsOutputToContain('installation failed during development dependency installation')
        ->expectsOutputToContain('Runtime dependencies were installed successfully')
        ->doesntExpectOutputToContain('installed successfully.')
        ->assertFailed();

    expect($calls)->toBe(2);
});

it('reports success only after both dependency groups install', function () {
    $runner = function (array $arguments, string $workingDirectory, callable $output): bool {
        $output("Installing {$arguments[2]}\n");

        return true;
    };

    app()->instance(InstallDcodeStarterKitDependencies::class, new InstallDcodeStarterKitDependencies($runner));

    $this->artisan('dcode:install')
        ->expectsOutputToContain('Installing bugsnag/bugsnag-laravel')
        ->expectsOutputToContain('Dcode Starter Kit dependencies installed successfully.')
        ->assertSuccessful();
});
