<?php

declare(strict_types=1);

namespace DcodeStarterKit\DcodeStarterKit\Actions;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;

class SetupFortifySanctum
{
    public const string STEP = 'Fortify and Sanctum setup';

    public function setup(string $workingDirectory, callable $output): bool
    {
        try {
            $this->copyScaffold($workingDirectory);
            $this->registerProvider($workingDirectory);
            $this->updateUserModel($workingDirectory);

            $output("Fortify and Sanctum foundation configured.\n");

            return true;
        } catch (RuntimeException $exception) {
            $output($exception->getMessage()."\n");

            return false;
        }
    }

    private function copyScaffold(string $workingDirectory): void
    {
        $sourceDirectory = dirname(__DIR__, 2).'/resources/fortify';
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($sourceDirectory, RecursiveDirectoryIterator::SKIP_DOTS));

        foreach ($files as $file) {
            if (! $file instanceof SplFileInfo || ! $file->isFile()) {
                continue;
            }

            $relative = ltrim(str_replace($sourceDirectory, '', $file->getPathname()), DIRECTORY_SEPARATOR);
            $target = $workingDirectory.'/'.$relative;

            if (is_file($target)) {
                if (file_get_contents($target) !== file_get_contents($file->getPathname())) {
                    throw new RuntimeException("Refusing to overwrite customised {$relative}. Review it and merge the Fortify/Sanctum setup manually.");
                }

                continue;
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

        if (! is_file($path)) {
            throw new RuntimeException('bootstrap/providers.php was not found; FortifyServiceProvider could not be registered safely.');
        }

        $contents = file_get_contents($path);

        if (! is_string($contents)) {
            throw new RuntimeException('Unable to read bootstrap/providers.php.');
        }

        if (str_contains($contents, 'FortifyServiceProvider::class')) {
            return;
        }

        $contents = preg_replace('/(return\s+\[)/', "$1\n    App\\Providers\\FortifyServiceProvider::class,", $contents, 1, $count);

        if ($count !== 1 || ! is_string($contents) || file_put_contents($path, $contents) === false) {
            throw new RuntimeException('Unable to register FortifyServiceProvider safely in bootstrap/providers.php.');
        }
    }

    private function updateUserModel(string $workingDirectory): void
    {
        $path = $workingDirectory.'/app/Models/User.php';

        if (! is_file($path)) {
            throw new RuntimeException('app/Models/User.php was not found; Fortify and Sanctum could not be configured safely.');
        }

        $contents = file_get_contents($path);

        if (! is_string($contents)) {
            throw new RuntimeException('Unable to read app/Models/User.php.');
        }

        foreach ([
            'use Laravel\\Sanctum\\HasApiTokens;',
            'use Laravel\\Fortify\\TwoFactorAuthenticatable;',
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

        if (! str_contains($contents, 'use HasApiTokens;') || ! str_contains($contents, 'use TwoFactorAuthenticatable;')) {
            $contents = preg_replace(
                '/(class\s+User\s+extends\s+[^\{]+\{)/',
                "$1\n    use HasApiTokens;\n    use TwoFactorAuthenticatable;",
                $contents,
                1,
                $count,
            ) ?? $contents;

            if ($count !== 1) {
                throw new RuntimeException('Unable to add Fortify and Sanctum traits to app/Models/User.php safely.');
            }
        }

        if (! str_contains($contents, "'first_name'")) {
            $fillableFields = "\n        'first_name',\n        'middle_name',\n        'last_name',\n        'active',";
            $contents = preg_replace(
                '/(protected\s+\$fillable\s*=\s*\[)/',
                "$1{$fillableFields}",
                $contents,
                1,
                $count,
            ) ?? $contents;

            if ($count !== 1) {
                $contents = preg_replace(
                    '/(\#\[Fillable\(\s*\[)/',
                    "$1{$fillableFields}",
                    $contents,
                    1,
                    $count,
                ) ?? $contents;
            }

            if ($count !== 1) {
                throw new RuntimeException('Unable to add starter user fields to app/Models/User.php safely.');
            }
        }

        if (file_put_contents($path, $contents) === false) {
            throw new RuntimeException('Unable to update app/Models/User.php.');
        }
    }
}
