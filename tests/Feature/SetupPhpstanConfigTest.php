<?php

declare(strict_types=1);

use DcodeStarterKit\DcodeStarterKit\Actions\SetupPhpstanConfig;

function phpstanConfigFixture(): string
{
    $directory = sys_get_temp_dir().'/dcode-phpstan-'.bin2hex(random_bytes(5));
    mkdir($directory, 0755, true);

    return $directory;
}

afterEach(function (): void {
    if (isset($this->phpstanDirectory) && is_dir($this->phpstanDirectory)) {
        if (is_file($this->phpstanDirectory.'/phpstan.neon.dist')) {
            unlink($this->phpstanDirectory.'/phpstan.neon.dist');
        }

        rmdir($this->phpstanDirectory);
    }
});

it('creates the host PHPStan configuration', function (): void {
    $this->phpstanDirectory = phpstanConfigFixture();

    expect((new SetupPhpstanConfig)->setup($this->phpstanDirectory, static function (): void {}))->toBeTrue()
        ->and(file_get_contents($this->phpstanDirectory.'/phpstan.neon.dist'))
        ->toBe(file_get_contents(dirname(__DIR__, 2).'/resources/phpstan.neon.dist'));
});

it('is idempotent and refuses a customised PHPStan configuration', function (): void {
    $this->phpstanDirectory = phpstanConfigFixture();
    $action = new SetupPhpstanConfig;

    expect($action->setup($this->phpstanDirectory, static function (): void {}))->toBeTrue()
        ->and($action->setup($this->phpstanDirectory, static function (): void {}))->toBeTrue();

    file_put_contents($this->phpstanDirectory.'/phpstan.neon.dist', "parameters:\n    level: 8\n");
    $messages = [];

    expect($action->setup($this->phpstanDirectory, function (string $message) use (&$messages): void {
        $messages[] = $message;
    }))->toBeFalse()
        ->and(implode('', $messages))->toContain('Refusing to overwrite customised phpstan.neon.dist');
});
