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

class SetupAttachments
{
    public const string STEP = 'Laravel Attachments setup';

    private const string ROUTE_MARKER = '// Dcode Starter Kit attachment routes';

    public function __construct(?Closure $processRunner = null)
    {
        $this->processRunner = $processRunner ?? $this->runArtisanProcess(...);
    }

    /** @param callable(string): void $output */
    public function setup(string $workingDirectory, callable $output): bool
    {
        try {
            $this->publishConfiguration($workingDirectory, $output);
            $this->copyScaffold($workingDirectory);
            $this->registerProvider($workingDirectory);
            $this->updateUserModel($workingDirectory);
            $this->registerRoutes($workingDirectory);

            $output("Laravel Attachments configured.\n");

            return true;
        } catch (RuntimeException $exception) {
            $output($exception->getMessage()."\n");

            return false;
        }
    }

    /** @param callable(string): void $output */
    private function publishConfiguration(string $workingDirectory, callable $output): void
    {
        $configPath = $workingDirectory.'/config/attachments.php';

        if (! is_file($configPath)
            && ! ($this->processRunner)(['php', 'artisan', 'vendor:publish', '--tag=laravel-attachments-config'], $workingDirectory, $output)) {
            throw new RuntimeException('Unable to publish the Laravel Attachments configuration.');
        }

        if (! is_file($configPath)) {
            throw new RuntimeException('The Laravel Attachments config publish command completed without creating config/attachments.php.');
        }

        $mediaConfigPath = $workingDirectory.'/config/media-library.php';

        if (! is_file($mediaConfigPath)
            && ! ($this->processRunner)(['php', 'artisan', 'vendor:publish', '--tag=medialibrary-config'], $workingDirectory, $output)) {
            throw new RuntimeException('Unable to publish the Spatie Media Library configuration.');
        }

        if (! is_file($mediaConfigPath)) {
            throw new RuntimeException('The Spatie Media Library config publish command completed without creating config/media-library.php.');
        }

        $contents = file_get_contents($mediaConfigPath);

        if (! is_string($contents)) {
            throw new RuntimeException('Unable to read config/media-library.php.');
        }

        $desiredMediaModel = 'Dcodegroup\\LaravelAttachments\\Models\\Media::class';
        $desiredPathGenerator = 'Dcodegroup\\LaravelAttachments\\MediaLibrary\\MediaPathGenerator::class';

        if (str_contains($contents, $desiredMediaModel) && str_contains($contents, $desiredPathGenerator)) {
            return;
        }

        if (preg_match("/'media_model'\\s*=>\\s*(.+),/", $contents, $mediaModelMatch) !== 1
            || preg_match("/'path_generator'\\s*=>\\s*(.+),/", $contents, $pathGeneratorMatch) !== 1) {
            throw new RuntimeException('Unable to configure config/media-library.php safely; review the media_model and path_generator entries manually.');
        }

        if (! in_array(trim($mediaModelMatch[1]), [
            'Media::class',
            'Dcodegroup\\LaravelAttachments\\Models\\Media::class',
        ], true)
            || ! in_array(trim($pathGeneratorMatch[1]), [
                'DefaultPathGenerator::class',
                'MediaPathGenerator::class',
                'Dcodegroup\\LaravelAttachments\\MediaLibrary\\MediaPathGenerator::class',
            ], true)) {
            throw new RuntimeException('Refusing to overwrite customised config/media-library.php; set media_model to Dcodegroup\\LaravelAttachments\\Models\\Media::class and path_generator to Dcodegroup\\LaravelAttachments\\MediaLibrary\\MediaPathGenerator::class, then rerun.');
        }

        $contents = str_replace(
            [
                'use Spatie\\MediaLibrary\\MediaCollections\\Models\\Media;',
                'use Spatie\\MediaLibrary\\Support\\PathGenerator\\DefaultPathGenerator;',
                "'media_model' => Media::class,",
                "'path_generator' => DefaultPathGenerator::class,",
            ],
            [
                'use Dcodegroup\\LaravelAttachments\\Models\\Media;',
                'use Dcodegroup\\LaravelAttachments\\MediaLibrary\\MediaPathGenerator;',
                "'media_model' => Media::class,",
                "'path_generator' => MediaPathGenerator::class,",
            ],
            $contents,
        );

        if (file_put_contents($mediaConfigPath, $contents) === false) {
            throw new RuntimeException('Unable to configure config/media-library.php for Laravel Attachments.');
        }
    }

    private function copyScaffold(string $workingDirectory): void
    {
        $sourceDirectory = dirname(__DIR__, 2).'/resources/attachments';
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($sourceDirectory, FilesystemIterator::SKIP_DOTS));

