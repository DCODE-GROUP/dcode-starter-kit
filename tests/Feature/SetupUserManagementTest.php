<?php

declare(strict_types=1);

use DcodeStarterKit\DcodeStarterKit\Actions\SetupUserManagement;

function userManagementFixture(): string
{
    $directory = sys_get_temp_dir().'/dcode-user-management-'.bin2hex(random_bytes(5));
    mkdir($directory.'/app/Models', 0777, true);
    mkdir($directory.'/bootstrap', 0777, true);
    mkdir($directory.'/config', 0777, true);
    mkdir($directory.'/database/seeders', 0777, true);
    mkdir($directory.'/routes', 0777, true);
    file_put_contents($directory.'/bootstrap/providers.php', "<?php\n\nreturn [\n];\n");
    file_put_contents($directory.'/config/permission.php', <<<'PHP'
<?php

use Spatie\Permission\Models\Role;

return [
    'models' => [
        'role' => Role::class,
    ],
];
PHP);
    file_put_contents($directory.'/database/seeders/DatabaseSeeder.php', <<<'PHP'
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use Illuminate\Database\Console\Seeds\WithoutModelEvents;

    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
    }
}
PHP);
    file_put_contents($directory.'/routes/web.php', "<?php\n\nuse Illuminate\\Support\\Facades\\Route;\n");
    file_put_contents($directory.'/app/Models/User.php', <<<'PHP'
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
}
PHP);

    return $directory;
}

it('installs permission-based user and role management into the host application', function () {
    $directory = userManagementFixture();

    expect((new SetupUserManagement)->setup($directory, fn () => null))->toBeTrue()
        ->and(is_file($directory.'/app/Models/Role.php'))->toBeTrue()
        ->and(is_file($directory.'/app/Policies/UserPolicy.php'))->toBeTrue()
        ->and(is_file($directory.'/app/Http/Controllers/Admin/Users/UserController.php'))->toBeTrue()
        ->and(is_file($directory.'/database/seeders/DatabaseSeeder.php'))->toBeTrue()
        ->and(file_get_contents($directory.'/bootstrap/providers.php'))->toContain('UserManagementServiceProvider::class')
        ->and(file_get_contents($directory.'/config/permission.php'))->toContain('use App\\Models\\Role;')
        ->and(file_get_contents($directory.'/app/Models/User.php'))->toContain('use HasRoles;')
        ->and(file_get_contents($directory.'/database/seeders/DatabaseSeeder.php'))->toContain('Dcode Starter Kit database seeder')
        ->and(file_get_contents($directory.'/database/seeders/DatabaseSeeder.php'))->toContain('forge@dcodegroup.com')
        ->and(is_file($directory.'/app/Tables/UsersTable.php'))->toBeTrue()
        ->and(is_file($directory.'/app/Tables/RolesTable.php'))->toBeTrue()
        ->and(file_get_contents($directory.'/app/Tables/UsersTable.php'))->toContain("SlotColumn::make('full_name', 'Name')->isSortable(false)->toArray()")
        ->and(file_get_contents($directory.'/app/Tables/RolesTable.php'))->toContain("Column::make('name', 'Name')->isSortable()->toArray()")
        ->and(file_get_contents($directory.'/resources/js/pages/Admin/Users/Index.vue'))->toContain('DsgTable')
        ->and(file_get_contents($directory.'/resources/js/pages/Admin/Roles/Index.vue'))->toContain('DsgTable')
        ->and(file_get_contents($directory.'/routes/web.php'))->toContain('DsgTable\\Http\\Controllers\\TableController')
        ->and(file_get_contents($directory.'/routes/web.php'))->toContain("Route::resource('users', UserController::class)");
});

it('does not overwrite customized user management files', function () {
    $directory = userManagementFixture();
    file_put_contents($directory.'/app/Models/Role.php', '<?php // custom');

    $messages = [];

    expect((new SetupUserManagement)->setup($directory, function (string $message) use (&$messages): void {
        $messages[] = $message;
    }))->toBeFalse()
        ->and(implode('', $messages))->toContain('Refusing to overwrite customised app/Models/Role.php');
});

it('can be rerun after user management setup is complete', function () {
    $directory = userManagementFixture();
    $setup = new SetupUserManagement;

    expect($setup->setup($directory, fn () => null))->toBeTrue()
        ->and($setup->setup($directory, fn () => null))->toBeTrue();
});

it('adds DSG table routes to an earlier user management installation', function () {
    $directory = userManagementFixture();
    $setup = new SetupUserManagement;

    expect($setup->setup($directory, fn () => null))->toBeTrue();

    $path = $directory.'/routes/web.php';
    $routes = (string) file_get_contents($path);
    $routes = preg_replace('/\/\/ Dcode Starter Kit DSG table routes.*?\n\}\);\n/s', '', $routes) ?? $routes;
    file_put_contents($path, $routes);

    expect($setup->setup($directory, fn () => null))->toBeTrue()
        ->and(file_get_contents($path))->toContain('// Dcode Starter Kit DSG table routes')
        ->and(substr_count((string) file_get_contents($path), "->name('dsg-table')"))->toBe(1);
});

it('refuses to replace a customized database seeder', function () {
    $directory = userManagementFixture();
    $path = $directory.'/database/seeders/DatabaseSeeder.php';
    file_put_contents($path, str_replace(
        "User::factory()->create([\n            'name' => 'Test User',\n            'email' => 'test@example.com',\n        ]);",
        'User::query()->create();',
        (string) file_get_contents($path),
    ));
    $messages = [];

    expect((new SetupUserManagement)->setup($directory, function (string $message) use (&$messages): void {
        $messages[] = $message;
    }))->toBeFalse()
        ->and(implode('', $messages))->toContain('Refusing to replace customised database/seeders/DatabaseSeeder.php');
});

it('upgrades the previous separate starter seeder wiring', function () {
    $directory = userManagementFixture();
    $path = $directory.'/database/seeders/DatabaseSeeder.php';
    file_put_contents($path, <<<'PHP'
<?php

use Database\Seeders\DcodeStarterKitSeeder;
namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(DcodeStarterKitSeeder::class);
    }
}
PHP);

    expect((new SetupUserManagement)->setup($directory, fn () => null))->toBeTrue()
        ->and(file_get_contents($path))->toContain('Dcode Starter Kit database seeder')
        ->and(file_get_contents($path))->not->toContain('DcodeStarterKitSeeder::class');
});
