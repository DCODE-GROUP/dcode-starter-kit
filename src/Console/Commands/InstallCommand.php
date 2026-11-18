<?php

declare(strict_types=1);

namespace DcodeStarterKit\DcodeStarterKit\Console\Commands;

use DcodeStarterKit\DcodeStarterKit\Actions\InstallDcodeStarterKitDependencies;
use DcodeStarterKit\DcodeStarterKit\Actions\SetupComposerHooks;
use DcodeStarterKit\DcodeStarterKit\Actions\SetupSpatiePermission;
use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature = 'dcode:install';

    protected $description = 'Install the Dcode Starter Kit application dependencies.';

    public function handle(
        InstallDcodeStarterKitDependencies $installer,
        SetupComposerHooks $composerHooks,
        SetupSpatiePermission $permissionSetup,
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

        $this->newLine();
        $this->info('Dcode Starter Kit dependencies installed successfully.');

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
