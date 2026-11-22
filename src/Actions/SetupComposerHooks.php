<?php

declare(strict_types=1);

namespace DcodeStarterKit\DcodeStarterKit\Actions;

use Closure;
use JsonException;
use RuntimeException;
use Symfony\Component\Process\Process;

class SetupComposerHooks
{
    public const string STEP = 'Composer hooks setup';

    private const array HOOKS = [
        'config' => [
            'stop-on-failure' => [
                'pre-push',
            ],
        ],
        'pre-commit' => [
            "echo '\n\n\\033[0;36mcghooks - Pre-Commit: \\033[0m \n'",
            "echo 'Committing as: \\033[0;32m'\$(git config user.name) '('\$(git config user.email)') \\033[0m\n'",
            "echo '\n\\033[0;35mRunning Pint (PHP CS Fixer): \\033[0m\n'",
            'vendor/bin/pint --dirty',
            "echo '\n\\033[0;35mRunning ESLint: \\033[0m\n'",
            "echo '    - pnpm run lint:fix'",
            "echo '\n'",
        ],
        'pre-push' => [
            "echo '\n\n\\033[0;36mcghooks - Pre-Push: \\033[0m \n'",
            "echo '\n\\033[0;35mRunning PHPStan: \\033[0m\n'",
            'vendor/bin/phpstan --error-format=github',
            "echo '\n\\033[0;35mRunning Architecture Tests: \\033[0m\n'",
            'vendor/bin/pest --testsuite Architecture --colors -c phpunit.xml.dist',
            "echo '\n'",
        ],
        'post-merge' => [
            '.dev/post-merge',
        ],
    ];

    public function __construct(?Closure $processRunner = null)
    {
        $this->processRunner = $processRunner ?? $this->runCghooksProcess(...);
    }

    /**
     * @param  callable(string): void  $output
     */
    public function setup(string $workingDirectory, callable $output): bool
    {
        $path = $workingDirectory.'/composer.json';
        $contents = file_get_contents($path);

        if (! is_string($contents)) {
            $output("Unable to read the application's composer.json; no Composer hooks were configured.\n");

            return false;
        }

        try {
            $decoded = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);

            if (! is_array($decoded)) {
                throw new JsonException('Composer metadata must be a JSON object.');
            }

            /** @var array<string, mixed> $composer */
            $composer = $decoded;
            $composer['extra'] = is_array($composer['extra'] ?? null) ? $composer['extra'] : [];
            $composer['extra']['hooks'] = self::HOOKS;
            $updated = json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n";
        } catch (JsonException $exception) {
            $output("Unable to parse the application's composer.json; no Composer hooks were configured.\n");

            return false;
        }

        if ($updated === $contents) {
            return true;
        }

        try {
            if (! $this->writeAtomically($path, $updated)) {
                throw new RuntimeException("Unable to update the application's composer.json.");
            }
        } catch (RuntimeException $exception) {
            $output($exception->getMessage()."\n");

            return false;
        }

        if (! ($this->processRunner)(['./vendor/bin/cghooks', 'update'], $workingDirectory, $output)) {
            $output("Unable to update the installed Composer hooks.\n");

            return false;
        }

        return true;
    }

    private function writeAtomically(string $path, string $contents): bool
    {
        $temporaryPath = $path.'.tmp-'.bin2hex(random_bytes(8));

        if (file_put_contents($temporaryPath, $contents) === false || ! rename($temporaryPath, $path)) {
            if (is_file($temporaryPath)) {
                unlink($temporaryPath);
            }

            return false;
        }

        return true;
    }

    /** @param array<int, string> $arguments @param callable(string): void $output */
    private function runCghooksProcess(array $arguments, string $workingDirectory, callable $output): bool
    {
        $process = new Process($arguments, $workingDirectory, null, null, 3600);
        $process->run(function (string $type, string $buffer) use ($output): void {
            $output($buffer);
        });

        return $process->isSuccessful();
    }

    private readonly Closure $processRunner;
}
