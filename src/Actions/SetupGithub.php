<?php

declare(strict_types=1);

namespace DcodeStarterKit\DcodeStarterKit\Actions;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;

class SetupGithub
{
    public const string STEP = 'GitHub workflow setup';

    /** @param callable(string): void $output */
    public function setup(string $workingDirectory, callable $output): bool
    {
        try {
            $this->copyScaffold($workingDirectory);
            $output("GitHub workflows and contribution templates configured.\n");

            return true;
        } catch (RuntimeException $exception) {
            $output($exception->getMessage()."\n");

            return false;
        }
    }

    private function copyScaffold(string $workingDirectory): void
    {
        $sourceDirectory = dirname(__DIR__, 2).'/resources/github';
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($sourceDirectory, FilesystemIterator::SKIP_DOTS));

        foreach ($files as $file) {
            if (! $file instanceof SplFileInfo || ! $file->isFile()) {
                continue;
            }

            $relative = ltrim(str_replace($sourceDirectory, '', $file->getPathname()), DIRECTORY_SEPARATOR);
            $target = $workingDirectory.'/.github/'.$relative;

            if (is_file($target)) {
                if (file_get_contents($target) === file_get_contents($file->getPathname())) {
                    continue;
                }

                throw new RuntimeException("Refusing to overwrite customised .github/{$relative}. Review it and merge the starter workflow or template manually.");
            }

            if (! is_dir(dirname($target)) && ! mkdir(dirname($target), 0755, true) && ! is_dir(dirname($target))) {
                throw new RuntimeException("Unable to create the directory for .github/{$relative}.");
            }

            if (! copy($file->getPathname(), $target)) {
                throw new RuntimeException("Unable to create .github/{$relative}.");
            }
        }
    }
}
