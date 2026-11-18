<?php

declare(strict_types=1);

namespace DcodeStarterKit\DcodeStarterKit\Actions;

use Closure;
use RuntimeException;
use Symfony\Component\Process\Process;

class SetupSpatiePermission
{
    public const string STEP = 'Spatie permission setup';

    public function __construct(?Closure $processRunner = null)
    {
        $this->processRunner = $processRunner ?? $this->runArtisanProcess(...);
    }

    /**
     * @param  callable(string): void  $output
     * @param  callable(bool, string): bool  $confirm
     * @param  callable(string, string): string  $ask
     */
    public function setup(
        string $workingDirectory,
        callable $output,
        callable $confirm,
        callable $ask,
        bool $interactive = true,
    ): bool {
        $configPath = $workingDirectory.'/config/permission.php';
        $existingConfig = is_file($configPath) ? file_get_contents($configPath) : null;
        $existingConfig = is_string($existingConfig) ? $existingConfig : null;
        $existingTeams = $this->readBoolean($existingConfig, 'teams');
        $existingForeignKey = $this->readForeignKey($existingConfig);

        [$teams, $foreignKey] = $this->collectOptions(
            $existingTeams,
            $existingForeignKey,
            $output,
            $confirm,
            $ask,
            $interactive,
        );

        if (! $this->isValidColumnIdentifier($foreignKey)) {
            $output("The configured tenant foreign-key column is invalid; no permission configuration was changed.\n");

            return false;
        }

        if (! is_dir($workingDirectory.'/database/migrations') || ! is_readable($workingDirectory.'/database/migrations')) {
            $output("Unable to establish the application's permission migration state safely; no permission configuration was changed.\n");

            return false;
        }

        $hasExistingMigration = $this->hasPublishedPermissionMigration($workingDirectory);
        $requiresUpgrade = $hasExistingMigration && (($existingTeams ?? false) !== $teams || ($teams && $existingForeignKey !== null && $existingForeignKey !== $foreignKey));

        if ($requiresUpgrade) {
            $output("Existing Spatie permission migrations indicate that this setting may require a schema upgrade. The current configuration was preserved. Review the schema and use php artisan permission:setup-teams when appropriate, then run php artisan migrate.\n");

            return true;
        }

        if ($existingConfig !== null) {
            try {
                $this->updateConfiguration($existingConfig, $teams, $foreignKey);
            } catch (RuntimeException $exception) {
                $output($exception->getMessage()."\n");

                return false;
            }
        }

        if (! $this->publishAssets($workingDirectory, $output, $existingConfig !== null, $hasExistingMigration)) {
            return false;
        }

        $configPath = $workingDirectory.'/config/permission.php';
        $config = file_get_contents($configPath);

        if (! is_string($config)) {
            $output("Unable to read config/permission.php safely; no configuration was changed.\n");

            return false;
        }

        try {
            $updatedConfig = $this->updateConfiguration($config, $teams, $foreignKey);

            if ($updatedConfig !== $config && ! $this->writeAtomically($configPath, $updatedConfig)) {
                throw new RuntimeException('Unable to persist config/permission.php safely.');
            }
        } catch (RuntimeException $exception) {
            $output($exception->getMessage()."\n");

            return false;
        }

        if (! ($this->processRunner)(['php', 'artisan', 'config:clear'], $workingDirectory, $output)) {
            $output("The child Artisan process could not clear the application configuration cache.\n");

            return false;
        }

        $this->reportNextSteps($output, $teams);

        return true;
    }

    /**
     * @param  callable(string): void  $output
     * @param  callable(bool, string): bool  $confirm
     * @param  callable(string, string): string  $ask
     * @return array{0: bool, 1: string}
     */
    private function collectOptions(
        ?bool $existingTeams,
        ?string $existingForeignKey,
        callable $output,
        callable $confirm,
        callable $ask,
        bool $interactive,
    ): array {
        $output("Spatie's teams feature scopes roles and role assignments to a team or tenant in a shared database. It does not implement application tenancy itself.\n");

        $teams = $existingTeams ?? false;

        if ($interactive) {
            $teams = $confirm($teams, 'Should roles and role assignments be scoped to a team/tenant?');
        }

        $foreignKey = $existingForeignKey ?? 'team_id';

        if ($teams && $interactive) {
            do {
                $foreignKey = $ask('What is the tenant foreign-key column name?', $foreignKey);

                if (! $this->isValidColumnIdentifier($foreignKey)) {
                    $output("The tenant foreign-key column must be a valid identifier, such as tenant_id or organisation_id.\n");
                }
            } while (! $this->isValidColumnIdentifier($foreignKey));
        }

        return [$teams, $foreignKey];
    }

    /** @param callable(string): void $output */
    private function reportNextSteps(callable $output, bool $teams): void
    {
        $output("Next steps: review config/permission.php and the published migration, then run php artisan migrate. Add Spatie\\Permission\\Traits\\HasRoles to the appropriate authenticatable model.\n");

        if ($teams) {
            $output("With teams enabled, call setPermissionsTeamId(\$tenantId) from the application's tenancy context before permission checks and clear loaded roles and permissions relations when changing tenant context.\n");
        }

        $output("Teams configuration does not provide tenant resolution or data isolation; UUID/ULID identifiers may require migration and model adjustments.\n");
    }

