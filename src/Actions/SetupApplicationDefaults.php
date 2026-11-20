<?php

declare(strict_types=1);

namespace DcodeStarterKit\DcodeStarterKit\Actions;

use RuntimeException;

class SetupApplicationDefaults
{
    public const string STEP = 'application defaults setup';

    private const string MARKER = '// Dcode Starter Kit application defaults';

    private const string LAZY_LOADING_MARKER = 'Model::handleLazyLoadingViolationUsing';

    /** @param callable(string): void $output */
    public function setup(string $workingDirectory, callable $output): bool
    {
        try {
            $this->updateApplicationProvider($workingDirectory);
            $output("Application defaults configured.\n");

            return true;
        } catch (RuntimeException $exception) {
            $output($exception->getMessage()."\n");

            return false;
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

        if (str_contains($contents, self::MARKER) && str_contains($contents, self::LAZY_LOADING_MARKER)) {
            return;
        }

        foreach ([
            'use Exception;',
            'use Illuminate\\Database\\Eloquent\\Model;',
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

        if (str_contains($contents, self::MARKER)) {
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
        } else {
            $defaults = <<<'PHP'

        // Dcode Starter Kit application defaults
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
}
