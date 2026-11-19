<?php

declare(strict_types=1);

namespace DcodeStarterKit\DcodeStarterKit\Actions;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;

class SetupAuditLogging
{
    public const string STEP = 'audit logging setup';

    private const string SCHEDULE_MARKER = '// Dcode Starter Kit audit log schedule';

    /** @param callable(string): void $output */
    public function setup(string $workingDirectory, callable $output): bool
    {
        try {
            $this->copyScaffold($workingDirectory);
            $this->registerSchedule($workingDirectory);

            $output("Administrative audit logging configured.\n");

            return true;
        } catch (RuntimeException $exception) {
            $output($exception->getMessage()."\n");

            return false;
        }
    }

    private function copyScaffold(string $workingDirectory): void
    {
        $sourceDirectory = dirname(__DIR__, 2).'/resources/audit';
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($sourceDirectory, FilesystemIterator::SKIP_DOTS));

        foreach ($files as $file) {
            if (! $file instanceof SplFileInfo || ! $file->isFile()) {
                continue;
            }

            $relative = ltrim(str_replace($sourceDirectory, '', $file->getPathname()), DIRECTORY_SEPARATOR);
            $target = $workingDirectory.'/'.$relative;

            if (is_file($target)) {
                if (file_get_contents($target) !== file_get_contents($file->getPathname())) {
                    throw new RuntimeException("Refusing to overwrite customised {$relative}.");
                }

                continue;
            }

            if (! is_dir(dirname($target)) && ! mkdir(dirname($target), 0755, true) && ! is_dir(dirname($target))) {
                throw new RuntimeException("Unable to create the directory for {$relative}.");
            }

            if (! copy($file->getPathname(), $target)) {
                throw new RuntimeException("Unable to create {$relative}.");
            }
        }
    }

    private function registerSchedule(string $workingDirectory): void
    {
        $path = $workingDirectory.'/routes/console.php';
        $contents = is_file($path) ? file_get_contents($path) : "<?php\n";

        if (! is_string($contents)) {
            throw new RuntimeException('Unable to read routes/console.php.');
        }

        if (str_contains($contents, self::SCHEDULE_MARKER)) {
            return;
        }

        $imports = '';

        foreach ([
            'App\\Console\\Commands\\AuditLogGarbageCollect',
            'Illuminate\\Support\\Facades\\Schedule',
        ] as $import) {
            if (! str_contains($contents, "use {$import};")) {
                $imports .= "use {$import};\n";
            }
        }

        if ($imports !== '') {
            $contents = preg_replace('/(<\?php\s*)/', "$1\n{$imports}", $contents, 1) ?? $contents;
        }

        $contents = rtrim($contents)."\n\n".self::SCHEDULE_MARKER."\nSchedule::command(AuditLogGarbageCollect::class)->daily();\n";

        if (file_put_contents($path, $contents) === false) {
            throw new RuntimeException('Unable to register the audit log cleanup schedule safely.');
        }
    }
}
