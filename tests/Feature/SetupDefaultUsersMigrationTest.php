<?php

declare(strict_types=1);

use DcodeStarterKit\DcodeStarterKit\Actions\SetupDefaultUsersMigration;

function usersMigrationFixture(): string
{
    $directory = sys_get_temp_dir().'/dcode-users-migration-'.bin2hex(random_bytes(5));
    mkdir($directory.'/database/migrations', 0777, true);
    file_put_contents($directory.'/database/migrations/0001_01_01_000000_create_users_table.php', <<<'PHP'
<?php

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
        });
    }
};
PHP
    );

    return $directory;
}

it('updates only the users table in the default users migration', function () {
    $directory = usersMigrationFixture();
    $action = new SetupDefaultUsersMigration;

    expect($action->setup($directory, fn () => null))->toBeTrue();

    $migration = (string) file_get_contents($directory.'/database/migrations/0001_01_01_000000_create_users_table.php');

    expect($migration)
        ->toContain("\$table->string('first_name');")
        ->toContain('$table->lastModifiedFields();')
        ->toContain("Schema::create('password_reset_tokens', function (Blueprint \$table) {")
        ->toContain("Schema::create('sessions', function (Blueprint \$table) {")
        ->not->toContain("\$table->string('name');")
        ->not->toContain("\$table->foreignId('current_team_id')");
});

it('is idempotent after configuring the users migration', function () {
    $directory = usersMigrationFixture();
    $action = new SetupDefaultUsersMigration;

    expect($action->setup($directory, fn () => null))->toBeTrue();
    $first = (string) file_get_contents($directory.'/database/migrations/0001_01_01_000000_create_users_table.php');
    expect($action->setup($directory, fn () => null))->toBeTrue()
        ->and(file_get_contents($directory.'/database/migrations/0001_01_01_000000_create_users_table.php'))->toBe($first);
});

it('does not change an ambiguous migration set', function () {
    $directory = usersMigrationFixture();
    file_put_contents($directory.'/database/migrations/0001_01_01_000001_create_users_again.php', (string) file_get_contents($directory.'/database/migrations/0001_01_01_000000_create_users_table.php'));
    $messages = [];

    expect((new SetupDefaultUsersMigration)->setup($directory, function (string $message) use (&$messages): void {
        $messages[] = $message;
    }))->toBeFalse()
        ->and(implode('', $messages))->toContain('Unable to identify one default users migration');
});
