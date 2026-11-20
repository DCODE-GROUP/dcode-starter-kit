<?php

declare(strict_types=1);

use DcodeStarterKit\DcodeStarterKit\Actions\InstallDcodeStarterKitDependencies;
use DcodeStarterKit\DcodeStarterKit\Actions\InstallDcodeStarterKitFrontend;
use DcodeStarterKit\DcodeStarterKit\Actions\PublishActivityLogMigrations;
use DcodeStarterKit\DcodeStarterKit\Actions\PublishAttachmentMigrations;
use DcodeStarterKit\DcodeStarterKit\Actions\RunDcodeStarterKitDatabaseSetup;
use DcodeStarterKit\DcodeStarterKit\Actions\SetupActivityLog;
use DcodeStarterKit\DcodeStarterKit\Actions\SetupApplicationDefaults;
use DcodeStarterKit\DcodeStarterKit\Actions\SetupAttachments;
use DcodeStarterKit\DcodeStarterKit\Actions\SetupAuditLogging;
use DcodeStarterKit\DcodeStarterKit\Actions\SetupAws;
use DcodeStarterKit\DcodeStarterKit\Actions\SetupBugsnag;
use DcodeStarterKit\DcodeStarterKit\Actions\SetupComposerHooks;
use DcodeStarterKit\DcodeStarterKit\Actions\SetupDefaultUsersMigration;
use DcodeStarterKit\DcodeStarterKit\Actions\SetupFortifySanctum;
use DcodeStarterKit\DcodeStarterKit\Actions\SetupHealth;
use DcodeStarterKit\DcodeStarterKit\Actions\SetupLoginLink;
use DcodeStarterKit\DcodeStarterKit\Actions\SetupMacroServiceProvider;
use DcodeStarterKit\DcodeStarterKit\Actions\SetupSlack;
use DcodeStarterKit\DcodeStarterKit\Actions\SetupSpatiePermission;
use DcodeStarterKit\DcodeStarterKit\Actions\SetupUserManagement;
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

    $auditLogging = Mockery::mock(SetupAuditLogging::class);
    $auditLogging->allows('setup')->andReturn(true);
    app()->instance(SetupAuditLogging::class, $auditLogging);

    $applicationDefaults = Mockery::mock(SetupApplicationDefaults::class);
    $applicationDefaults->allows('setup')->andReturn(true);
    app()->instance(SetupApplicationDefaults::class, $applicationDefaults);

    $aws = Mockery::mock(SetupAws::class);
    $aws->allows('setup')->andReturn(true);
    app()->instance(SetupAws::class, $aws);

    $bugsnag = Mockery::mock(SetupBugsnag::class);
    $bugsnag->allows('setup')->andReturn(true);
    app()->instance(SetupBugsnag::class, $bugsnag);

    $attachmentMigrations = Mockery::mock(PublishAttachmentMigrations::class);
    $attachmentMigrations->allows('publish')->andReturn(true);
    app()->instance(PublishAttachmentMigrations::class, $attachmentMigrations);

    $attachments = Mockery::mock(SetupAttachments::class);
    $attachments->allows('setup')->andReturn(true);
    app()->instance(SetupAttachments::class, $attachments);

    $userManagement = Mockery::mock(SetupUserManagement::class);
    $userManagement->allows('setup')->andReturn(true);
    app()->instance(SetupUserManagement::class, $userManagement);

    $loginLink = Mockery::mock(SetupLoginLink::class);
    $loginLink->allows('setup')->andReturn(true);
    app()->instance(SetupLoginLink::class, $loginLink);

    $health = Mockery::mock(SetupHealth::class);
    $health->allows('setup')->andReturn(true);
    app()->instance(SetupHealth::class, $health);

    $slack = Mockery::mock(SetupSlack::class);
    $slack->allows('setup')->andReturn(true);
    app()->instance(SetupSlack::class, $slack);

    $databaseSetup = Mockery::mock(RunDcodeStarterKitDatabaseSetup::class);
    $databaseSetup->allows('run')->andReturn(true);
    app()->instance(RunDcodeStarterKitDatabaseSetup::class, $databaseSetup);

    app()->instance(InstallDcodeStarterKitDependencies::class, new InstallDcodeStarterKitDependencies(
        function (array $arguments, string $workingDirectory, callable $output): bool {
            return true;
        },
    ));

    $this->artisan('dcode:install')
        ->expectsOutputToContain('Dcode Starter Kit dependencies installed successfully.')
        ->assertSuccessful();
});
