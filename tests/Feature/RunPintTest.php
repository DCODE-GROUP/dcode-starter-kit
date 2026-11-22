<?php

declare(strict_types=1);

use DcodeStarterKit\DcodeStarterKit\Actions\RunPint;

it('runs Pint against the whole application', function (): void {
    $calls = [];
    $runner = function (array $arguments, string $workingDirectory, callable $output) use (&$calls): bool {
        $calls[] = [$arguments, $workingDirectory];

        return true;
    };

    expect((new RunPint($runner))->run('/tmp', fn () => null))->toBeTrue()
        ->and($calls)->toBe([[['vendor/bin/pint'], '/tmp']]);
});

it('reports Pint failures', function (): void {
    $messages = [];

    expect((new RunPint(fn () => false))->run('/tmp', function (string $message) use (&$messages): void {
        $messages[] = $message;
    }))->toBeFalse()
        ->and(implode('', $messages))->toContain('Unable to format the application with Pint.');
});
