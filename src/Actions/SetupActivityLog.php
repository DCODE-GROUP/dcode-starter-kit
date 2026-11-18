<?php

declare(strict_types=1);

namespace DcodeStarterKit\DcodeStarterKit\Actions;

use Closure;
use RuntimeException;
use Symfony\Component\Process\Process;

class SetupActivityLog
{
    public const string STEP = 'activity log setup';

    public function __construct(?Closure $processRunner = null)
    {
        $this->processRunner = $processRunner ?? $this->runArtisanProcess(...);
    }

    /** @param callable(string): void $output */
    public function setup(string $workingDirectory, callable $output): bool
    {
        try {
            $this->publishConfig($workingDirectory, $output);
            $this->updateUserModel($workingDirectory);

            $output("Activity log configuration and User integration configured.\n");

            return true;
        } catch (RuntimeException $exception) {
            $output($exception->getMessage()."\n");

            return false;
        }
    }

    /** @param callable(string): void $output */
    private function publishConfig(string $workingDirectory, callable $output): void
    {
        $path = $workingDirectory.'/config/activity-log.php';

        if (is_file($path)) {
            return;
        }

        if (! ($this->processRunner)(['php', 'artisan', 'vendor:publish', '--tag=activity-log-config'], $workingDirectory, $output)) {
            throw new RuntimeException('Unable to publish the activity-log configuration.');
        }

        if (! is_file($path)) {
            throw new RuntimeException('The activity-log configuration publish command completed without creating config/activity-log.php. Review the activity-log package and application environment.');
        }
    }

    private function updateUserModel(string $workingDirectory): void
    {
        $path = $workingDirectory.'/app/Models/User.php';

        if (! is_file($path)) {
            throw new RuntimeException('app/Models/User.php was not found; activity-log User integration could not be configured safely.');
        }

        $contents = file_get_contents($path);

        if (! is_string($contents)) {
            throw new RuntimeException('Unable to read app/Models/User.php.');
        }

        foreach ([
            'use Dcodegroup\\ActivityLog\\Contracts\\HasActivityUser;',
            'use Dcodegroup\\ActivityLog\\Support\\Traits\\ActivityLoggable;',
        ] as $import) {
            if (! str_contains($contents, $import)) {
                $contents = preg_replace_callback(
                    '/(namespace App\\\\Models;\s*)/',
                    fn (array $matches): string => $matches[1]."\n{$import}\n",
                    $contents,
                    1,
                ) ?? $contents;
            }
        }

        if (! str_contains($contents, 'implements HasActivityUser')) {
            $contents = preg_replace_callback(
                '/(class\s+User\s+extends\s+[^\{]+)(\{)/',
                function (array $matches): string {
                    $declaration = rtrim($matches[1]);

                    if (str_contains($declaration, ' implements ')) {
                        return $declaration.', HasActivityUser '.$matches[2];
                    }

                    return $declaration.' implements HasActivityUser '.$matches[2];
                },
                $contents,
                1,
                $count,
            ) ?? $contents;

            if ($count !== 1) {
                throw new RuntimeException('Unable to add HasActivityUser to app/Models/User.php safely.');
            }
        }

        if (! str_contains($contents, 'use ActivityLoggable;')) {
            $contents = preg_replace(
                '/(class\s+User\s+extends\s+[^\{]+\{)/',
                "$1\n    use ActivityLoggable;",
                $contents,
                1,
                $count,
            ) ?? $contents;

            if ($count !== 1) {
                throw new RuntimeException('Unable to add ActivityLoggable to app/Models/User.php safely.');
            }
        }

        if (! str_contains($contents, 'function getActivityLogUserName')) {
            $methods = <<<'PHP'

    public function getActivityLogUserName(): string
    {
        $name = trim(implode(' ', array_filter([
            $this->first_name ?? null,
            $this->middle_name ?? null,
            $this->last_name ?? null,
        ])));

        return $name !== '' ? $name : (string) ($this->name ?? $this->email);
    }

    public function getActivityLogEmail(): string
    {
        return (string) $this->email;
    }
PHP;
            $closingBrace = strrpos($contents, '}');

            if ($closingBrace === false) {
                throw new RuntimeException('Unable to add activity-log User methods safely.');
            }

            $contents = substr($contents, 0, $closingBrace).$methods."\n".substr($contents, $closingBrace);
        }

        if (file_put_contents($path, $contents) === false) {
            throw new RuntimeException('Unable to update app/Models/User.php for activity logging.');
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
