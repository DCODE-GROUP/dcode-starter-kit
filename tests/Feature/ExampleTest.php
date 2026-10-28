<?php

declare(strict_types=1);

use DcodeStarterKit\DcodeStarterKit\DcodeStarterKit;

it('resolves the singleton', function () {
    expect(app(DcodeStarterKit::class))->toBeInstanceOf(DcodeStarterKit::class);
});

it('returns the same instance from the container', function () {
    expect(app(DcodeStarterKit::class))->toBe(app(DcodeStarterKit::class));
});

it('merges the package config', function () {
    expect(config('dcode-starter-kit.placeholder'))->toBe('default');
});

it('loads the package translations', function () {
    expect(trans('dcode-starter-kit::messages.placeholder'))->toBe('DcodeStarterKit placeholder translation.');
});

it('loads the package views', function () {
    expect(view()->exists('dcode-starter-kit::placeholder'))->toBeTrue();
});

it('registers the artisan command', function () {
    $this->artisan('dcode-starter-kit:placeholder')
        ->expectsOutputToContain('DcodeStarterKit placeholder command executed.')
        ->assertSuccessful();
});
