<?php

declare(strict_types=1);

namespace DcodeStarterKit\DcodeStarterKit\Actions;

use RuntimeException;

class SetupApplicationDefaults
{
    public const string STEP = 'application defaults setup';

    private const string MARKER = '// Dcode Starter Kit application defaults';

    private const string LAZY_LOADING_MARKER = 'Model::handleLazyLoadingViolationUsing';

    private const string API_LIMITER_MARKER = "RateLimiter::for('api'";

    /** @param callable(string): void $output */
    public function setup(string $workingDirectory, callable $output): bool
    {
        try {
            $this->copyInertiaConfiguration($workingDirectory);
            $this->copySettingsConfiguration($workingDirectory);
            $this->updateApplicationProvider($workingDirectory);
            $output("Application defaults configured.\n");

            return true;
        } catch (RuntimeException $exception) {
            $output($exception->getMessage()."\n");

            return false;
        }
    }

    private function copyInertiaConfiguration(string $workingDirectory): void
    {
        $source = dirname(__DIR__, 2).'/resources/inertia/config/inertia.php';
        $target = $workingDirectory.'/config/inertia.php';

        if (is_file($target)) {
            if (file_get_contents($source) !== file_get_contents($target)) {
                throw new RuntimeException('Refusing to overwrite customised config/inertia.php.');
            }

            return;
        }

        if (! is_dir(dirname($target)) && ! mkdir(dirname($target), 0755, true) && ! is_dir(dirname($target))) {
            throw new RuntimeException('Unable to create the config directory for inertia.php.');
        }

        if (! copy($source, $target)) {
            throw new RuntimeException('Unable to create config/inertia.php.');
        }
    }

    private function updateApplicationProvider(string $workingDirectory): void
    {
        $path = $workingDirectory.'/app/Providers/AppServiceProvider.php';

        if (! is_file($path)) {
            throw new RuntimeException('app/Providers/AppServiceProvider.php was not found; application defaults could not be configured safely.');
        }

        $contents = file_get_contents($path);

        if (! is_string($contents)) {
            throw new RuntimeException('Unable to read app/Providers/AppServiceProvider.php.');
        }

        if (str_contains($contents, self::MARKER)
            && str_contains($contents, self::LAZY_LOADING_MARKER)
            && str_contains($contents, self::API_LIMITER_MARKER)) {
            return;
        }

        foreach ([
            'use Exception;',
            'use Illuminate\\Cache\\RateLimiting\\Limit;',
            'use Illuminate\\Database\\Eloquent\\Model;',
            'use Illuminate\\Http\\Request;',
            'use Illuminate\\Support\\Facades\\RateLimiter;',
            'use Illuminate\\Support\\Facades\\Vite;',
        ] as $import) {
            if (! str_contains($contents, $import)) {
                $contents = preg_replace_callback(
                    '/(namespace App\\\\Providers;\s*)/',
                    fn (array $matches): string => $matches[1]."\n{$import}\n",
                    $contents,
                    1,
                ) ?? $contents;
            }
        }

        $lazyLoadingHandler = <<<'PHP'

        Model::handleLazyLoadingViolationUsing(function (Model $model, string $relation): void {
            $exception = new Exception(
                'Lazy loading: '.get_class($model)."::{$relation}"
            );

            if (app()->isProduction()) {
                report($exception);

                return;
            }

            throw $exception;
        });
PHP;

        $apiLimiter = <<<'PHP'

        RateLimiter::for('api', fn (Request $request): Limit => Limit::perMinute(60)->by($request->user()?->id ?: $request->ip()));
PHP;

        if (str_contains($contents, self::MARKER)) {
            if (! str_contains($contents, self::LAZY_LOADING_MARKER)) {
                $contents = preg_replace_callback(
                    '/(Model::preventLazyLoading\(\);)/',
                    static fn (array $matches): string => $matches[1].$lazyLoadingHandler,
                    $contents,
                    1,
                    $count,
                ) ?? $contents;

                if ($count !== 1) {
                    throw new RuntimeException('Unable to add the lazy-loading violation handler safely.');
                }
            }

            if (! str_contains($contents, self::API_LIMITER_MARKER)) {
                $contents = str_replace(self::MARKER, self::MARKER.$apiLimiter, $contents, $count);

                if ($count !== 1) {
                    throw new RuntimeException('Unable to add the API rate limiter safely.');
                }
            }
        } else {
            $defaults = <<<'PHP'

        // Dcode Starter Kit application defaults
PHP;
            $defaults .= $apiLimiter;
            $defaults .= <<<'PHP'

        Model::preventLazyLoading();
PHP;
            $defaults .= $lazyLoadingHandler;
            $defaults .= <<<'PHP'

        Vite::prefetch(3);
        Vite::useScriptTagAttributes([
            'crossorigin' => 'anonymous',
        ]);
PHP;

            $contents = preg_replace_callback(
                '/(public function boot\(\)\s*(?::\s*void\s*)?\{)/',
                static fn (array $matches): string => $matches[1].$defaults,
                $contents,
                1,
                $count,
            ) ?? $contents;

            if ($count !== 1) {
                throw new RuntimeException('Unable to add the application defaults to AppServiceProvider safely.');
            }
        }

        if (file_put_contents($path, $contents) === false) {
            throw new RuntimeException('Unable to update app/Providers/AppServiceProvider.php.');
        }
    }

    private function copySettingsConfiguration(string $workingDirectory): void
    {
        $source = dirname(__DIR__, 2).'/resources/settings/config/settings.php';
        $target = $workingDirectory.'/config/settings.php';

        if (is_file($target)) {
            if (file_get_contents($source) !== file_get_contents($target)) {
                throw new RuntimeException('Refusing to overwrite customised config/settings.php.');
            }

            return;
        }

        if (! is_dir(dirname($target)) && ! mkdir(dirname($target), 0755, true) && ! is_dir(dirname($target))) {
            throw new RuntimeException('Unable to create the config directory for settings.php.');
        }

        if (! copy($source, $target)) {
            throw new RuntimeException('Unable to create config/settings.php.');
        }
    }
}
