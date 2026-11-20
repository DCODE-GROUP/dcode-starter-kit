<?php

declare(strict_types=1);

use DcodeStarterKit\DcodeStarterKit\Actions\SetupAws;

function awsFixture(): string
{
    $directory = sys_get_temp_dir().'/dcode-aws-'.bin2hex(random_bytes(5));
    mkdir($directory.'/app/Providers', 0755, true);
    mkdir($directory.'/bootstrap', 0755, true);
    mkdir($directory.'/config', 0755, true);
    file_put_contents($directory.'/bootstrap/app.php', <<<'PHP'
<?php

use Illuminate\Foundation\Application;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(web: __DIR__.'/../routes/web.php');
PHP);
    file_put_contents($directory.'/config/filesystems.php', <<<'PHP'
<?php

return [
    'disks' => [
        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
        ],
    ],
];
PHP);

    return $directory;
}

afterEach(function () {
    if (isset($this->awsDirectory) && is_dir($this->awsDirectory)) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->awsDirectory, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($iterator as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }

        rmdir($this->awsDirectory);
    }
});

it('installs AWS and CloudFront configuration and S3-compatible disks', function () {
    $this->awsDirectory = awsFixture();

    expect(app(SetupAws::class)->setup($this->awsDirectory, static function (): void {}))->toBeTrue()
        ->and(is_file($this->awsDirectory.'/config/aws.php'))->toBeTrue()
        ->and(is_file($this->awsDirectory.'/config/cloudfront-url-signer.php'))->toBeTrue()
        ->and(file_get_contents($this->awsDirectory.'/config/filesystems.php'))->toContain("'s3' => [")
        ->toContain("'minio' => [")
        ->and(file_get_contents($this->awsDirectory.'/bootstrap/app.php'))->toContain('AwsServiceProvider::class');
});

it('is idempotent for generated AWS configuration', function () {
    $this->awsDirectory = awsFixture();
    $action = app(SetupAws::class);

    expect($action->setup($this->awsDirectory, static function (): void {}))->toBeTrue()
        ->and($action->setup($this->awsDirectory, static function (): void {}))->toBeTrue();

    expect(substr_count((string) file_get_contents($this->awsDirectory.'/config/filesystems.php'), "'s3' => ["))->toBe(1)
        ->and(substr_count((string) file_get_contents($this->awsDirectory.'/config/filesystems.php'), "'minio' => ["))->toBe(1)
        ->and(substr_count((string) file_get_contents($this->awsDirectory.'/bootstrap/app.php'), 'AwsServiceProvider::class'))->toBe(1);
});
