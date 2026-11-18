<?php

declare(strict_types=1);

use DcodeStarterKit\DcodeStarterKit\Actions\InstallDcodeStarterKitDependencies;
use DcodeStarterKit\DcodeStarterKit\Actions\InstallDcodeStarterKitFrontend;
use DcodeStarterKit\DcodeStarterKit\Actions\PublishActivityLogMigrations;
use DcodeStarterKit\DcodeStarterKit\Actions\SetupActivityLog;
use DcodeStarterKit\DcodeStarterKit\Actions\SetupComposerHooks;
use DcodeStarterKit\DcodeStarterKit\Actions\SetupDefaultUsersMigration;
use DcodeStarterKit\DcodeStarterKit\Actions\SetupFortifySanctum;
use DcodeStarterKit\DcodeStarterKit\Actions\SetupMacroServiceProvider;
use DcodeStarterKit\DcodeStarterKit\Actions\SetupSpatiePermission;
use DcodeStarterKit\DcodeStarterKit\DcodeStarterKit;

it('resolves the singleton', function () {
    expect(app(DcodeStarterKit::class))->toBeInstanceOf(DcodeStarterKit::class);
});

it('returns the same instance from the container', function () {
    expect(app(DcodeStarterKit::class))->toBe(app(DcodeStarterKit::class));
});

it('merges the package config', function () {
    expect(config('dcode-starter-kit.placeholder'))->toBe('default');
});

it('loads the package translations', function () {
    expect(trans('dcode-starter-kit::messages.placeholder'))->toBe('DcodeStarterKit placeholder translation.');
});

it('loads the package views', function () {
    expect(view()->exists('dcode-starter-kit::placeholder'))->toBeTrue();
});

it('registers the install artisan command', function () {
    $composerHooks = Mockery::mock(SetupComposerHooks::class);
    $composerHooks->allows('setup')->andReturn(true);
    app()->instance(SetupComposerHooks::class, $composerHooks);

    $permissionSetup = Mockery::mock(SetupSpatiePermission::class);
    $permissionSetup->allows('setup')->andReturn(true);
    app()->instance(SetupSpatiePermission::class, $permissionSetup);

    $frontend = Mockery::mock(InstallDcodeStarterKitFrontend::class);
    $frontend->allows('install')->andReturn(true);
    app()->instance(InstallDcodeStarterKitFrontend::class, $frontend);

    $activityLogMigrations = Mockery::mock(PublishActivityLogMigrations::class);
    $activityLogMigrations->allows('publish')->andReturn(true);
    app()->instance(PublishActivityLogMigrations::class, $activityLogMigrations);

    $macroServiceProvider = Mockery::mock(SetupMacroServiceProvider::class);
    $macroServiceProvider->allows('setup')->andReturn(true);
    app()->instance(SetupMacroServiceProvider::class, $macroServiceProvider);

    $usersMigration = Mockery::mock(SetupDefaultUsersMigration::class);
    $usersMigration->allows('setup')->andReturn(true);
    app()->instance(SetupDefaultUsersMigration::class, $usersMigration);

    $fortifySanctum = Mockery::mock(SetupFortifySanctum::class);
    $fortifySanctum->allows('setup')->andReturn(true);
    app()->instance(SetupFortifySanctum::class, $fortifySanctum);

    $activityLog = Mockery::mock(SetupActivityLog::class);
    $activityLog->allows('setup')->andReturn(true);
    app()->instance(SetupActivityLog::class, $activityLog);

    app()->instance(InstallDcodeStarterKitDependencies::class, new InstallDcodeStarterKitDependencies(
        function (array $arguments, string $workingDirectory, callable $output): bool {
            return true;
        },
    ));

    $this->artisan('dcode:install')
        ->expectsOutputToContain('Dcode Starter Kit dependencies installed successfully.')
        ->assertSuccessful();
});
