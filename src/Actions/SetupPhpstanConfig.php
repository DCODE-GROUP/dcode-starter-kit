<?php

declare(strict_types=1);

namespace DcodeStarterKit\DcodeStarterKit\Actions;

use RuntimeException;

class SetupPhpstanConfig
{
    public const string STEP = 'PHPStan configuration setup';

    /** @param callable(string): void $output */
    public function setup(string $workingDirectory, callable $output): bool
    {
        try {
            $this->copyConfig($workingDirectory);
            $output("PHPStan configuration created.\n");

            return true;
        } catch (RuntimeException $exception) {
            $output($exception->getMessage()."\n");

            return false;
        }
    }

    private function copyConfig(string $workingDirectory): void
    {
        $source = dirname(__DIR__, 2).'/resources/phpstan.neon.dist';
        $target = $workingDirectory.'/phpstan.neon.dist';

        if (! is_file($source)) {
            throw new RuntimeException('The host application PHPStan configuration could not be found.');
        }

        if (is_file($target)) {
            if (file_get_contents($target) === file_get_contents($source)) {
                return;
            }

            throw new RuntimeException('Refusing to overwrite customised phpstan.neon.dist. Review it before rerunning dcode:install.');
        }

        if (! copy($source, $target)) {
            throw new RuntimeException('Unable to create phpstan.neon.dist.');
        }
    }
}
