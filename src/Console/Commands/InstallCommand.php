<?php

declare(strict_types=1);

namespace DcodeStarterKit\DcodeStarterKit\Console\Commands;

use DcodeStarterKit\DcodeStarterKit\Actions\InstallDcodeStarterKitDependencies;
use DcodeStarterKit\DcodeStarterKit\Actions\InstallDcodeStarterKitFrontend;
use DcodeStarterKit\DcodeStarterKit\Actions\PublishActivityLogMigrations;
use DcodeStarterKit\DcodeStarterKit\Actions\PublishAttachmentMigrations;
use DcodeStarterKit\DcodeStarterKit\Actions\RunDcodeStarterKitDatabaseSetup;
use DcodeStarterKit\DcodeStarterKit\Actions\SetupActivityLog;
use DcodeStarterKit\DcodeStarterKit\Actions\SetupAttachments;
use DcodeStarterKit\DcodeStarterKit\Actions\SetupAuditLogging;
use DcodeStarterKit\DcodeStarterKit\Actions\SetupComposerHooks;
use DcodeStarterKit\DcodeStarterKit\Actions\SetupDefaultUsersMigration;
use DcodeStarterKit\DcodeStarterKit\Actions\SetupFortifySanctum;
use DcodeStarterKit\DcodeStarterKit\Actions\SetupHealth;
use DcodeStarterKit\DcodeStarterKit\Actions\SetupLoginLink;
use DcodeStarterKit\DcodeStarterKit\Actions\SetupMacroServiceProvider;
use DcodeStarterKit\DcodeStarterKit\Actions\SetupSpatiePermission;
use DcodeStarterKit\DcodeStarterKit\Actions\SetupUserManagement;
use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature = 'dcode:install {--tenancy= : Set permission teams/tenancy to true or false without prompting.}';

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
        SetupAuditLogging $auditLogging,
        PublishAttachmentMigrations $attachmentMigrations,
        SetupAttachments $attachments,
        SetupUserManagement $userManagement,
        SetupLoginLink $loginLink,
        SetupHealth $health,
        RunDcodeStarterKitDatabaseSetup $databaseSetup,
    ): int {
        $tenancyOption = $this->option('tenancy');
        $tenancy = null;

        if ($tenancyOption !== null) {
            if (is_array($tenancyOption) || (! is_string($tenancyOption) && ! is_int($tenancyOption) && ! is_bool($tenancyOption))) {
                $this->error('The --tenancy option must be true, false, yes, or no.');

                return self::FAILURE;
            }

            $tenancy = match (strtolower((string) $tenancyOption)) {
                '1', 'true', 'yes' => true,
                '0', 'false', 'no' => false,
                default => null,
            };

            if ($tenancy === null) {
                $this->error('The --tenancy option must be true, false, yes, or no.');

                return self::FAILURE;
            }
        }

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

        if (! $auditLogging->setup(
            base_path(),
            function (string $output): void {
                $this->output->write($output);
            },
        )) {
            $this->error('Dcode Starter Kit installation failed during '.SetupAuditLogging::STEP.'.');

            return self::FAILURE;
        }

        $this->info('Administrative audit logging configured successfully.');

        if (! $attachmentMigrations->publish(
            base_path(),
            function (string $output): void {
                $this->output->write($output);
            },
        )) {
            $this->error('Dcode Starter Kit installation failed during '.PublishAttachmentMigrations::STEP.'.');

            return self::FAILURE;
        }

        $this->info('Attachment migrations published successfully.');

        if (! $attachments->setup(
            base_path(),
            function (string $output): void {
                $this->output->write($output);
            },
        )) {
            $this->error('Dcode Starter Kit installation failed during '.SetupAttachments::STEP.'.');

            return self::FAILURE;
        }

        $this->info('Laravel Attachments configured successfully.');

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
            $tenancy,
        )) {
            $this->error('Dcode Starter Kit installation failed during '.SetupSpatiePermission::STEP.'.');

            return self::FAILURE;
        }

        $this->info('Spatie permission configuration and migrations are ready for review.');

        if (! $userManagement->setup(
            base_path(),
            function (string $output): void {
                $this->output->write($output);
            },
        )) {
            $this->error('Dcode Starter Kit installation failed during '.SetupUserManagement::STEP.'.');

            return self::FAILURE;
        }

        $this->info('User and role management configured successfully.');

        if (! $loginLink->setup(
            base_path(),
            function (string $output): void {
                $this->output->write($output);
            },
        )) {
            $this->error('Dcode Starter Kit installation failed during '.SetupLoginLink::STEP.'.');

            return self::FAILURE;
        }

        $this->info('Local login-link configured successfully.');

        if (! $health->setup(
            base_path(),
            function (string $output): void {
                $this->output->write($output);
            },
        )) {
            $this->error('Dcode Starter Kit installation failed during '.SetupHealth::STEP.'.');

            return self::FAILURE;
        }

        $this->info('Health checks configured successfully.');

        if (! $frontend->install(base_path(), function (string $output): void {
            $this->output->write($output);
        })) {
            $this->error('Dcode Starter Kit installation failed during '.InstallDcodeStarterKitFrontend::STEP.'.');

            return self::FAILURE;
        }

        $this->info('Dcode Starter Kit frontend installed successfully.');

        if (! $databaseSetup->run(
            base_path(),
            function (string $output): void {
                $this->output->write($output);
            },
        )) {
            $this->error('Dcode Starter Kit installation failed during '.RunDcodeStarterKitDatabaseSetup::STEP.'.');

            return self::FAILURE;
        }

        $this->info('Database migrations and starter seeding completed successfully.');

        return self::SUCCESS;
    }
}
