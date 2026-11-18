<?php

declare(strict_types=1);

use DcodeStarterKit\DcodeStarterKit\Actions\SetupFortifySanctum;

function fortifySanctumFixture(): string
{
    $directory = sys_get_temp_dir().'/dcode-fortify-'.bin2hex(random_bytes(5));
    mkdir($directory.'/bootstrap', 0777, true);
    mkdir($directory.'/app/Models', 0777, true);
    file_put_contents($directory.'/bootstrap/providers.php', "<?php\n\nreturn [\n];\n");
    file_put_contents($directory.'/app/Models/User.php', <<<'PHP'
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    protected $fillable = [
        'name',
        'email',
        'password',
    ];
}
PHP);

    return $directory;
}

it('installs the host Fortify and Sanctum foundation', function () {
    $directory = fortifySanctumFixture();
    $messages = [];

    expect((new SetupFortifySanctum)->setup($directory, function (string $message) use (&$messages): void {
        $messages[] = $message;
    }))->toBeTrue()
        ->and(is_file($directory.'/app/Providers/FortifyServiceProvider.php'))->toBeTrue()
        ->and(is_file($directory.'/app/Actions/Fortify/CreateNewUser.php'))->toBeTrue()
        ->and(is_file($directory.'/config/fortify.php'))->toBeTrue()
        ->and(is_file($directory.'/database/migrations/2019_12_14_000001_create_personal_access_tokens_table.php'))->toBeTrue()
        ->and(file_get_contents($directory.'/bootstrap/providers.php'))->toContain('App\\Providers\\FortifyServiceProvider::class')
        ->and(file_get_contents($directory.'/app/Models/User.php'))
        ->toContain('use Laravel\\Sanctum\\HasApiTokens;')
        ->toContain('use Laravel\\Fortify\\TwoFactorAuthenticatable;')
        ->toContain("'first_name'")
        ->and(file_get_contents($directory.'/app/Actions/Fortify/UpdateUserProfileInformation.php'))
        ->toContain("'photo' => ['nullable', 'mimes:jpg,jpeg,png', 'max:1024'],")
        ->toContain("store('profile-photos', 'public')")
        ->and($messages)->toContain("Fortify and Sanctum foundation configured.\n");
});

it('is idempotent and refuses customised Fortify files', function () {
    $directory = fortifySanctumFixture();
    $action = new SetupFortifySanctum;

    expect($action->setup($directory, fn () => null))->toBeTrue()
        ->and($action->setup($directory, fn () => null))->toBeTrue();

    file_put_contents($directory.'/config/fortify.php', "<?php return ['custom' => true];");
    $messages = [];

    expect($action->setup($directory, function (string $message) use (&$messages): void {
        $messages[] = $message;
    }))->toBeFalse()
        ->and(implode('', $messages))->toContain('Refusing to overwrite customised config/fortify.php');
});

it('updates Laravel 13 attribute-based user fillable configuration', function () {
    $directory = fortifySanctumFixture();
    $path = $directory.'/app/Models/User.php';
    $contents = (string) file_get_contents($path);
    $contents = str_replace(
        'use Illuminate\\Foundation\\Auth\\User as Authenticatable;',
        "use Illuminate\\Database\\Eloquent\\Attributes\\Fillable;\nuse Illuminate\\Foundation\\Auth\\User as Authenticatable;",
        $contents,
    );
    $contents = str_replace(
        "    protected \$fillable = [\n        'name',\n        'email',\n        'password',\n    ];",
        "    #[Fillable(['name', 'email', 'password'])]",
        $contents,
    );
    file_put_contents($path, $contents);

    expect((new SetupFortifySanctum)->setup($directory, fn () => null))->toBeTrue()
        ->and(file_get_contents($path))->toContain("#[Fillable([\n        'first_name',\n        'middle_name',\n        'last_name',\n        'active',");
});
