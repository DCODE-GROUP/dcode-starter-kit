<?php

declare(strict_types=1);

namespace DcodeStarterKit\DcodeStarterKit\Actions;

use Closure;
use Symfony\Component\Process\Process;

class InstallDcodeStarterKitDependencies
{
    public const string RUNTIME_STEP = 'runtime dependency installation';

    public const string DEVELOPMENT_STEP = 'development dependency installation';

    private const array RUNTIME_DEPENDENCIES = [
        'aws/aws-sdk-php',
        'aws/aws-sdk-php-laravel',
        'bugsnag/bugsnag-laravel',
        'dcodegroup/activity-log',
        'dcodegroup/laravel-dsg-table',
        'dcodegroup/laravel-attachments-backend',
        'dcodegroup/laravel-cloudfront-url-signer',
        'laravel/fortify',
        'laravel/sanctum',
        'laravel/slack-notification-channel',
        'league/flysystem-aws-s3-v3',
        'spatie/cpu-load-health-check',
        'spatie/laravel-health',
        'spatie/laravel-log-dumper',
        'spatie/laravel-login-link',
        'spatie/laravel-model-status',
        'spatie/laravel-permission',
        'spatie/laravel-query-builder',
        'tightenco/ziggy',
        'inertiajs/inertia-laravel',
    ];

    private const array DEVELOPMENT_DEPENDENCIES = [
        'dcode-group/composer-git-hooks',
        'larastan/larastan',
        'laravel/pail',
        'pestphp/pest',
        'pestphp/pest-plugin-laravel',
        'pestphp/pest-plugin-type-coverage',
    ];

    public function __construct(?Closure $processRunner = null)
    {
        $this->processRunner = $processRunner ?? $this->runComposerProcess(...);
    }

    /**
     * @param  callable(string): void  $output
     */
    public function install(string $workingDirectory, callable $output): ?string
    {
        if (! $this->installRuntimeDependencies($workingDirectory, $output)) {
            return self::RUNTIME_STEP;
        }

        if (! $this->installDevelopmentDependencies($workingDirectory, $output)) {
            return self::DEVELOPMENT_STEP;
        }

        return null;
    }

    /**
     * @param  callable(string): void  $output
     */
    private function installRuntimeDependencies(string $workingDirectory, callable $output): bool
    {
        return $this->runComposerRequire(self::RUNTIME_DEPENDENCIES, false, $workingDirectory, $output);
    }

    /**
     * @param  callable(string): void  $output
     */
    private function installDevelopmentDependencies(string $workingDirectory, callable $output): bool
    {
        return $this->runComposerRequire(self::DEVELOPMENT_DEPENDENCIES, true, $workingDirectory, $output);
    }

    /**
     * @param  array<int, string>  $dependencies
     * @param  callable(string): void  $output
     */
    private function runComposerRequire(array $dependencies, bool $development, string $workingDirectory, callable $output): bool
    {
        $arguments = ['composer', 'require'];

        if ($development) {
            $arguments[] = '--dev';
        }

        return ($this->processRunner)(array_merge($arguments, $dependencies), $workingDirectory, $output);
    }

    /**
     * @param  array<int, string>  $arguments
     * @param  callable(string): void  $output
     */
    private function runComposerProcess(array $arguments, string $workingDirectory, callable $output): bool
    {
        $process = new Process($arguments, $workingDirectory, null, null, 3600);
        $process->run(function (string $type, string $buffer) use ($output): void {
            $output($buffer);
        });

        return $process->isSuccessful();
    }

    private readonly Closure $processRunner;
}
