<?php

declare(strict_types=1);

namespace DcodeStarterKit\DcodeStarterKit\Actions;

use Closure;
use RuntimeException;
use Symfony\Component\Process\Process;

class RunPint
{
    public const string STEP = 'application formatting';

    public function __construct(?Closure $processRunner = null)
    {
        $this->processRunner = $processRunner ?? $this->runProcess(...);
    }

    /** @param callable(string): void $output */
    public function run(string $workingDirectory, callable $output): bool
    {
        try {
            if (! ($this->processRunner)(['vendor/bin/pint'], $workingDirectory, $output)) {
                throw new RuntimeException('Unable to format the application with Pint.');
            }

            return true;
        } catch (RuntimeException $exception) {
            $output($exception->getMessage()."\n");

            return false;
        }
    }

    /** @param array<int, string> $arguments @param callable(string): void $output */
    private function runProcess(array $arguments, string $workingDirectory, callable $output): bool
    {
        $process = new Process($arguments, $workingDirectory, null, null, 3600);
        $process->run(function (string $type, string $buffer) use ($output): void {
            $output($buffer);
        });

        return $process->isSuccessful();
    }

    private readonly Closure $processRunner;
}
