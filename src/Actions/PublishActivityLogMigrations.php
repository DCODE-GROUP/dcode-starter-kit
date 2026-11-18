<?php

declare(strict_types=1);

namespace DcodeStarterKit\DcodeStarterKit\Actions;

use Closure;
use RuntimeException;
use Symfony\Component\Process\Process;

class PublishActivityLogMigrations
{
    public const string STEP = 'activity log migration publishing';

    private const string MIGRATION_FILENAME = '0001_01_01_000003_create_activity_logs_tables.php';

    public function __construct(?Closure $processRunner = null)
    {
        $this->processRunner = $processRunner ?? $this->runArtisanProcess(...);
    }

    /** @param callable(string): void $output */
    public function publish(string $workingDirectory, callable $output): bool
    {
        try {
            if ($this->hasPublishedMigration($workingDirectory)) {
                return true;
            }

            if (! ($this->processRunner)(['php', 'artisan', 'vendor:publish', '--tag=activity-log-migrations'], $workingDirectory, $output)) {
                throw new RuntimeException('Unable to publish the activity log migrations.');
            }

            $this->normalizeMigrationFilename($workingDirectory);

            return true;
        } catch (RuntimeException $exception) {
            $output($exception->getMessage()."\n");

            return false;
        }
    }

    private function hasPublishedMigration(string $workingDirectory): bool
    {
        return count(glob($workingDirectory.'/database/migrations/*_create_activity_logs_tables.php') ?: []) > 0;
    }

    private function normalizeMigrationFilename(string $workingDirectory): void
    {
        $directory = $workingDirectory.'/database/migrations';
        $target = $directory.'/'.self::MIGRATION_FILENAME;
        $published = glob($directory.'/*_create_activity_logs_tables.php') ?: [];

        if ($published === []) {
            throw new RuntimeException('The activity log migration publish command completed without creating a migration. Review the activity-log package and application environment.');
        }

        if (count($published) > 1) {
            throw new RuntimeException('Multiple activity log migrations were found; no migration was renamed.');
        }

        if ($published[0] === $target) {
            return;
        }

        if (is_file($target)) {
            throw new RuntimeException("The target activity log migration {$target} already exists; no migration was renamed.");
        }

        if (! rename($published[0], $target)) {
            throw new RuntimeException('Unable to rename the activity log migration safely.');
        }
    }

    /** @param array<int, string> $arguments @param callable(string): void $output */
    private function runArtisanProcess(array $arguments, string $workingDirectory, callable $output): bool
    {
        $process = new Process($arguments, $workingDirectory, null, null, 3600);
        $process->run(function (string $type, string $buffer) use ($output): void {
            $output($buffer);
        });

        return $process->isSuccessful();
    }

    private readonly Closure $processRunner;
}
