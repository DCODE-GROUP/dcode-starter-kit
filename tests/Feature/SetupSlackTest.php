<?php

declare(strict_types=1);

use DcodeStarterKit\DcodeStarterKit\Actions\SetupSlack;

function slackFixture(): string
{
    $directory = sys_get_temp_dir().'/dcode-slack-'.bin2hex(random_bytes(5));
    mkdir($directory.'/config', 0755, true);
    file_put_contents($directory.'/config/services.php', "<?php\n\nreturn [\n];\n");
    file_put_contents($directory.'/config/logging.php', <<<'PHP'
<?php

return [
    'channels' => [
        'single' => [
            'driver' => 'single',
            'path' => storage_path('logs/laravel.log'),
        ],
    ],

];
PHP);

    return $directory;
}

afterEach(function () {
    if (isset($this->slackDirectory) && is_dir($this->slackDirectory)) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->slackDirectory, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($iterator as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }

        rmdir($this->slackDirectory);
    }
});

it('adds Slack notification and logging configuration', function () {
    $this->slackDirectory = slackFixture();

    expect(app(SetupSlack::class)->setup($this->slackDirectory, static function (): void {}))->toBeTrue()
        ->and(file_get_contents($this->slackDirectory.'/config/services.php'))
        ->toContain('SLACK_BOT_USER_OAUTH_TOKEN')
        ->toContain('SLACK_BOT_USER_DEFAULT_CHANNEL')
        ->and(file_get_contents($this->slackDirectory.'/config/logging.php'))
        ->toContain("'driver' => 'slack'")
        ->toContain('LOG_SLACK_WEBHOOK_URL');
});

it('is idempotent for generated Slack configuration', function () {
    $this->slackDirectory = slackFixture();
    $action = app(SetupSlack::class);

    expect($action->setup($this->slackDirectory, static function (): void {}))->toBeTrue()
        ->and($action->setup($this->slackDirectory, static function (): void {}))->toBeTrue();

    expect(substr_count((string) file_get_contents($this->slackDirectory.'/config/services.php'), "'slack' => ["))->toBe(1)
        ->and(substr_count((string) file_get_contents($this->slackDirectory.'/config/logging.php'), "'driver' => 'slack'"))->toBe(1);
});
