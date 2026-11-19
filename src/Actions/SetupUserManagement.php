<?php

declare(strict_types=1);

namespace DcodeStarterKit\DcodeStarterKit\Actions;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;

class SetupUserManagement
{
    public const string STEP = 'user and role management setup';

    private const string ROUTE_MARKER = '// Dcode Starter Kit user and role management routes';

    private const string TABLE_ROUTE_MARKER = '// Dcode Starter Kit DSG table routes';

    /** @param callable(string): void $output */
    public function setup(string $workingDirectory, callable $output): bool
    {
        try {
            $this->copyScaffold($workingDirectory);
            $this->registerProvider($workingDirectory);
            $this->updateUserModel($workingDirectory);
            $this->configurePermissionRole($workingDirectory);
            $this->registerSeeder($workingDirectory);
            $this->registerRoutes($workingDirectory);

            $output("User and role management configured, including the host DatabaseSeeder.\n");

            return true;
        } catch (RuntimeException $exception) {
            $output($exception->getMessage()."\n");

            return false;
        }
    }

    private function copyScaffold(string $workingDirectory): void
    {
        $sourceDirectory = dirname(__DIR__, 2).'/resources/user-management';
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($sourceDirectory, FilesystemIterator::SKIP_DOTS));

        foreach ($files as $file) {
            if (! $file instanceof SplFileInfo || ! $file->isFile()) {
                continue;
            }

            $relative = ltrim(str_replace($sourceDirectory, '', $file->getPathname()), DIRECTORY_SEPARATOR);

            if ($relative === 'database/seeders/DatabaseSeeder.php') {
                continue;
            }

            $target = $workingDirectory.'/'.$relative;

            if (is_file($target)) {
                if (file_get_contents($target) === file_get_contents($file->getPathname())) {
                    continue;
                }

                if (! $this->isGeneratedScaffold($relative, $target)) {
                    throw new RuntimeException("Refusing to overwrite customised {$relative}.");
                }
            }

            if (! is_dir(dirname($target)) && ! mkdir(dirname($target), 0755, true) && ! is_dir(dirname($target))) {
                throw new RuntimeException("Unable to create the directory for {$relative}.");
            }

            if (! copy($file->getPathname(), $target)) {
                throw new RuntimeException("Unable to create {$relative}.");
            }
        }
    }

    private function isGeneratedScaffold(string $relative, string $target): bool
    {
        $contents = file_get_contents($target);

        if (! is_string($contents)) {
            return false;
        }

        return match ($relative) {
            'resources/js/pages/Admin/Users/Index.vue' => str_contains($contents, '<table')
                && str_contains($contents, 'admin.users.index'),
            'resources/js/pages/Admin/Roles/Index.vue' => str_contains($contents, '<table')
                && str_contains($contents, 'admin.roles.index'),
            'resources/js/pages/Admin/Users/CreateEdit.vue' => str_contains($contents, 'class="max-w-3xl')
                && str_contains($contents, 'admin.users.update'),
            'resources/js/pages/Admin/Roles/CreateEdit.vue' => str_contains($contents, 'class="max-w-3xl')
                && str_contains($contents, 'admin.roles.update'),
            'app/Http/Controllers/Admin/Users/UserController.php' => str_contains($contents, "Inertia::render('Admin/Users/Index'")
                && str_contains($contents, 'Gate::authorize'),
            'app/Http/Controllers/Admin/Roles/RoleController.php' => str_contains($contents, "Inertia::render('Admin/Roles/Index'")
                && str_contains($contents, 'Gate::authorize'),
            'app/Tables/UsersTable.php' => str_contains($contents, 'class UsersTable')
                && str_contains($contents, 'CrudActions::for($model, \'admin.users\')'),
            'app/Tables/RolesTable.php' => str_contains($contents, 'class RolesTable')
                && str_contains($contents, 'CrudActions::for($model, \'admin.roles\')'),
            default => false,
        };
    }

    private function registerProvider(string $workingDirectory): void
    {
        $path = $workingDirectory.'/bootstrap/providers.php';
        $contents = $this->read($path, 'bootstrap/providers.php');

        if (str_contains($contents, 'UserManagementServiceProvider::class')) {
            return;
        }

        $contents = preg_replace('/(return\s+\[)/', "$1\n    App\\Providers\\UserManagementServiceProvider::class,", $contents, 1, $count);

        if ($count !== 1 || ! is_string($contents) || file_put_contents($path, $contents) === false) {
            throw new RuntimeException('Unable to register UserManagementServiceProvider safely in bootstrap/providers.php.');
        }
    }

    private function updateUserModel(string $workingDirectory): void
    {
        $path = $workingDirectory.'/app/Models/User.php';
        $contents = $this->read($path, 'app/Models/User.php');
        $import = 'use Spatie\\Permission\\Traits\\HasRoles;';

        if (! str_contains($contents, $import)) {
            $contents = preg_replace_callback(
                '/(namespace App\\\\Models;\s*)/',
                fn (array $matches): string => $matches[1]."\n{$import}\n",
                $contents,
                1,
            ) ?? $contents;
        }

        if (! str_contains($contents, 'use HasRoles;')) {
            $contents = preg_replace('/(class\s+User\s+extends\s+[^\{]+\{)/', "$1\n    use HasRoles;", $contents, 1, $count) ?? $contents;

            if ($count !== 1) {
                throw new RuntimeException('Unable to add HasRoles to app/Models/User.php safely.');
            }
        }

        if (file_put_contents($path, $contents) === false) {
            throw new RuntimeException('Unable to update app/Models/User.php for user and role management.');
        }
    }

    private function registerSeeder(string $workingDirectory): void
    {
        $path = $workingDirectory.'/database/seeders/DatabaseSeeder.php';
        $contents = $this->read($path, 'database/seeders/DatabaseSeeder.php');
        $source = dirname(__DIR__, 2).'/resources/user-management/database/seeders/DatabaseSeeder.php';

        if (str_contains($contents, '// Dcode Starter Kit database seeder')) {
            return;
        }

        $isLegacyStarterSeeder = str_contains($contents, 'DcodeStarterKitSeeder::class')
            || str_contains($contents, 'use Database\\Seeders\\DcodeStarterKitSeeder;');

        if (! $isLegacyStarterSeeder && ! $this->isDefaultDatabaseSeeder($contents)) {
            throw new RuntimeException('Refusing to replace customised database/seeders/DatabaseSeeder.php. Move the starter seeding logic into your existing seeder manually, then rerun.');
        }

        $replacement = file_get_contents($source);

        if (! is_string($replacement) || file_put_contents($path, $replacement) === false) {
            throw new RuntimeException('Unable to replace database/seeders/DatabaseSeeder.php safely.');
        }
    }

    private function isDefaultDatabaseSeeder(string $contents): bool
    {
        if (! str_contains($contents, 'class DatabaseSeeder extends Seeder')) {
            return false;
        }

        $withoutComments = preg_replace('/\/\/.*$/m', '', $contents) ?? $contents;
        $withoutComments = preg_replace('/\/\*.*?\*\//s', '', $withoutComments) ?? $withoutComments;

        return preg_match('/public function run\(\):\s*void\s*\{\s*\}/s', $withoutComments) === 1
            || preg_match("/public function run\\(\\):\\s*void\\s*\\{.*User::factory\\(\\)->create\\(\\[.*['\"]name['\"]\\s*=>\\s*['\"]Test User['\"].*['\"]email['\"]\\s*=>\\s*['\"]test@example\\.com['\"].*\\]\\);.*\\}/s", $withoutComments) === 1;
    }

    private function configurePermissionRole(string $workingDirectory): void
    {
        $path = $workingDirectory.'/config/permission.php';

        if (! is_file($path)) {
            return;
        }

        $contents = $this->read($path, 'config/permission.php');

        if (str_contains($contents, 'App\\Models\\Role::class')) {
            return;
        }

        if (! str_contains($contents, "'role' => Role::class")) {
            throw new RuntimeException('Refusing to change a customized config/permission.php. Point models.role to App\\Models\\Role::class manually, then rerun.');
        }

        $contents = str_replace(
            'use Spatie\\Permission\\Models\\Role;',
            'use App\\Models\\Role;',
            $contents,
        );
        $contents = str_replace("'role' => Role::class", "'role' => Role::class", $contents);

        if (file_put_contents($path, $contents) === false) {
            throw new RuntimeException('Unable to configure config/permission.php to use App\\Models\\Role.');
        }
    }

    private function registerRoutes(string $workingDirectory): void
    {
        $path = $workingDirectory.'/routes/web.php';
        $contents = $this->read($path, 'routes/web.php');

        if (! str_contains($contents, self::ROUTE_MARKER) && (str_contains($contents, "Route::resource('users'") || str_contains($contents, "Route::resource('roles'"))) {
            throw new RuntimeException('Existing users or roles routes were found in routes/web.php. Review them before adding the starter management routes.');
        }

        $imports = [
            'App\\Http\\Controllers\\Admin\\Roles\\RoleController',
            'App\\Http\\Controllers\\Admin\\Users\\UserController',
            'App\\Http\\Middleware\\AdminAuditLog',
            'App\\Http\\Middleware\\HandleDcodeInertiaRequests',
            'Dcodegroup\\LaravelDsgTable\\Http\\Controllers\\TableController',
            'Dcodegroup\\LaravelDsgTable\\Http\\Controllers\\TableFiltersController',
            'Illuminate\\Support\\Facades\\Route',
        ];
        $missingImports = '';

        foreach ($imports as $import) {
            if (! str_contains($contents, "use {$import};")) {
                $missingImports .= "use {$import};\n";
            }
        }

        if ($missingImports !== '') {
            $contents = preg_replace('/(<\?php\s*)/', "$1\n{$missingImports}", $contents, 1) ?? $contents;
        }

        $contents = str_replace(
            "Route::middleware([HandleDcodeInertiaRequests::class, 'auth'])\n    ->prefix('admin')",
            "Route::middleware([HandleDcodeInertiaRequests::class, 'auth', AdminAuditLog::class])\n    ->prefix('admin')",
            $contents,
        );

        $tableRoutes = "\n\n".self::TABLE_ROUTE_MARKER.<<<'PHP'

Route::middleware([HandleDcodeInertiaRequests::class, 'auth'])->group(function (): void {
    Route::get('dsg-table/{tableName}/filters/{param?}', TableFiltersController::class)->name('dsg-table.filters');
    Route::get('dsg-table/{tableName}/{param?}', TableController::class)->name('dsg-table');
});
PHP;

        if (str_contains($contents, self::ROUTE_MARKER)) {
            if (str_contains($contents, self::TABLE_ROUTE_MARKER)) {
                if (file_put_contents($path, $contents) === false) {
                    throw new RuntimeException('Unable to update starter user and role routes safely.');
                }

                return;
            }

            $contents = str_replace(self::ROUTE_MARKER, self::ROUTE_MARKER.$tableRoutes, $contents);

            if (file_put_contents($path, $contents) === false) {
                throw new RuntimeException('Unable to add starter DSG table routes safely.');
            }

            return;
        }

        $routes = "\n\n".self::ROUTE_MARKER.$tableRoutes.<<<'PHP'

Route::middleware([HandleDcodeInertiaRequests::class, 'auth', AdminAuditLog::class])
    ->prefix('admin')
    ->as('admin.')
    ->group(function (): void {
        Route::resource('users', UserController::class)->except(['show']);
        Route::resource('roles', RoleController::class)->except(['show']);
    });
PHP;

        if (file_put_contents($path, rtrim($contents).$routes."\n") === false) {
            throw new RuntimeException('Unable to register starter user and role routes safely.');
        }
    }

    private function read(string $path, string $label): string
    {
        $contents = is_file($path) ? file_get_contents($path) : false;

        if (! is_string($contents)) {
            throw new RuntimeException("Unable to read {$label}; user and role management could not be configured safely.");
        }

        return $contents;
    }
}
