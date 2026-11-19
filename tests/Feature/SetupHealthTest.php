<?php

declare(strict_types=1);

use DcodeStarterKit\DcodeStarterKit\Actions\SetupHealth;

function healthFixture(): string
{
    $directory = sys_get_temp_dir().'/dcode-health-'.bin2hex(random_bytes(5));
    mkdir($directory.'/bootstrap', 0755, true);
    mkdir($directory.'/config', 0755, true);
    mkdir($directory.'/database/migrations', 0755, true);
    mkdir($directory.'/routes', 0755, true);
    file_put_contents($directory.'/bootstrap/providers.php', "<?php\n\nreturn [];\n");
    file_put_contents($directory.'/routes/web.php', "<?php\n\nuse Illuminate\\Support\\Facades\\Route;\n");

    return $directory;
}

afterEach(function () {
    if (isset($this->healthDirectory) && is_dir($this->healthDirectory)) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->healthDirectory, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($iterator as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }

        rmdir($this->healthDirectory);
    }
});

it('publishes and registers the reusable health checks', function () {
    $this->healthDirectory = healthFixture();
    $runner = function (array $arguments, string $workingDirectory): bool {
        if (in_array('--tag=health-config', $arguments, true)) {
            file_put_contents($workingDirectory.'/config/health.php', "<?php return [];\n");
        }

        if (in_array('--tag=health-migrations', $arguments, true)) {
            file_put_contents($workingDirectory.'/database/migrations/2026_01_01_000000_create_health_tables.php', "<?php\n");
        }

        return true;
    };

    expect((new SetupHealth($runner))->setup($this->healthDirectory, static function (): void {}))->toBeTrue()
        ->and(file_get_contents($this->healthDirectory.'/bootstrap/providers.php'))->toContain('HealthServiceProvider::class')
        ->and(file_get_contents($this->healthDirectory.'/routes/web.php'))->toContain("->name('health')")
        ->and(is_file($this->healthDirectory.'/app/Providers/HealthServiceProvider.php'))->toBeTrue()
        ->and(is_file($this->healthDirectory.'/database/migrations/2026_01_01_000000_create_health_tables.php'))->toBeTrue();
});

it('is safe to rerun when the generated health integration is unchanged', function () {
    $this->healthDirectory = healthFixture();
    $runner = function (array $arguments, string $workingDirectory): bool {
        if (in_array('--tag=health-config', $arguments, true)) {
            file_put_contents($workingDirectory.'/config/health.php', "<?php return [];\n");
        }

        if (in_array('--tag=health-migrations', $arguments, true)) {
            file_put_contents($workingDirectory.'/database/migrations/2026_01_01_000000_create_health_tables.php', "<?php\n");
        }

        return true;
    };
    $action = new SetupHealth($runner);

    expect($action->setup($this->healthDirectory, static function (): void {}))->toBeTrue()
        ->and($action->setup($this->healthDirectory, static function (): void {}))->toBeTrue()
        ->and(substr_count((string) file_get_contents($this->healthDirectory.'/routes/web.php'), "->name('health')"))->toBe(1);
});