    /** @param callable(string): void $output */
    private function publishAssets(string $workingDirectory, callable $output, bool $hasConfig, bool $hasMigration): bool
    {
        $provider = 'Spatie\\Permission\\PermissionServiceProvider';
        $tags = $this->supportedPublishTags($workingDirectory);

        if (! $hasConfig) {
            $arguments = ['php', 'artisan', 'vendor:publish', '--provider='.$provider];

            if (isset($tags['config'])) {
                $arguments[] = '--tag='.$tags['config'];
            }

            if (! ($this->processRunner)($arguments, $workingDirectory, $output)) {
                $output("Unable to publish Spatie permission configuration.\n");

                return false;
            }
        }

        if (! $hasMigration && $this->hasPublishedPermissionMigration($workingDirectory) === false) {
            $arguments = ['php', 'artisan', 'vendor:publish', '--provider='.$provider];

            if (isset($tags['migration'])) {
                $arguments[] = '--tag='.$tags['migration'];
            }

            if (! ($this->processRunner)($arguments, $workingDirectory, $output)) {
                $output("Unable to publish Spatie permission migrations.\n");

                return false;
            }
        }

        return true;
    }

    /** @return array{config?: string, migration?: string} */
    private function supportedPublishTags(string $workingDirectory): array
    {
        $providerPath = $workingDirectory.'/vendor/spatie/laravel-permission/src/PermissionServiceProvider.php';
        $source = is_file($providerPath) ? file_get_contents($providerPath) : false;

        if (! is_string($source)) {
            return [];
        }

        $tags = [];

        if (preg_match("/['\"](permission-[^'\"]*config[^'\"]*)['\"]/", $source, $matches) === 1) {
            $tags['config'] = $matches[1];
        }

        if (preg_match("/['\"](permission-[^'\"]*migration[^'\"]*)['\"]/", $source, $matches) === 1) {
            $tags['migration'] = $matches[1];
        }

        return $tags;
    }

    private function hasPublishedPermissionMigration(string $workingDirectory): bool
    {
        foreach (glob($workingDirectory.'/database/migrations/*.php') ?: [] as $migration) {
            $basename = basename($migration);

            if (str_contains($basename, 'create_permission_tables') || str_contains($basename, 'permission_tables')) {
                return true;
            }

            $source = file_get_contents($migration);

            if (is_string($source) && str_contains($source, "create('roles'")) {
                return true;
            }
        }

        return false;
    }

    private function updateConfiguration(string $config, bool $teams, string $foreignKey): string
    {
        $updated = preg_replace_callback(
            "/^(\s*)(['\"]teams['\"]\s*=>\s*)(true|false)(\s*,?.*)$/m",
            static fn (array $matches): string => $matches[1].$matches[2].($teams ? 'true' : 'false').$matches[4],
            $config,
            1,
        );

        if (! is_string($updated) || $updated === $config && $this->readBoolean($config, 'teams') === null) {
            throw new RuntimeException('Unable to safely locate the teams setting in config/permission.php.');
        }

        if (! $teams) {
            return $updated;
        }

        $updatedWithForeignKey = preg_replace_callback(
            "/^(\s*)(['\"]team_foreign_key['\"]\s*=>\s*)(['\"])[^'\"]*(['\"])(\s*,?.*)$/m",
            static fn (array $matches): string => $matches[1].$matches[2].$matches[3].$foreignKey.$matches[4].$matches[5],
            $updated,
            1,
        );

        if (! is_string($updatedWithForeignKey) || ! str_contains($updatedWithForeignKey, 'team_foreign_key')) {
            throw new RuntimeException('Unable to safely locate column_names.team_foreign_key in config/permission.php.');
        }

        return $updatedWithForeignKey;
    }

    private function readBoolean(?string $config, string $key): ?bool
    {
        if ($config === null || preg_match("/['\"]".preg_quote($key, '/')."['\"]\s*=>\s*(true|false)/", $config, $matches) !== 1) {
            return null;
        }

        return $matches[1] === 'true';
    }

    private function readForeignKey(?string $config): ?string
    {
        if ($config === null || preg_match("/['\"]team_foreign_key['\"]\s*=>\s*['\"]([^'\"]+)['\"]/", $config, $matches) !== 1) {
            return null;
        }

        return $matches[1];
    }

    private function isValidColumnIdentifier(string $column): bool
    {
        return preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $column) === 1;
    }

    private function writeAtomically(string $path, string $contents): bool
    {
        $temporaryPath = $path.'.tmp-'.bin2hex(random_bytes(8));

        $written = file_put_contents($temporaryPath, $contents) !== false;

        if (! $written || ! rename($temporaryPath, $path)) {
            unlink($temporaryPath);

            return false;
        }

        return true;
    }

    /** @param array<int, string> $arguments @param callable(string): void $output */
    private function runArtisanProcess(array $arguments, string $workingDirectory, callable $output): bool
    {
        $process = new Process($arguments, $workingDirectory, null, null, 3600);
        $process->run(function (string $type, string $buffer) use ($output): void {
            $output($buffer);
        });

        return $process->isSuccessful();
    }

    private readonly Closure $processRunner;
}
