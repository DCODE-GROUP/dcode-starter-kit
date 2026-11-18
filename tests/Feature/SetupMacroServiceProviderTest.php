<?php

declare(strict_types=1);

use DcodeStarterKit\DcodeStarterKit\Actions\SetupMacroServiceProvider;

function macroProviderFixture(): string
{
    $directory = sys_get_temp_dir().'/dcode-macro-'.bin2hex(random_bytes(5));
    mkdir($directory.'/bootstrap', 0777, true);
    file_put_contents($directory.'/bootstrap/providers.php', "<?php\n\nreturn [\n    App\\Providers\\AppServiceProvider::class,\n];\n");

    return $directory;
}

it('creates and registers the macro service provider', function () {
    $directory = macroProviderFixture();
    $messages = [];

    expect((new SetupMacroServiceProvider)->setup($directory, function (string $message) use (&$messages): void {
        $messages[] = $message;
    }))->toBeTrue()
        ->and(file_get_contents($directory.'/app/Providers/MacroServiceProvider.php'))
        ->toContain("Blueprint::macro('lastModifiedFields'")
        ->and(file_get_contents($directory.'/bootstrap/providers.php'))
        ->toContain('App\\Providers\\MacroServiceProvider::class')
        ->and($messages)->toContain("MacroServiceProvider created and registered.\n");
});

it('is idempotent for its generated provider', function () {
    $directory = macroProviderFixture();
    $action = new SetupMacroServiceProvider;

    expect($action->setup($directory, fn () => null))->toBeTrue()
        ->and($action->setup($directory, fn () => null))->toBeTrue()
        ->and(substr_count((string) file_get_contents($directory.'/bootstrap/providers.php'), 'MacroServiceProvider::class'))->toBe(1);
});

it('does not overwrite a customised macro service provider', function () {
    $directory = macroProviderFixture();
    mkdir($directory.'/app/Providers', 0777, true);
    file_put_contents($directory.'/app/Providers/MacroServiceProvider.php', "<?php\n\n// Custom provider\n");
    $messages = [];

    expect((new SetupMacroServiceProvider)->setup($directory, function (string $message) use (&$messages): void {
        $messages[] = $message;
    }))->toBeFalse()
        ->and(implode('', $messages))->toContain('already exists and was not created');
});
