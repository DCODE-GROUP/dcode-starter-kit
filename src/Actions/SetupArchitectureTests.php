<?php

declare(strict_types=1);

namespace DcodeStarterKit\DcodeStarterKit\Actions;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;

class SetupArchitectureTests
{
    public const string STEP = 'architecture tests setup';

    /** @param callable(string): void $output */
    public function setup(string $workingDirectory, callable $output): bool
    {
        try {
            $this->copyPhpunitConfiguration($workingDirectory);
            $this->copyTests($workingDirectory);
            $output("Architecture tests configured.\n");

            return true;
        } catch (RuntimeException $exception) {
            $output($exception->getMessage()."\n");

            return false;
        }
    }

    private function copyPhpunitConfiguration(string $workingDirectory): void
    {
        $this->copyFile(
            dirname(__DIR__, 2).'/resources/phpunit.xml.dist',
            $workingDirectory.'/phpunit.xml.dist',
            'phpunit.xml.dist',
        );
    }

    private function copyTests(string $workingDirectory): void
    {
        $sourceDirectory = dirname(__DIR__, 2).'/resources/tests';
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($sourceDirectory, FilesystemIterator::SKIP_DOTS));

        foreach ($files as $file) {
            if (! $file instanceof SplFileInfo || ! $file->isFile()) {
                continue;
            }

            $relative = ltrim(str_replace($sourceDirectory, '', $file->getPathname()), DIRECTORY_SEPARATOR);
            $this->copyFile($file->getPathname(), $workingDirectory.'/tests/'.$relative, 'tests/'.$relative);
        }
    }

    private function copyFile(string $source, string $target, string $relative): void
    {
        if (is_file($target)) {
            if (file_get_contents($target) === file_get_contents($source)) {
                return;
            }

            throw new RuntimeException("Refusing to overwrite customised {$relative}.");
        }

        if (! is_dir(dirname($target)) && ! mkdir(dirname($target), 0755, true) && ! is_dir(dirname($target))) {
            throw new RuntimeException("Unable to create the directory for {$relative}.");
        }

        if (! copy($source, $target)) {
            throw new RuntimeException("Unable to create {$relative}.");
        }
    }
}
