<?php

declare(strict_types=1);

namespace DcodeStarterKit\DcodeStarterKit\Actions;

use Closure;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;
use Symfony\Component\Process\Process;

class SetupHealth
{
    public const string STEP = 'health checks setup';

    private const string ROUTE_MARKER = '// Dcode Starter Kit health check route';

    /** @var Closure(array<int, string>, string, callable(string): void): bool */
    private Closure $processRunner;

    public function __construct(?Closure $processRunner = null)
    {
        $this->processRunner = $processRunner ?? $this->runArtisanProcess(...);
    }

    /** @param callable(string): void $output */
    public function setup(string $workingDirectory, callable $output): bool
    {
        try {
            $this->publishConfiguration($workingDirectory, $output);
            $this->publishMigrations($workingDirectory, $output);
            $this->copyProvider($workingDirectory);
            $this->registerProvider($workingDirectory);
            $this->registerRoute($workingDirectory);

            $output("Health checks configured.\n");

            return true;
        } catch (RuntimeException $exception) {
            $output($exception->getMessage()."\n");

            return false;
        }
    }

    /** @param callable(string): void $output */
    private function publishConfiguration(string $workingDirectory, callable $output): void
    {
        $path = $workingDirectory.'/config/health.php';

        if (! is_file($path)
            && ! ($this->processRunner)(['php', 'artisan', 'vendor:publish', '--tag=health-config'], $workingDirectory, $output)) {
            throw new RuntimeException('Unable to publish the Laravel Health configuration.');
        }

        if (! is_file($path)) {
            throw new RuntimeException('The Laravel Health config publish command completed without creating config/health.php.');
        }
    }

    /** @param callable(string): void $output */
    private function publishMigrations(string $workingDirectory, callable $output): void
    {
        if ($this->hasHealthMigration($workingDirectory)) {
            return;
        }

        if (! ($this->processRunner)(['php', 'artisan', 'vendor:publish', '--tag=health-migrations'], $workingDirectory, $output)) {
            throw new RuntimeException('Unable to publish the Laravel Health migrations.');
        }

        if (! $this->hasHealthMigration($workingDirectory)) {
            throw new RuntimeException('The Laravel Health migration publish command completed without creating a health migration.');
        }
    }

    private function hasHealthMigration(string $workingDirectory): bool
    {
        foreach (glob($workingDirectory.'/database/migrations/*create_health_tables.php') ?: [] as $path) {
            if (is_file($path)) {
                return true;
            }
        }

        return false;
    }

    private function copyProvider(string $workingDirectory): void
    {
        $sourceDirectory = dirname(__DIR__, 2).'/resources/health';
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($sourceDirectory, FilesystemIterator::SKIP_DOTS));

        foreach ($files as $file) {
            if (! $file instanceof SplFileInfo || ! $file->isFile()) {
                continue;
            }

            $relative = ltrim(str_replace($sourceDirectory, '', $file->getPathname()), DIRECTORY_SEPARATOR);
            $target = $workingDirectory.'/'.$relative;

            if (is_file($target)) {
                if (file_get_contents($target) === file_get_contents($file->getPathname())) {
                    continue;
                }

                throw new RuntimeException("Refusing to overwrite customised {$relative}.");
            }

            if (! is_dir(dirname($target)) && ! mkdir(dirname($target), 0755, true) && ! is_dir(dirname($target))) {
                throw new RuntimeException("Unable to create the directory for {$relative}.");
            }

            if (! copy($file->getPathname(), $target)) {
                throw new RuntimeException("Unable to create {$relative}.");
            }
        }
    }

    private function registerProvider(string $workingDirectory): void
    {
        $path = $workingDirectory.'/bootstrap/providers.php';
        $contents = $this->read($path, 'bootstrap/providers.php');

        if (str_contains($contents, 'HealthServiceProvider::class')) {
            return;
        }

        $contents = preg_replace('/(return\s+\[)/', "$1\n    App\\Providers\\HealthServiceProvider::class,", $contents, 1, $count);

        if ($count !== 1 || ! is_string($contents) || file_put_contents($path, $contents) === false) {
            throw new RuntimeException('Unable to register HealthServiceProvider safely in bootstrap/providers.php.');
        }
    }

    private function registerRoute(string $workingDirectory): void
    {
        $path = $workingDirectory.'/routes/web.php';
        $contents = $this->read($path, 'routes/web.php');

        if (str_contains($contents, self::ROUTE_MARKER) || str_contains($contents, "->name('health')")) {
            return;
        }

        $imports = '';
        foreach ([
            'Spatie\\Health\\Http\\Controllers\\HealthCheckJsonResultsController',
        ] as $import) {
            if (! str_contains($contents, "use {$import};")) {
                $imports .= "use {$import};\n";
            }
        }

        if ($imports !== '') {
            $contents = preg_replace('/(<\?php\s*)/', "$1\n{$imports}", $contents, 1) ?? $contents;
        }

        $contents = rtrim($contents)."\n\n".self::ROUTE_MARKER."\nRoute::get('health', HealthCheckJsonResultsController::class)->name('health');\n";

        if (file_put_contents($path, $contents) === false) {
            throw new RuntimeException('Unable to register the health endpoint safely.');
        }
    }

    private function read(string $path, string $relative): string
    {
        if (! is_file($path)) {
            throw new RuntimeException("{$relative} was not found; health checks could not be configured safely.");
        }

        $contents = file_get_contents($path);

        if (! is_string($contents)) {
            throw new RuntimeException("Unable to read {$relative}.");
        }

        return $contents;
    }

    /** @param array<int, string> $arguments @param callable(string): void $output */
    private function runArtisanProcess(array $arguments, string $workingDirectory, callable $output): bool
    {
        $process = new Process($arguments, $workingDirectory);
        $process->setTimeout(null);
        $process->run(static function (string $type, string $buffer) use ($output): void {
            $output($buffer);
        });

        return $process->isSuccessful();
    }
}
