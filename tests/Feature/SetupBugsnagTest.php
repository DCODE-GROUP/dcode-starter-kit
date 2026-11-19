<?php

declare(strict_types=1);

use DcodeStarterKit\DcodeStarterKit\Actions\SetupBugsnag;

function bugsnagFixture(): string
{
    $directory = sys_get_temp_dir().'/dcode-bugsnag-'.bin2hex(random_bytes(5));
    mkdir($directory.'/bootstrap', 0755, true);
    mkdir($directory.'/config', 0755, true);
    file_put_contents($directory.'/bootstrap/providers.php', "<?php\n\nreturn [];\n");
    file_put_contents($directory.'/bootstrap/app.php', "<?php\n\nuse Illuminate\\Foundation\\Application;\n\nreturn Application::configure(basePath: dirname(__DIR__));\n");
    mkdir($directory.'/vendor/bugsnag/bugsnag-laravel/config', 0755, true);
    file_put_contents($directory.'/vendor/bugsnag/bugsnag-laravel/config/bugsnag.php', "<?php return [];\n");
    file_put_contents($directory.'/config/logging.php', <<<'PHP'
<?php

return [
    'channels' => [
        'stack' => [
            'driver' => 'stack',
            'channels' => explode(',', (string) env('LOG_STACK', 'single')),
        ],
    ],

];
PHP);

    return $directory;
}

afterEach(function () {
    if (isset($this->bugsnagDirectory) && is_dir($this->bugsnagDirectory)) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->bugsnagDirectory, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($iterator as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }

        rmdir($this->bugsnagDirectory);
    }
});

it('falls back to the installed dependency config when vendor publish has no resources', function () {
    $this->bugsnagDirectory = bugsnagFixture();
    $action = new SetupBugsnag(static function (): bool {
        return true;
    });

    expect($action->setup($this->bugsnagDirectory, static function (): void {}))->toBeTrue()
        ->and(is_file($this->bugsnagDirectory.'/config/bugsnag.php'))->toBeTrue()
        ->and(file_get_contents($this->bugsnagDirectory.'/config/bugsnag.php'))->toBe("<?php return [];\n");
});

it('configures Bugsnag logging, provider registration, and OOM handling', function () {
    $this->bugsnagDirectory = bugsnagFixture();
    $action = new SetupBugsnag;

    expect($action->setup($this->bugsnagDirectory, static function (): void {}))->toBeTrue()
        ->and(file_get_contents($this->bugsnagDirectory.'/bootstrap/providers.php'))
        ->toContain('BugsnagServiceProvider::class')
        ->and(file_get_contents($this->bugsnagDirectory.'/config/logging.php'))
        ->toContain("'driver' => 'bugsnag'")
        ->toContain("env('LOG_STACK', 'daily,bugsnag')")
        ->and(file_get_contents($this->bugsnagDirectory.'/bootstrap/app.php'))
        ->toContain('OomBootstrapper')
        ->toContain('(new OomBootstrapper)->bootstrap();');
});

it('is idempotent for generated Bugsnag integration', function () {
    $this->bugsnagDirectory = bugsnagFixture();
    $action = new SetupBugsnag;

    expect($action->setup($this->bugsnagDirectory, static function (): void {}))->toBeTrue()
        ->and($action->setup($this->bugsnagDirectory, static function (): void {}))->toBeTrue()
        ->and(substr_count((string) file_get_contents($this->bugsnagDirectory.'/bootstrap/providers.php'), 'BugsnagServiceProvider::class'))->toBe(1)
        ->and(substr_count((string) file_get_contents($this->bugsnagDirectory.'/config/logging.php'), "'driver' => 'bugsnag'"))->toBe(1);
});
