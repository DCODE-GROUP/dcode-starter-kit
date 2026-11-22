<?php

declare(strict_types=1);

use DcodeStarterKit\DcodeStarterKit\Actions\SetupComposerHooks;

function composerHooksFixture(array $composer): string
{
    $directory = sys_get_temp_dir().'/dcode-composer-hooks-'.bin2hex(random_bytes(5));
    mkdir($directory, 0777, true);
    file_put_contents($directory.'/composer.json', json_encode($composer, JSON_PRETTY_PRINT)."\n");

    return $directory;
}

it('adds the project starter hooks while preserving other composer metadata', function () {
    $directory = composerHooksFixture([
        'name' => 'test/application',
        'extra' => [
            'laravel' => ['dont-discover' => []],
        ],
    ]);

    $calls = [];
    $action = new SetupComposerHooks(function (array $arguments, string $workingDirectory, callable $output) use (&$calls): bool {
        $calls[] = [$arguments, $workingDirectory];

        return true;
    });

    expect($action->setup($directory, fn () => null))->toBeTrue()
        ->and($calls)->toBe([[['./vendor/bin/cghooks', 'update'], $directory]]);

    $composer = json_decode((string) file_get_contents($directory.'/composer.json'), true);

    expect($composer['name'])->toBe('test/application')
        ->and($composer['extra']['laravel'])->toBe(['dont-discover' => []])
        ->and($composer['extra']['hooks'])->toBe([
            'config' => ['stop-on-failure' => ['pre-push']],
            'pre-commit' => [
                "echo '\n\n\\033[0;36mcghooks - Pre-Commit: \\033[0m \n'",
                "echo 'Committing as: \\033[0;32m'\$(git config user.name) '('\$(git config user.email)') \\033[0m\n'",
                "echo '\n\\033[0;35mRunning Pint (PHP CS Fixer): \\033[0m\n'",
                'vendor/bin/pint --dirty',
                "echo '\n\\033[0;35mRunning ESLint: \\033[0m\n'",
                "echo '    - pnpm run lint:fix'",
                "echo '\n'",
            ],
            'pre-push' => [
                "echo '\n\n\\033[0;36mcghooks - Pre-Push: \\033[0m \n'",
                "echo '\n\\033[0;35mRunning PHPStan: \\033[0m\n'",
                'vendor/bin/phpstan --error-format=github',
                "echo '\n\\033[0;35mRunning Architecture Tests: \\033[0m\n'",
                'vendor/bin/pest --testsuite Architecture --colors -c phpunit.xml.dist',
                "echo '\n'",
            ],
            'post-merge' => ['.dev/post-merge'],
        ]);
});

it('is safe to rerun when the hooks are already configured', function () {
    $directory = composerHooksFixture(['extra' => []]);
    $action = new SetupComposerHooks(fn () => true);

    expect($action->setup($directory, fn () => null))->toBeTrue();
    $before = file_get_contents($directory.'/composer.json');
    expect($action->setup($directory, fn () => null))->toBeTrue()
        ->and(file_get_contents($directory.'/composer.json'))->toBe($before);
});

it('fails when cghooks cannot update the installed hooks', function () {
    $directory = composerHooksFixture(['extra' => []]);
    $messages = [];

    expect((new SetupComposerHooks(fn () => false))->setup($directory, function (string $message) use (&$messages): void {
        $messages[] = $message;
    }))->toBeFalse()
        ->and(implode('', $messages))->toContain('Unable to update the installed Composer hooks.');
});
