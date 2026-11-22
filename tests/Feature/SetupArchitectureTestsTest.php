<?php

declare(strict_types=1);

use DcodeStarterKit\DcodeStarterKit\Actions\SetupArchitectureTests;

function architectureTestsFixture(): string
{
    $directory = sys_get_temp_dir().'/dcode-architecture-tests-'.bin2hex(random_bytes(5));
    mkdir($directory, 0755, true);

    return $directory;
}

afterEach(function (): void {
    if (isset($this->architectureTestsDirectory) && is_dir($this->architectureTestsDirectory)) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->architectureTestsDirectory, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($iterator as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }

        rmdir($this->architectureTestsDirectory);
    }
});

it('installs the host PHPUnit configuration and architecture tests', function (): void {
    $this->architectureTestsDirectory = architectureTestsFixture();

    expect((new SetupArchitectureTests)->setup($this->architectureTestsDirectory, static function (): void {}))->toBeTrue()
        ->and(file_get_contents($this->architectureTestsDirectory.'/phpunit.xml.dist'))
        ->toBe(file_get_contents(dirname(__DIR__, 2).'/resources/phpunit.xml.dist'))
        ->and(is_file($this->architectureTestsDirectory.'/tests/Architecture/CommonTest.php'))->toBeTrue()
        ->and(is_file($this->architectureTestsDirectory.'/tests/Architecture/FilterTest.php'))->toBeTrue()
        ->and(is_file($this->architectureTestsDirectory.'/tests/Unit/TranslationsTest.php'))->toBeTrue();
});

it('is idempotent and refuses customised architecture files', function (): void {
    $this->architectureTestsDirectory = architectureTestsFixture();
    $action = new SetupArchitectureTests;

    expect($action->setup($this->architectureTestsDirectory, static function (): void {}))->toBeTrue()
        ->and($action->setup($this->architectureTestsDirectory, static function (): void {}))->toBeTrue();

    file_put_contents($this->architectureTestsDirectory.'/tests/Architecture/CommonTest.php', '<?php // custom');
    $messages = [];

    expect($action->setup($this->architectureTestsDirectory, function (string $message) use (&$messages): void {
        $messages[] = $message;
    }))->toBeFalse()
        ->and(implode('', $messages))->toContain('Refusing to overwrite customised tests/Architecture/CommonTest.php.');
});
