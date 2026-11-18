<?php

declare(strict_types=1);

use DcodeStarterKit\DcodeStarterKit\Actions\SetupSpatiePermission;

function permissionFixture(string $config, bool $migration = false): string
{
    $directory = sys_get_temp_dir().'/dcode-permission-'.bin2hex(random_bytes(5));
    mkdir($directory.'/config', 0777, true);
    mkdir($directory.'/database/migrations', 0777, true);
    file_put_contents($directory.'/config/permission.php', $config);

    if ($migration) {
        file_put_contents($directory.'/database/migrations/2026_01_01_000000_create_permission_tables.php', "Schema::create('roles', fn () => null);");
    }

    return $directory;
}

function permissionConfig(bool $teams = false, string $foreignKey = 'team_id', string $unrelated = 'custom'): string
{
    return "<?php\nreturn [\n    // preserve this comment\n    'teams' => ".($teams ? 'true' : 'false').",\n    'column_names' => [\n        'team_foreign_key' => '$foreignKey',\n    ],\n    'unrelated' => '$unrelated',\n];\n";
}

it('configures teams off by default and preserves unrelated configuration', function () {
    $directory = permissionFixture(permissionConfig(false));
    $calls = [];
    $action = new SetupSpatiePermission(function (array $arguments, string $workingDirectory, callable $output) use (&$calls, $directory): bool {
        $calls[] = $arguments;

        if (str_contains(implode(' ', $arguments), 'vendor:publish')) {
            file_put_contents($directory.'/database/migrations/2026_01_01_000000_create_permission_tables.php', "Schema::create('roles', fn () => null);");
        }

        return true;
    });

    expect($action->setup($directory, fn () => null, fn () => true, fn () => 'team_id', false))->toBeTrue()
        ->and(file_get_contents($directory.'/config/permission.php'))->toContain("'unrelated' => 'custom'")
        ->and($calls)->toHaveCount(2);
});

it('enables teams with the default or custom valid foreign key', function (string $foreignKey) {
    $directory = permissionFixture(permissionConfig());
    $action = new SetupSpatiePermission(function (array $arguments, string $workingDirectory, callable $output) use ($directory): bool {
        if (str_contains(implode(' ', $arguments), 'vendor:publish')) {
            file_put_contents($directory.'/database/migrations/2026_01_01_000000_create_permission_tables.php', "Schema::create('roles', fn () => null);");
        }

        return true;
    });

    expect($action->setup($directory, fn () => null, fn () => true, fn () => $foreignKey, true))->toBeTrue()
        ->and(file_get_contents($directory.'/config/permission.php'))->toContain("'teams' => true")
        ->and(file_get_contents($directory.'/config/permission.php'))->toContain("'team_foreign_key' => '$foreignKey'");
})->with(['team_id', 'organisation_id']);

it('uses an explicit tenancy override without asking questions', function (bool $teams) {
    $directory = permissionFixture(permissionConfig());
    $action = new SetupSpatiePermission(function (array $arguments, string $workingDirectory, callable $output) use ($directory): bool {
        if (str_contains(implode(' ', $arguments), 'vendor:publish')) {
            file_put_contents($directory.'/database/migrations/2026_01_01_000000_create_permission_tables.php', "Schema::create('roles', fn () => null);");
        }

        return true;
    });

    $asked = false;

    expect($action->setup(
        $directory,
        fn () => null,
        function () use (&$asked): bool {
            $asked = true;

            return false;
        },
        function () use (&$asked): string {
            $asked = true;

            return 'team_id';
        },
        false,
        $teams,
    ))->toBeTrue()
        ->and($asked)->toBeFalse()
        ->and(file_get_contents($directory.'/config/permission.php'))->toContain("'teams' => ".($teams ? 'true' : 'false'));
})->with([true, false]);

it('does not publish duplicate migrations or overwrite existing config on rerun', function () {
    $directory = permissionFixture(permissionConfig(true, 'tenant_id', 'keep'), true);
    $before = file_get_contents($directory.'/config/permission.php');
    $calls = [];
    $action = new SetupSpatiePermission(function (array $arguments, string $workingDirectory, callable $output) use (&$calls): bool {
        $calls[] = $arguments;

        return true;
    });

    expect($action->setup($directory, fn () => null, fn (bool $default) => $default, fn (string $question, string $default) => $default, true))->toBeTrue()
        ->and(file_get_contents($directory.'/config/permission.php'))->toBe($before)
        ->and($calls)->toHaveCount(1)
        ->and(glob($directory.'/database/migrations/*.php'))->toHaveCount(1);
});

it('reports an existing schema upgrade without changing configuration', function () {
    $directory = permissionFixture(permissionConfig(false, 'team_id'), true);
    $messages = [];
    $action = new SetupSpatiePermission(fn () => true);

    expect($action->setup($directory, function (string $message) use (&$messages): void {
        $messages[] = $message;
    }, fn () => true, fn () => 'team_id', true))->toBeTrue()
        ->and(file_get_contents($directory.'/config/permission.php'))->toContain("'teams' => false")
        ->and(implode('', $messages))->toContain('schema upgrade');
});

it('fails when publishing or clearing the child command fails', function () {
    $directory = permissionFixture(permissionConfig());
    $action = new SetupSpatiePermission(fn () => false);

    expect($action->setup($directory, fn () => null, fn () => false, fn () => 'team_id', false))->toBeFalse();
});

it('fails safely when the configuration cannot be edited', function () {
    $directory = permissionFixture("<?php\nreturn ['unrelated' => true];\n");
    $configPath = $directory.'/config/permission.php';
    $before = file_get_contents($configPath);
    $action = new SetupSpatiePermission(fn () => true);

    expect($action->setup($directory, fn () => null, fn () => false, fn () => 'team_id', false))->toBeFalse()
        ->and(file_get_contents($configPath))->toBe($before);
});
