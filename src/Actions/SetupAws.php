<?php

declare(strict_types=1);

namespace DcodeStarterKit\DcodeStarterKit\Actions;

use RuntimeException;

class SetupAws
{
    public const string STEP = 'AWS setup';

    private const string PROVIDER = 'Aws\\Laravel\\AwsServiceProvider::class';

    /** @param callable(string): void $output */
    public function setup(string $workingDirectory, callable $output): bool
    {
        try {
            $this->copyConfiguration($workingDirectory);
            $this->configureFilesystems($workingDirectory);
            $this->registerProvider($workingDirectory);

            $output("AWS S3 and CloudFront configured.\n");

            return true;
        } catch (RuntimeException $exception) {
            $output($exception->getMessage()."\n");

            return false;
        }
    }

    private function copyConfiguration(string $workingDirectory): void
    {
        $sourceDirectory = dirname(__DIR__, 2).'/resources/aws/config';

        foreach (['aws.php', 'cloudfront-url-signer.php'] as $file) {
            $source = $sourceDirectory.'/'.$file;
            $target = $workingDirectory.'/config/'.$file;

            if (! is_file($source)) {
                throw new RuntimeException("The AWS configuration source {$file} is missing.");
            }

            if (is_file($target)) {
                if (file_get_contents($source) !== file_get_contents($target)) {
                    throw new RuntimeException("Refusing to overwrite customised config/{$file}.");
                }

                continue;
            }

            if (! is_dir(dirname($target)) && ! mkdir(dirname($target), 0755, true) && ! is_dir(dirname($target))) {
                throw new RuntimeException('Unable to create the config directory for AWS.');
            }

            if (! copy($source, $target)) {
                throw new RuntimeException("Unable to create config/{$file}.");
            }
        }
    }

    private function configureFilesystems(string $workingDirectory): void
    {
        $path = $workingDirectory.'/config/filesystems.php';

        if (! is_file($path)) {
            throw new RuntimeException('config/filesystems.php was not found; the S3 filesystem could not be configured safely.');
        }

        $contents = file_get_contents($path);

        if (! is_string($contents)) {
            throw new RuntimeException('Unable to read config/filesystems.php.');
        }

        $disks = '';

        if (! str_contains($contents, "'s3' => [")) {
            $disks .= <<<'PHP'

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION', 'ap-southeast-2'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
            'report' => false,
        ],
PHP;
        }

        if (! str_contains($contents, "'minio' => [")) {
            $disks .= <<<'PHP'

        'minio' => [
            'driver' => 's3',
            'key' => env('MINIO_ACCESS_KEY_ID', 'laradock'),
            'secret' => env('MINIO_SECRET_ACCESS_KEY', 'laradock'),
            'region' => env('MINIO_DEFAULT_REGION', 'us-east-1'),
            'bucket' => env('MINIO_BUCKET', 'strata'),
            'endpoint' => env('MINIO_ENDPOINT', 'http://minio:9900'),
            'url' => env('MINIO_URL', 'https://minio.test/project-starter/'),
            'use_path_style_endpoint' => true,
            'throw' => false,
        ],
PHP;
        }

        if ($disks === '') {
            return;
        }

        $contents = preg_replace_callback(
            "/(    'disks' => \[)(.*?)(\n    \],)/s",
            static fn (array $matches): string => $matches[1].$matches[2].$disks.$matches[3],
            $contents,
            1,
            $count,
        ) ?? $contents;

        if ($count !== 1 || file_put_contents($path, $contents) === false) {
            throw new RuntimeException('Unable to add the S3 filesystem disk safely to config/filesystems.php.');
        }
    }

    private function registerProvider(string $workingDirectory): void
    {
        $path = $workingDirectory.'/bootstrap/app.php';

        if (! is_file($path)) {
            throw new RuntimeException('bootstrap/app.php was not found; the AWS provider could not be registered safely.');
        }

        $contents = file_get_contents($path);

        if (! is_string($contents)) {
            throw new RuntimeException('Unable to read bootstrap/app.php.');
        }

        if (str_contains($contents, self::PROVIDER)) {
            return;
        }

        $contents = preg_replace_callback(
            '/(Application::configure\([^;]+?\))\s*->withRouting/s',
            static fn (array $matches): string => $matches[1]."\n    ->withProviders([\n        ".self::PROVIDER.",\n    ])\n    ->withRouting",
            $contents,
            1,
            $count,
        ) ?? $contents;

        if ($count !== 1 || file_put_contents($path, $contents) === false) {
            throw new RuntimeException('Unable to register AwsServiceProvider safely in bootstrap/app.php.');
        }
    }
}
