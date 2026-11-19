<?php

declare(strict_types=1);

namespace DcodeStarterKit\DcodeStarterKit\Actions;

use Closure;
use RuntimeException;
use Symfony\Component\Process\Process;

class SetupBugsnag
{
    public const string STEP = 'Bugsnag setup';

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
            $this->registerProvider($workingDirectory);
            $this->configureLogging($workingDirectory);
            $this->configureOomBootstrapper($workingDirectory);

            $output("Bugsnag configured.\n");

            return true;
        } catch (RuntimeException $exception) {
            $output($exception->getMessage()."\n");

            return false;
        }
    }

    /** @param callable(string): void $output */
    private function publishConfiguration(string $workingDirectory, callable $output): void
    {
        $path = $workingDirectory.'/config/bugsnag.php';

        if (! is_file($path)) {
            ($this->processRunner)([
                'php',
                'artisan',
                'vendor:publish',
                '--provider=Bugsnag\\BugsnagLaravel\\BugsnagServiceProvider',
            ], $workingDirectory, $output);
        }

        if (is_file($path)) {
            return;
        }

        $vendorPath = $workingDirectory.'/vendor/bugsnag/bugsnag-laravel/config/bugsnag.php';

        if (! is_file($vendorPath) || ! copy($vendorPath, $path)) {
            throw new RuntimeException('The Bugsnag config publish command completed without creating config/bugsnag.php, and the dependency config could not be copied from vendor/bugsnag/bugsnag-laravel/config/bugsnag.php.');
        }
    }

    private function registerProvider(string $workingDirectory): void
    {
        $path = $workingDirectory.'/bootstrap/providers.php';
        $contents = $this->read($path, 'bootstrap/providers.php');

        if (str_contains($contents, 'BugsnagServiceProvider::class')) {
            return;
        }

        $imports = "use Bugsnag\\BugsnagLaravel\\BugsnagServiceProvider;\n";
        $contents = preg_replace('/(<\?php\s*)/', "$1\n{$imports}", $contents, 1) ?? $contents;
        $contents = preg_replace('/(return\s+\[)/', "$1\n    BugsnagServiceProvider::class,", $contents, 1, $count);

        if ($count !== 1 || file_put_contents($path, $contents) === false) {
            throw new RuntimeException('Unable to register BugsnagServiceProvider safely in bootstrap/providers.php.');
        }
    }

    private function configureLogging(string $workingDirectory): void
    {
        $path = $workingDirectory.'/config/logging.php';
        $contents = $this->read($path, 'config/logging.php');

        if (! str_contains($contents, "'driver' => 'bugsnag'")) {
            $needle = "\n    ],\n\n];";
            $replacement = "\n\n        'bugsnag' => [\n            'driver' => 'bugsnag',\n        ],\n\n    ],\n\n];";
            $contents = str_replace($needle, $replacement, $contents, $count);

            if ($count !== 1) {
                throw new RuntimeException('Unable to add the Bugsnag logging channel safely. Review config/logging.php and add a channel using the bugsnag driver manually.');
            }
        }

        if (str_contains($contents, "env('LOG_STACK', 'single')")) {
            $contents = str_replace("env('LOG_STACK', 'single')", "env('LOG_STACK', 'daily,bugsnag')", $contents);
        }

        if (file_put_contents($path, $contents) === false) {
            throw new RuntimeException('Unable to configure the Bugsnag logging channel.');
        }
    }

    private function configureOomBootstrapper(string $workingDirectory): void
    {
        $path = $workingDirectory.'/bootstrap/app.php';
        $contents = $this->read($path, 'bootstrap/app.php');

        if (str_contains($contents, 'OomBootstrapper')) {
            return;
        }

        if (! str_contains($contents, 'Application::configure')) {
            throw new RuntimeException('Unable to configure the Bugsnag OOM bootstrapper safely in bootstrap/app.php.');
        }

        $contents = preg_replace(
            '/(<\?php\s*)/',
            "$1\nuse Bugsnag\\BugsnagLaravel\\OomBootstrapper;\n\n(new OomBootstrapper)->bootstrap();\n",
            $contents,
            1,
        ) ?? $contents;

        if (file_put_contents($path, $contents) === false) {
            throw new RuntimeException('Unable to configure the Bugsnag OOM bootstrapper.');
        }
    }

    private function read(string $path, string $relative): string
    {
        if (! is_file($path)) {
            throw new RuntimeException("{$relative} was not found; Bugsnag could not be configured safely.");
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
