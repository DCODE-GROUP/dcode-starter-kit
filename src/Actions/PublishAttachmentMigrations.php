<?php

declare(strict_types=1);

namespace DcodeStarterKit\DcodeStarterKit\Actions;

use Closure;
use RuntimeException;
use Symfony\Component\Process\Process;

class PublishAttachmentMigrations
{
    public const string STEP = 'attachment migration publishing';

    /** @var array<string, string> */
    private const array MIGRATIONS = [
        'create_media_table.php' => '0001_01_01_000004_create_media_table.php',
        'create_media_categories_table.php' => '0001_01_01_000005_create_media_categories_table.php',
        'create_annotations_table.php' => '0001_01_01_000006_create_annotations_table.php',
    ];

    public function __construct(?Closure $processRunner = null)
    {
        $this->processRunner = $processRunner ?? $this->runArtisanProcess(...);
    }

    /** @param callable(string): void $output */
    public function publish(string $workingDirectory, callable $output): bool
    {
        try {
            $missing = $this->missingMigrations($workingDirectory);

            if ($missing === []) {
                return true;
            }

            if (! ($this->processRunner)(['php', 'artisan', 'vendor:publish', '--tag=laravel-attachments-migrations'], $workingDirectory, $output)) {
                throw new RuntimeException('Unable to publish the Laravel Attachments migrations.');
            }

            foreach ($missing as $suffix => $targetName) {
                $this->normalizeMigration($workingDirectory, $suffix, $targetName);
            }

            return true;
        } catch (RuntimeException $exception) {
            $output($exception->getMessage()."\n");

            return false;
        }
    }

    /** @return array<string, string> */
    private function missingMigrations(string $workingDirectory): array
    {
        $missing = [];

        foreach (self::MIGRATIONS as $suffix => $targetName) {
            if (! is_file($workingDirectory.'/database/migrations/'.$targetName)) {
                $missing[$suffix] = $targetName;
            }
        }

        return $missing;
    }

    private function normalizeMigration(string $workingDirectory, string $suffix, string $targetName): void
    {
        $directory = $workingDirectory.'/database/migrations';
        $target = $directory.'/'.$targetName;
        $published = glob($directory.'/*_'.$suffix) ?: [];

        if ($published === []) {
            $stubName = $suffix === 'create_annotations_table.php'
                ? 'create_media_annotations_table.stub.php'
                : str_replace('.php', '.stub.php', $suffix);
            $stub = $workingDirectory.'/vendor/dcodegroup/laravel-attachments-backend/database/migrations/'.$stubName;

            if (is_file($stub) && copy($stub, $target)) {
                return;
            }

            throw new RuntimeException("The attachment migration publish command completed without creating a {$suffix} migration. Review the Laravel Attachments package and application environment.");
        }

        if (count($published) > 1) {
            throw new RuntimeException("Multiple {$suffix} migrations were found; no attachment migration was renamed.");
        }

        if (is_file($target)) {
            throw new RuntimeException("The target attachment migration {$target} already exists; no migration was renamed.");
        }

        if (! rename($published[0], $target)) {
            throw new RuntimeException("Unable to rename the {$suffix} attachment migration safely.");
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
