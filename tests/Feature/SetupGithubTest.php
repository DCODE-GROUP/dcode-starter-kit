<?php

declare(strict_types=1);

use DcodeStarterKit\DcodeStarterKit\Actions\SetupGithub;

function githubFixture(): string
{
    $directory = sys_get_temp_dir().'/dcode-github-'.bin2hex(random_bytes(5));
    mkdir($directory, 0755, true);

    return $directory;
}

afterEach(function (): void {
    if (isset($this->githubDirectory) && is_dir($this->githubDirectory)) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->githubDirectory, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($iterator as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }

        rmdir($this->githubDirectory);
    }
});

it('installs host GitHub workflows and contribution templates', function (): void {
    $this->githubDirectory = githubFixture();

    expect(app(SetupGithub::class)->setup($this->githubDirectory, static function (): void {}))->toBeTrue()
        ->and(is_file($this->githubDirectory.'/.github/workflows/ci.yml'))->toBeTrue()
        ->and(is_file($this->githubDirectory.'/.github/workflows/run-tests.yml'))->toBeTrue()
        ->and(is_file($this->githubDirectory.'/.github/workflows/pull-request.yml'))->toBeTrue()
        ->and(file_get_contents($this->githubDirectory.'/.github/workflows/ci.yml'))->toContain('runs-on: self-hosted')
        ->and(file_get_contents($this->githubDirectory.'/.github/workflows/ci.yml'))->not->toContain('matrix:')
        ->and(is_file($this->githubDirectory.'/.github/PULL_REQUEST_TEMPLATE.md'))->toBeTrue()
        ->and(is_file($this->githubDirectory.'/.github/dependabot.yml'))->toBeTrue();
});

it('is idempotent and refuses customised GitHub files', function (): void {
    $this->githubDirectory = githubFixture();
    $action = app(SetupGithub::class);

    expect($action->setup($this->githubDirectory, static function (): void {}))->toBeTrue()
        ->and($action->setup($this->githubDirectory, static function (): void {}))->toBeTrue();

    file_put_contents($this->githubDirectory.'/.github/workflows/ci.yml', 'custom');
    $messages = [];

    expect($action->setup($this->githubDirectory, function (string $message) use (&$messages): void {
        $messages[] = $message;
    }))->toBeFalse()
        ->and(implode('', $messages))->toContain('Refusing to overwrite customised .github/workflows/ci.yml');
});