        foreach ($files as $file) {
            if (! $file instanceof SplFileInfo || ! $file->isFile()) {
                continue;
            }

            $relative = ltrim(str_replace($sourceDirectory, '', $file->getPathname()), DIRECTORY_SEPARATOR);
            $target = $workingDirectory.'/'.$relative;

            if (is_file($target)) {
                if (file_get_contents($target) !== file_get_contents($file->getPathname())) {
                    throw new RuntimeException("Refusing to overwrite customised {$relative}.");
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
            throw new RuntimeException('bootstrap/providers.php was not found; AttachmentServiceProvider could not be registered safely.');
        }

        $contents = file_get_contents($path);

        if (! is_string($contents)) {
            throw new RuntimeException('Unable to read bootstrap/providers.php.');
        }

        if (str_contains($contents, 'AttachmentServiceProvider::class')) {
            return;
        }

        $contents = preg_replace('/(return\s+\[)/', "$1\n    App\\Providers\\AttachmentServiceProvider::class,", $contents, 1, $count);

        if ($count !== 1 || ! is_string($contents) || file_put_contents($path, $contents) === false) {
            throw new RuntimeException('Unable to register AttachmentServiceProvider safely in bootstrap/providers.php.');
        }
    }

    private function updateUserModel(string $workingDirectory): void
    {
        $path = $workingDirectory.'/app/Models/User.php';
        $contents = is_file($path) ? file_get_contents($path) : false;

        if (! is_string($contents)) {
            throw new RuntimeException('Unable to read app/Models/User.php; Laravel Attachments could not be configured safely.');
        }

        $import = 'use Dcodegroup\\LaravelAttachments\\Contracts\\HasMediaUser;';

        if (! str_contains($contents, $import)) {
            $contents = preg_replace_callback(
                '/(namespace App\\\\Models;\s*)/',
                fn (array $matches): string => $matches[1]."\n{$import}\n",
                $contents,
                1,
            ) ?? $contents;
        }

        if (! str_contains($contents, 'implements HasMediaUser')) {
            $contents = preg_replace_callback(
                '/(class\s+User\s+extends\s+[^\{]+)(\{)/',
                function (array $matches): string {
                    $declaration = rtrim($matches[1]);

                    return str_contains($declaration, ' implements ')
                        ? $declaration.', HasMediaUser '.$matches[2]
                        : $declaration.' implements HasMediaUser '.$matches[2];
                },
                $contents,
                1,
                $count,
            ) ?? $contents;

            if ($count !== 1) {
                throw new RuntimeException('Unable to add HasMediaUser to app/Models/User.php safely.');
            }
        }

        if (! str_contains($contents, 'function getMediaUserName')) {
            $method = <<<'PHP'

    public function getMediaUserName(): string
    {
        $name = trim(implode(' ', array_filter([
            $this->first_name ?? null,
            $this->middle_name ?? null,
            $this->last_name ?? null,
        ])));

        return $name !== '' ? $name : (string) ($this->name ?? $this->email);
    }
PHP;
            $closingBrace = strrpos($contents, '}');

            if ($closingBrace === false) {
                throw new RuntimeException('Unable to add getMediaUserName to app/Models/User.php safely.');
            }

            $contents = substr($contents, 0, $closingBrace).$method."\n".substr($contents, $closingBrace);
        }

        if (file_put_contents($path, $contents) === false) {
            throw new RuntimeException('Unable to update app/Models/User.php for Laravel Attachments.');
        }
    }

    private function registerRoutes(string $workingDirectory): void
    {
        $path = $workingDirectory.'/routes/web.php';

        if (! is_file($path)) {
            throw new RuntimeException('No routes/web.php was found; Laravel Attachments routes could not be registered safely.');
        }

        $contents = file_get_contents($path);

        if (! is_string($contents)) {
            throw new RuntimeException('Unable to read routes/web.php.');
        }

        if (str_contains($contents, self::ROUTE_MARKER)) {
            return;
        }

        if (str_contains($contents, 'laravel_attachments.php') || str_contains($contents, 'Route::attachments(')) {
            throw new RuntimeException('Existing Laravel Attachments routes were found in routes/web.php. Review them before adding the starter attachment routes.');
        }

        if (! str_contains($contents, 'use Illuminate\\Support\\Facades\\Route;')) {
            $contents = preg_replace('/(<\?php\s*)/', "$1\nuse Illuminate\\Support\\Facades\\Route;\n", $contents, 1) ?? $contents;
        }

        $routeRegistration = "\n\n".self::ROUTE_MARKER.<<<'PHP'

Route::middleware('auth')
    ->as(config('attachments.route_name_prefix').'.')
    ->prefix('attachments')
    ->group(base_path('routes/laravel_attachments.php'));
PHP;

        if (file_put_contents($path, rtrim($contents).$routeRegistration."\n") === false) {
            throw new RuntimeException('Unable to register Laravel Attachments routes safely.');
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
