<?php

declare(strict_types=1);

use DcodeStarterKit\DcodeStarterKit\Actions\PublishAttachmentMigrations;

function attachmentMigrationFixture(): string
{
    $directory = sys_get_temp_dir().'/dcode-attachments-'.bin2hex(random_bytes(5));
    mkdir($directory.'/database/migrations', 0777, true);

    return $directory;
}

it('publishes and normalizes the attachment migrations', function () {
    $directory = attachmentMigrationFixture();
    $calls = [];
    $runner = function (array $arguments, string $workingDirectory, callable $output) use (&$calls): bool {
        $calls[] = [$arguments, $workingDirectory];
        file_put_contents($workingDirectory.'/database/migrations/2026_01_01_000000_create_media_table.php', '<?php');
        file_put_contents($workingDirectory.'/database/migrations/2026_01_01_000000_create_media_categories_table.php', '<?php');
        file_put_contents($workingDirectory.'/database/migrations/2026_01_01_000000_create_annotations_table.php', '<?php');

        return true;
    };

    expect((new PublishAttachmentMigrations($runner))->publish($directory, fn () => null))->toBeTrue()
        ->and($calls[0][0])->toBe(['php', 'artisan', 'vendor:publish', '--tag=laravel-attachments-migrations'])
        ->and(is_file($directory.'/database/migrations/0001_01_01_000004_create_media_table.php'))->toBeTrue()
        ->and(is_file($directory.'/database/migrations/0001_01_01_000005_create_media_categories_table.php'))->toBeTrue()
        ->and(is_file($directory.'/database/migrations/0001_01_01_000006_create_annotations_table.php'))->toBeTrue();
});

it('does not publish duplicate attachment migrations', function () {
    $directory = attachmentMigrationFixture();
    foreach ([
        '0001_01_01_000004_create_media_table.php',
        '0001_01_01_000005_create_media_categories_table.php',
        '0001_01_01_000006_create_annotations_table.php',
    ] as $migration) {
        file_put_contents($directory.'/database/migrations/'.$migration, '<?php');
    }
    $calls = 0;

    expect((new PublishAttachmentMigrations(function () use (&$calls): bool {
        $calls++;

        return true;
    }))->publish($directory, fn () => null))->toBeTrue()
        ->and($calls)->toBe(0);
});

it('falls back to the installed attachment stubs when publishing is guarded by the environment', function () {
    $directory = attachmentMigrationFixture();
    mkdir($directory.'/vendor/dcodegroup/laravel-attachments-backend/database/migrations', 0777, true);

    foreach ([
        'create_media_table.stub.php',
        'create_media_categories_table.stub.php',
        'create_media_annotations_table.stub.php',
    ] as $stub) {
        file_put_contents($directory.'/vendor/dcodegroup/laravel-attachments-backend/database/migrations/'.$stub, '<?php');
    }

    expect((new PublishAttachmentMigrations(function (): bool {
        return true;
    }))->publish($directory, fn () => null))->toBeTrue()
        ->and(is_file($directory.'/database/migrations/0001_01_01_000004_create_media_table.php'))->toBeTrue()
        ->and(is_file($directory.'/database/migrations/0001_01_01_000005_create_media_categories_table.php'))->toBeTrue()
        ->and(is_file($directory.'/database/migrations/0001_01_01_000006_create_annotations_table.php'))->toBeTrue();
});
