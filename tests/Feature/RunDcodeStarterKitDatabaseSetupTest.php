<?php

declare(strict_types=1);

use DcodeStarterKit\DcodeStarterKit\Actions\RunDcodeStarterKitDatabaseSetup;

it('runs migrations before the database seeder', function () {
    $calls = [];
    $runner = function (array $arguments, string $workingDirectory, callable $output) use (&$calls): bool {
        $calls[] = $arguments;

        return true;
    };

    expect((new RunDcodeStarterKitDatabaseSetup($runner))->run('/tmp', fn () => null))->toBeTrue()
        ->and($calls)->toBe([
            ['php', 'artisan', 'migrate', '--force'],
            ['php', 'artisan', 'db:seed', '--force'],
        ]);
});

it('does not seed when migrations fail', function () {
    $calls = [];
    $runner = function (array $arguments, string $workingDirectory, callable $output) use (&$calls): bool {
        $calls[] = $arguments;

        return false;
    };

    expect((new RunDcodeStarterKitDatabaseSetup($runner))->run('/tmp', fn () => null))->toBeFalse()
        ->and($calls)->toHaveCount(1);
});

it('reports seeding failures after migrations succeed', function () {
    $calls = [];
    $runner = function (array $arguments, string $workingDirectory, callable $output) use (&$calls): bool {
        $calls[] = $arguments;

        return count($calls) === 1;
    };

    expect((new RunDcodeStarterKitDatabaseSetup($runner))->run('/tmp', fn () => null))->toBeFalse()
        ->and($calls)->toHaveCount(2);
});
