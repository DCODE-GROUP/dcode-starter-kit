<?php

declare(strict_types=1);

namespace DcodeStarterKit\DcodeStarterKit\Console\Commands;

use DcodeStarterKit\DcodeStarterKit\Actions\InstallDcodeStarterKitDependencies;
use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature = 'dcode:install';

    protected $description = 'Install the Dcode Starter Kit application dependencies.';

    public function handle(InstallDcodeStarterKitDependencies $installer): int
    {
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

        return self::SUCCESS;
    }
}
