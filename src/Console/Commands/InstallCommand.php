<?php

declare(strict_types=1);

namespace DcodeStarterKit\DcodeStarterKit\Console\Commands;

use DcodeStarterKit\DcodeStarterKit\Actions\InstallDcodeStarterKitDependencies;
use DcodeStarterKit\DcodeStarterKit\Actions\InstallDcodeStarterKitFrontend;
use DcodeStarterKit\DcodeStarterKit\Actions\PublishActivityLogMigrations;
use DcodeStarterKit\DcodeStarterKit\Actions\SetupActivityLog;
use DcodeStarterKit\DcodeStarterKit\Actions\SetupComposerHooks;
use DcodeStarterKit\DcodeStarterKit\Actions\SetupDefaultUsersMigration;
use DcodeStarterKit\DcodeStarterKit\Actions\SetupFortifySanctum;
use DcodeStarterKit\DcodeStarterKit\Actions\SetupMacroServiceProvider;
use DcodeStarterKit\DcodeStarterKit\Actions\SetupSpatiePermission;
use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature = 'dcode:install';

    protected $description = 'Install the Dcode Starter Kit application dependencies.';

    public function handle(
        InstallDcodeStarterKitDependencies $installer,
        InstallDcodeStarterKitFrontend $frontend,
        SetupComposerHooks $composerHooks,
        SetupMacroServiceProvider $macroServiceProvider,
        SetupDefaultUsersMigration $usersMigration,
        SetupSpatiePermission $permissionSetup,
        PublishActivityLogMigrations $activityLogMigrations,
        SetupFortifySanctum $fortifySanctum,
        SetupActivityLog $activityLog,
    ): int {
        $failedStep = $installer->install(
            base_path(),
            function (string $output): void {
                $this->output->write($output);
            },
        );

        if ($failedStep !== null) {
            $this->newLine();
            $this->error("Dcode Starter Kit installation failed during {$failedStep}.");

            if ($failedStep === InstallDcodeStarterKitDependencies::DEVELOPMENT_STEP) {
                $this->line('Runtime dependencies were installed successfully; development dependencies were not completed.');
            }

            return self::FAILURE;
        }

        if (! $frontend->install(base_path(), function (string $output): void {
            $this->output->write($output);
        })) {
            $this->error('Dcode Starter Kit installation failed during '.InstallDcodeStarterKitFrontend::STEP.'.');

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('Dcode Starter Kit dependencies installed successfully.');

        if (! $activityLogMigrations->publish(
            base_path(),
            function (string $output): void {
                $this->output->write($output);
            },
        )) {
            $this->error('Dcode Starter Kit installation failed during '.PublishActivityLogMigrations::STEP.'.');

            return self::FAILURE;
        }

        $this->info('Activity log migrations published successfully.');

        if (! $activityLog->setup(
            base_path(),
            function (string $output): void {
                $this->output->write($output);
            },
        )) {
            $this->error('Dcode Starter Kit installation failed during '.SetupActivityLog::STEP.'.');

            return self::FAILURE;
        }

        $this->info('Activity log User integration configured successfully.');

        $this->info('Dcode Starter Kit frontend installed successfully.');

        if (! $fortifySanctum->setup(
            base_path(),
            function (string $output): void {
                $this->output->write($output);
            },
        )) {
            $this->error('Dcode Starter Kit installation failed during '.SetupFortifySanctum::STEP.'.');

            return self::FAILURE;
        }

        $this->info('Fortify and Sanctum configured successfully.');

        if (! $macroServiceProvider->setup(
            base_path(),
            function (string $output): void {
                $this->output->write($output);
            },
        )) {
            $this->error('Dcode Starter Kit installation failed during '.SetupMacroServiceProvider::STEP.'.');

            return self::FAILURE;
        }

        $this->info('MacroServiceProvider configured successfully.');

        if (! $usersMigration->setup(
            base_path(),
            function (string $output): void {
                $this->output->write($output);
            },
        )) {
            $this->error('Dcode Starter Kit installation failed during '.SetupDefaultUsersMigration::STEP.'.');

            return self::FAILURE;
        }

        $this->info('Default users migration configured successfully.');

        if (! $composerHooks->setup(
            base_path(),
            function (string $output): void {
                $this->output->write($output);
            },
        )) {
            $this->error('Dcode Starter Kit installation failed during '.SetupComposerHooks::STEP.'.');

            return self::FAILURE;
        }

        $this->info('Composer hooks configured successfully.');

        if (! $permissionSetup->setup(
            base_path(),
            function (string $output): void {
                $this->output->write($output);
            },
            fn (bool $default, string $question): bool => $this->confirm($question, $default),
            fn (string $question, string $default): string => (string) $this->ask($question, $default),
            $this->input->isInteractive(),
        )) {
            $this->error('Dcode Starter Kit installation failed during '.SetupSpatiePermission::STEP.'.');

            return self::FAILURE;
        }

        $this->info('Spatie permission configuration and migrations are ready for review.');

        return self::SUCCESS;
    }
}
