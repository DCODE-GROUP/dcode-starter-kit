<?php

declare(strict_types=1);

use DcodeStarterKit\DcodeStarterKit\Actions\SetupApplicationDefaults;

function applicationDefaultsFixture(): string
{
    $directory = sys_get_temp_dir().'/dcode-application-defaults-'.bin2hex(random_bytes(5));
    mkdir($directory.'/app/Providers', 0755, true);
    file_put_contents($directory.'/app/Providers/AppServiceProvider.php', <<<'PHP'
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Existing application boot logic.
    }
}
PHP);

    return $directory;
}

afterEach(function () {
    if (isset($this->applicationDefaultsDirectory) && is_dir($this->applicationDefaultsDirectory)) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->applicationDefaultsDirectory, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($iterator as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }

        rmdir($this->applicationDefaultsDirectory);
    }
});

it('adds the standard lazy-loading and Vite defaults to the host provider', function () {
    $this->applicationDefaultsDirectory = applicationDefaultsFixture();

    expect(app(SetupApplicationDefaults::class)->setup($this->applicationDefaultsDirectory, static function (): void {}))->toBeTrue()
        ->and(file_get_contents($this->applicationDefaultsDirectory.'/app/Providers/AppServiceProvider.php'))
        ->toContain('Model::preventLazyLoading();')
        ->toContain('Model::handleLazyLoadingViolationUsing')
        ->toContain('if (app()->isProduction())')
        ->toContain('report($exception);')
        ->toContain('Vite::prefetch(3);')
        ->toContain("'crossorigin' => 'anonymous'");
});

it('is idempotent and preserves existing provider logic', function () {
    $this->applicationDefaultsDirectory = applicationDefaultsFixture();
    $action = app(SetupApplicationDefaults::class);

    expect($action->setup($this->applicationDefaultsDirectory, static function (): void {}))->toBeTrue()
        ->and($action->setup($this->applicationDefaultsDirectory, static function (): void {}))->toBeTrue();

    $contents = (string) file_get_contents($this->applicationDefaultsDirectory.'/app/Providers/AppServiceProvider.php');

    expect($contents)->toContain('// Existing application boot logic.')
        ->and(substr_count($contents, 'Dcode Starter Kit application defaults'))->toBe(1)
        ->and(substr_count($contents, 'Model::preventLazyLoading();'))->toBe(1)
        ->and(substr_count($contents, 'Model::handleLazyLoadingViolationUsing'))->toBe(1);
});

it('upgrades an existing application defaults block with the production handler', function () {
    $this->applicationDefaultsDirectory = applicationDefaultsFixture();
    $action = app(SetupApplicationDefaults::class);
    $action->setup($this->applicationDefaultsDirectory, static function (): void {});

    $path = $this->applicationDefaultsDirectory.'/app/Providers/AppServiceProvider.php';
    $contents = (string) file_get_contents($path);
    $contents = preg_replace('/\n        Model::handleLazyLoadingViolationUsing.*?\n        \}\);/s', '', $contents) ?? $contents;
    file_put_contents($path, $contents);

    expect($action->setup($this->applicationDefaultsDirectory, static function (): void {}))->toBeTrue()
        ->and(file_get_contents($path))->toContain('Model::handleLazyLoadingViolationUsing')
        ->and(substr_count((string) file_get_contents($path), 'Model::handleLazyLoadingViolationUsing'))->toBe(1);
});
