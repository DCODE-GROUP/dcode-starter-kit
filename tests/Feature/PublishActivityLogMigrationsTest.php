<?php

declare(strict_types=1);

use DcodeStarterKit\DcodeStarterKit\Actions\PublishActivityLogMigrations;

function activityMigrationFixture(): string
{
    $directory = sys_get_temp_dir().'/dcode-activity-log-'.bin2hex(random_bytes(5));
    mkdir($directory.'/database/migrations', 0777, true);

    return $directory;
}

it('publishes activity log migrations with the package tag', function () {
    $directory = activityMigrationFixture();
    $calls = [];

    $runner = function (array $arguments, string $workingDirectory, callable $output) use (&$calls): bool {
        $calls[] = [$arguments, $workingDirectory];
        file_put_contents($workingDirectory.'/database/migrations/2026_01_01_000000_create_activity_logs_tables.php', '<?php');

        return true;
    };

    expect((new PublishActivityLogMigrations($runner))->publish($directory, fn () => null))->toBeTrue()
        ->and($calls[0][0])->toBe(['php', 'artisan', 'vendor:publish', '--tag=activity-log-migrations']);
    expect(is_file($directory.'/database/migrations/0001_01_01_000003_create_activity_logs_tables.php'))->toBeTrue()
        ->and(is_file($directory.'/database/migrations/2026_01_01_000000_create_activity_logs_tables.php'))->toBeFalse();
});

it('does not publish duplicate activity log migrations', function () {
    $directory = activityMigrationFixture();
    file_put_contents($directory.'/database/migrations/0001_01_01_000003_create_activity_logs_tables.php', '<?php');
    $calls = 0;

    expect((new PublishActivityLogMigrations(function () use (&$calls): bool {
        $calls++;

        return true;
    }))->publish($directory, fn () => null))->toBeTrue()
        ->and($calls)->toBe(0);
});

it('reports a failed publish command', function () {
    $directory = activityMigrationFixture();
    $messages = [];

    expect((new PublishActivityLogMigrations(fn () => false))->publish($directory, function (string $message) use (&$messages): void {
        $messages[] = $message;
    }))->toBeFalse()
        ->and(implode('', $messages))->toContain('Unable to publish the activity log migrations');
});
