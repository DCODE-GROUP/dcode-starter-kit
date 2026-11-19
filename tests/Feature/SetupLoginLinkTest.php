<?php

declare(strict_types=1);

use DcodeStarterKit\DcodeStarterKit\Actions\SetupLoginLink;

function loginLinkFixture(): string
{
    $directory = sys_get_temp_dir().'/dcode-login-link-'.bin2hex(random_bytes(5));
    mkdir($directory.'/config', 0755, true);

    return $directory;
}

afterEach(function () {
    if (isset($this->loginLinkDirectory) && is_dir($this->loginLinkDirectory)) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->loginLinkDirectory, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($iterator as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }

        rmdir($this->loginLinkDirectory);
    }
});

it('installs the local-only login-link configuration', function () {
    $this->loginLinkDirectory = loginLinkFixture();

    expect(app(SetupLoginLink::class)->setup($this->loginLinkDirectory, static function (): void {}))->toBeTrue()
        ->and(file_get_contents($this->loginLinkDirectory.'/config/login-link.php'))
        ->toContain("'allowed_environments' => ['local']")
        ->toContain("'login_link_controller' => LoginLinkController::class");
});

it('is idempotent and refuses customised login-link configuration', function () {
    $this->loginLinkDirectory = loginLinkFixture();
    $action = app(SetupLoginLink::class);

    expect($action->setup($this->loginLinkDirectory, static function (): void {}))->toBeTrue()
        ->and($action->setup($this->loginLinkDirectory, static function (): void {}))->toBeTrue();

    file_put_contents($this->loginLinkDirectory.'/config/login-link.php', '<?php return [];');

    expect($action->setup($this->loginLinkDirectory, static function (): void {}))->toBeFalse();
});
