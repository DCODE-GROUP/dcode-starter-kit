<?php

declare(strict_types=1);

namespace DcodeStarterKit\DcodeStarterKit\Actions;

use RuntimeException;

class SetupLoginLink
{
    public const string STEP = 'local login-link setup';

    /** @param callable(string): void $output */
    public function setup(string $workingDirectory, callable $output): bool
    {
        try {
            $this->copyConfiguration($workingDirectory);
            $output("Local login-link configured.\n");

            return true;
        } catch (RuntimeException $exception) {
            $output($exception->getMessage()."\n");

            return false;
        }
    }

    private function copyConfiguration(string $workingDirectory): void
    {
        $source = dirname(__DIR__, 2).'/resources/login-link/config/login-link.php';
        $target = $workingDirectory.'/config/login-link.php';

        if (! is_file($source)) {
            throw new RuntimeException('The starter login-link configuration source is missing.');
        }

        if (is_file($target)) {
            if (file_get_contents($source) === file_get_contents($target)) {
                return;
            }

            throw new RuntimeException('Refusing to overwrite customised config/login-link.php. Review it and merge the local login-link settings manually.');
        }

        if (! is_dir(dirname($target)) && ! mkdir(dirname($target), 0755, true) && ! is_dir(dirname($target))) {
            throw new RuntimeException('Unable to create the config directory for login-link.php.');
        }

        if (! copy($source, $target)) {
            throw new RuntimeException('Unable to create config/login-link.php.');
        }
    }
}
